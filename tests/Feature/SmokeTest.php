<?php

use App\Models\Teacher\Teacher;

it('responde /up com 200', function () {
    $this->get('/up')->assertOk();
});

it('redireciona convidados da home para o login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('renderiza o login', function () {
    $this->get('/login')->assertOk();
});

it('renderiza o registro', function () {
    $this->get('/register')->assertOk();
});

it('redireciona rota do professor para login', function () {
    $this->get('/professor/disciplina')->assertRedirect(route('login'));
});

it('redireciona rota do aluno para o login do aluno', function () {
    $this->get('/aluno/disciplina')->assertRedirect(route('student.login'));
});

it('renderiza as rotas de autenticacao', function (string $url) {
    $this->get($url)->assertOk();
})->with([
    '/teacher/login',
    '/student/login',
    '/teacher/register',
    '/forgot-password',
    '/reset-password/token-de-teste',
]);


it('redireciona professor autenticado da home para disciplinas', function () {
    $teacher = Teacher::factory()->create();

    $this->actingAs($teacher, 'teacher')
        ->get('/')
        ->assertRedirect(route('teacher.discipline'));
});
