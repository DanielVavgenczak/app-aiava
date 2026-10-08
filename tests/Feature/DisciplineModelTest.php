<?php

use App\Models\Module;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('aplica soft delete e esconde a disciplina por padrão', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();

    $discipline->delete();

    expect(Discipline::find($discipline->id))->toBeNull()
        ->and(Discipline::withTrashed()->find($discipline->id)->trashed())->toBeTrue()
        ->and(Discipline::onlyTrashed()->whereKey($discipline->id)->exists())->toBeTrue();
});

it('resolve o relacionamento com o professor por teacher() e Teacher()', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();

    expect($discipline->teacher()->getRelated())->toBeInstanceOf(Teacher::class)
        ->and($discipline->Teacher()->getRelated())->toBeInstanceOf(Teacher::class);
});

it('filtra por professor e título normalizado', function () {
    $teacher = Teacher::factory()->create();
    $other = Teacher::factory()->create();
    $matematica = Discipline::factory()->for($teacher)->create(['title' => 'Matematica']);
    Discipline::factory()->for($teacher)->create(['title' => 'Historia']);
    Discipline::factory()->for($other)->create(['title' => 'matematica']);

    expect(Discipline::forTeacher($teacher->id)->count())->toBe(2)
        ->and(Discipline::forTeacher($teacher->id)->normalized('  MATEMATICA  ')->value('id'))->toBe($matematica->id);
});

it('teacherHasDiscipline detecta duplicata inclusive soft-deletada', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create(['title' => 'Matematica']);

    expect(Discipline::teacherHasDiscipline($teacher->id, '  MATEMATICA '))->toBeTrue();

    $discipline->delete();

    expect(Discipline::teacherHasDiscipline($teacher->id, 'Matematica'))->toBeTrue();
});

it('impede duplicatas de (teacher_id, title) no banco', function () {
    $teacher = Teacher::factory()->create();
    Discipline::factory()->for($teacher)->create(['title' => 'Matematica']);

    $this->expectException(QueryException::class);

    DB::table('disciplines')->insert([
        'id' => (string) Str::uuid(),
        'teacher_id' => $teacher->id,
        'title' => 'Matematica',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('permite o mesmo título para professores diferentes', function () {
    $a = Teacher::factory()->create();
    $b = Teacher::factory()->create();
    Discipline::factory()->for($a)->create(['title' => 'Matematica']);
    Discipline::factory()->for($b)->create(['title' => 'Matematica']);

    expect(Discipline::count())->toBe(2);
});

it('expoe modules_count via withCount', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    Module::factory()->count(2)->create(['discipline_id' => $discipline->id]);

    $loaded = Discipline::withCount('modules')->find($discipline->id);

    expect($loaded->modules_count)->toBe(2);
});