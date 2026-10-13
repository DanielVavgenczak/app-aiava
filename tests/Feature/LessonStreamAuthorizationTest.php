<?php

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

function makeLesson(Teacher $teacher, Discipline $discipline): Lesson
{
    $module = Module::create([
        'title' => 'Módulo ' . uniqid(),
        'discipline_id' => $discipline->id,
    ]);

    return Lesson::create([
        'original_name' => 'aula.mp4',
        'hash_name' => 'aula.mp4',
        'path_file' => 'videos/lessons/aula.mp4',
        'title' => 'Bloco 01',
        'module_id' => $module->id,
        'teacher_id' => $teacher->id,
    ]);
}

function makeStudentFrom(Teacher $teacher, Discipline $discipline): Student
{
    $classe = Classe::factory()->create([
        'teacher_id' => $teacher->id,
        'discipline_id' => $discipline->id,
    ]);

    return Student::factory()->create([
        'teacher_id' => $teacher->id,
        'classe_id' => $classe->id,
        'discipline_id' => $discipline->id,
    ]);
}

function signedStreamUrl(Lesson $lesson): string
{
    return URL::temporarySignedRoute('media.stream', now()->addMinutes(30), ['lesson_id' => $lesson->id]);
}

it('nega stream de aula de outra disciplina para o aluno', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $lesson = makeLesson($teacher, $discipline);

    $otherTeacher = Teacher::factory()->create();
    $otherDiscipline = Discipline::factory()->for($otherTeacher)->create();
    $student = makeStudentFrom($otherTeacher, $otherDiscipline);

    $this->actingAs($student, 'student')
        ->get(signedStreamUrl($lesson))
        ->assertForbidden();
});

it('autoriza stream da própria disciplina (arquivo ausente => 404)', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $lesson = makeLesson($teacher, $discipline);
    $student = makeStudentFrom($teacher, $discipline);

    $this->actingAs($student, 'student')
        ->get(signedStreamUrl($lesson))
        ->assertNotFound();
});

it('nega stream para professor que não é dono da disciplina', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $lesson = makeLesson($teacher, $discipline);

    $other = Teacher::factory()->create();

    $this->actingAs($other, 'teacher')
        ->get(signedStreamUrl($lesson))
        ->assertForbidden();
});

it('rejeita stream sem assinatura válida', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $lesson = makeLesson($teacher, $discipline);

    $this->actingAs($teacher, 'teacher')
        ->get(route('media.stream', $lesson->id))
        ->assertForbidden();
});

it('responde 200 com o tamanho total quando não há Range', function () {
    Storage::fake('private');
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $lesson = makeLesson($teacher, $discipline);
    Storage::disk('private')->put($lesson->path_file, str_repeat('A', 1000));

    $this->actingAs($teacher, 'teacher')
        ->get(signedStreamUrl($lesson))
        ->assertStatus(200)
        ->assertHeader('Accept-Ranges', 'bytes')
        ->assertHeader('Content-Length', '1000');
});

it('responde 206 com Content-Range ao solicitar um intervalo de bytes', function () {
    Storage::fake('private');
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $lesson = makeLesson($teacher, $discipline);
    Storage::disk('private')->put($lesson->path_file, str_repeat('A', 1000));

    $this->actingAs($teacher, 'teacher')
        ->get(signedStreamUrl($lesson), ['Range' => 'bytes=0-99'])
        ->assertStatus(206)
        ->assertHeader('Content-Range', 'bytes 0-99/1000')
        ->assertHeader('Content-Length', '100');
});

it('responde 416 quando o intervalo está fora do arquivo', function () {
    Storage::fake('private');
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $lesson = makeLesson($teacher, $discipline);
    Storage::disk('private')->put($lesson->path_file, str_repeat('A', 1000));

    $this->actingAs($teacher, 'teacher')
        ->get(signedStreamUrl($lesson), ['Range' => 'bytes=5000-6000'])
        ->assertStatus(416)
        ->assertHeader('Content-Range', 'bytes */1000');
});
