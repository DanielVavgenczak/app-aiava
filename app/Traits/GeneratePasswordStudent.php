<?php

namespace App\Traits;

trait GeneratePasswordStudent
{
    public static function generateRandomPassword(int $length = 6): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%&*';

        $all = $uppercase . $lowercase . $numbers . $symbols;

        // Garante pelo menos 1 de cada tipo
        $password = [
            $uppercase[rand(0, strlen($uppercase) - 1)],
            $lowercase[rand(0, strlen($lowercase) - 1)],
            $numbers[rand(0, strlen($numbers) - 1)],
            $symbols[rand(0, strlen($symbols) - 1)],
        ];

        // Completa o restante
        for ($i = 4; $i < $length; $i++) {
            $password[] = $all[rand(0, strlen($all) - 1)];
        }

        // Embaralha
        shuffle($password);

        return implode('', $password);
    }
    /**
     * Gera e retorna senha + hash juntos
     *
     * @param int $length
     * @return array
     */
    public function generatePasswordWithHash(int $length = 8): array
    {
        $password = $this->generateRandomPassword($length);

        return [
            'plain' => $password,
            'hashed' => bcrypt($password),
        ];
    }
}
