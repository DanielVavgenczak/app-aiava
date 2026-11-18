<?php

use App\Models\Teacher\Classe;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Student;
use App\Models\Teacher\Teacher;

function makeStudentWithClassForMessagesTour(Teacher $teacher, Discipline $discipline): Student
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

it('exibe a imagem e a acao de iniciar conversa no tour do chat do aluno', function () {
    $teacher = Teacher::factory()->create();
    $discipline = Discipline::factory()->for($teacher)->create();
    $student = makeStudentWithClassForMessagesTour($teacher, $discipline);

    $this->actingAs($student, 'student')
        ->get(route('student.messages'))
        ->assertOk()
        ->assertSee('images/rb-aiava.png', false)
        ->assertDontSee('images/ai-sos.png', false)
        ->assertSee('Iniciar uma conversa')
        ->assertSee('StartTourInitChatInClasse')
        ->assertDontSee('Como usar o chat');
});
