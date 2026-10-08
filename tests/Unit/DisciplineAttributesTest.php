<?php

use App\Models\Teacher\Discipline;

it('normaliza o título com trim e minúsculas', function () {
    expect(Discipline::normalizeTitle('  Matematica  '))->toBe('matematica');
});

it('aplica trim (sem alterar caixa) ao definir o título via mutator', function () {
    $discipline = new Discipline(['title' => '  Matematica Basica  ']);

    expect($discipline->title)->toBe('Matematica Basica');
});