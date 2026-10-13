<?php

namespace App\Support;

class ActivityValue
{
    /**
     * Converte valores recebidos da UI (que usam vírgula como separador
     * decimal) para float, aceitando também notação com ponto.
     */
    public static function toFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return (float) str_replace(',', '.', (string) $value);
    }

    /**
     * Formata um valor numérico para exibição em pt-BR, removendo zeros
     * decimais desnecessários (5,00 → 5 | 5,50 → 5,5 | 5,25 → 5,25).
     */
    public static function format(mixed $value): string
    {
        $number = static::toFloat($value);

        if ($number == (int) $number) {
            return number_format($number, 0, ',', '');
        }

        $formatted = number_format($number, 2, ',', '');

        if (str_ends_with($formatted, '0')) {
            $formatted = rtrim(rtrim($formatted, '0'), ',');
        }

        return $formatted;
    }
}
