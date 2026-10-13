<?php

use App\Support\ActivityValue;

it('converte valores com vírgula e ponto para float', function () {
    expect(ActivityValue::toFloat('2,5'))->toBe(2.5)
        ->and(ActivityValue::toFloat('5,00'))->toBe(5.0)
        ->and(ActivityValue::toFloat('10'))->toBe(10.0)
        ->and(ActivityValue::toFloat('3.75'))->toBe(3.75)
        ->and(ActivityValue::toFloat(4))->toBe(4.0)
        ->and(ActivityValue::toFloat(null))->toBe(0.0)
        ->and(ActivityValue::toFloat(''))->toBe(0.0);
});

it('formata números para pt-BR removendo zeros desnecessários', function () {
    expect(ActivityValue::format(5))->toBe('5')
        ->and(ActivityValue::format('5,00'))->toBe('5')
        ->and(ActivityValue::format(2.5))->toBe('2,5')
        ->and(ActivityValue::format('2,50'))->toBe('2,5')
        ->and(ActivityValue::format(5.25))->toBe('5,25')
        ->and(ActivityValue::format(0.05))->toBe('0,05')
        ->and(ActivityValue::format(10))->toBe('10');
});

it('compara valores decimais numericamente e não como string', function () {
    // Regressão: "2,5" não pode ser considerado maior que "10".
    expect(ActivityValue::toFloat('2,5') > ActivityValue::toFloat('10'))->toBeFalse()
        ->and(ActivityValue::toFloat('10') > ActivityValue::toFloat('2,5'))->toBeTrue()
        ->and(ActivityValue::toFloat('2,5') > ActivityValue::toFloat('2,4'))->toBeTrue();
});
