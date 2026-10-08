<?php

it('responde /up com 200', function () {
    $this->get('/up')->assertOk();
});

it('renderiza a home', function () {
    $this->get('/')->assertOk();
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

it('redireciona rota do aluno para login', function () {
    $this->get('/aluno/disciplina')->assertRedirect(route('login'));
});
