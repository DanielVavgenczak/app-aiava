<?php

namespace App\Support;

use Symfony\Component\Process\Process;

final class VideoDuration
{
    public const MIN_SECONDS = 11;

    public const MAX_SECONDS = 720;

    public const MIN_MESSAGE = 'O vídeo deve ter pelo menos 11 segundos.';

    public const MAX_MESSAGE = 'O vídeo excede o limite de 12 minutos.';

    /**
     * Retorna a duração do vídeo em segundos usando o ffprobe.
     * Retorna null quando a duração não pode ser determinada.
     */
    public static function seconds(string $path): ?float
    {
        if (! self::ffprobeAvailable()) {
            return null;
        }

        $process = new Process([
            'ffprobe',
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $path,
        ]);

        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $duration = trim($process->getOutput());

        return is_numeric($duration) ? (float) $duration : null;
    }

    /**
     * Retorna a mensagem de erro da duração, ou null quando está dentro dos
     * limites ou quando não foi possível determiná-la.
     */
    public static function validationError(string $path): ?string
    {
        $duration = self::seconds($path);

        if ($duration === null) {
            return null;
        }

        if ($duration < self::MIN_SECONDS) {
            return self::MIN_MESSAGE;
        }

        if ($duration > self::MAX_SECONDS) {
            return self::MAX_MESSAGE;
        }

        return null;
    }

    private static function ffprobeAvailable(): bool
    {
        static $available = null;

        if ($available === null) {
            $which = new Process(['which', 'ffprobe']);
            $which->run();
            $available = $which->isSuccessful();
        }

        return $available;
    }
}
