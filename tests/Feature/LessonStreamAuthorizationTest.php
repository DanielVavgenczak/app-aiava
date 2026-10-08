<?php

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;

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

it('nega stream de aula de outra disciplina para o aluno', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $lesson = makeLesson($teacher, $discipline);

    $otherTeacher = Teacher::factory()->create();
    $otherDiscipline = Discipline::factory()->for($otherTeacher)->create();
    $student = makeStudentFrom($otherTeacher, $otherDiscipline);

    $this->actingAs($student, 'student')
        ->get(route('media.stream', $lesson->id))
        ->assertForbidden();
});

it('autoriza stream da própria disciplina (arquivo ausente => 404)', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $lesson = makeLesson($teacher, $discipline);
    $student = makeStudentFrom($teacher, $discipline);

    $this->actingAs($student, 'student')
        ->get(route('media.stream', $lesson->id))
        ->assertNotFound();
});

it('nega stream para professor que não é dono da disciplina', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $lesson = makeLesson($teacher, $discipline);

    $other = Teacher::factory()->create();

    $this->actingAs($other, 'teacher')
        ->get(route('media.stream', $lesson->id))
        ->assertForbidden();
});
