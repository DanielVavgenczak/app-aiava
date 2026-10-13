<?php
namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Symfony\Component\Process\Process;

class LessonUploadController extends Controller
{
    // Diretório de armazenamento de vídeos
    private const VIDEO_STORAGE_PATH = 'videos/lessons';

    // Duração máxima permitida para um vídeo (12 minutos, em segundos)
    private const MAX_VIDEO_DURATION_SECONDS = 720;

    // Extensões de vídeo aceitas no upload
    private const SUPPORTED_VIDEO_EXTENSIONS = ['mp4', 'webm', 'ogg'];

     // Tipos MIME suportados
    private const SUPPORTED_MIME_TYPES = [
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogg' => 'video/ogg',
        'pdf' => 'application/pdf',
    ];


    /**
     * Stream do vídeo.
     *
     * @param int $lesson_id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function stream(Request $request, $lesson_id)
    {
            $lesson = Lesson::with('module.discipline')->findOrFail($lesson_id);

            $this->authorizeStreamAccess($lesson);

            if (!Storage::disk('private')->exists($lesson->path_file)) {
                abort(404, message: 'Arquivo não encontrado');
            }

            $path = Storage::disk('private')->path($lesson->path_file);
            $fileSize = filesize($path);
            $mimeType = $this->resolveMimeType($path);

            $this->adjustMemoryLimit($fileSize);

            $hasRange = (bool) $request->header('Range');
            [$start, $end] = $this->resolveRange($request, $fileSize);
            $length = $end - $start + 1;

            $headers = $this->prepareHeaders($mimeType, $length, $path);

            if ($hasRange) {
                $headers['Content-Range'] = "bytes {$start}-{$end}/{$fileSize}";
            }

            return response()->stream(function () use ($path, $start, $length) {
                $this->readFileChunked($path, $start, $length);
            }, $hasRange ? 206 : 200, $headers);
    }

    /**
     * Resolve o intervalo de bytes requisitado, aplicando os limites do arquivo.
     *
     * @return array{0:int,1:int}
     */
    private function resolveRange(Request $request, int $fileSize): array
    {
        $range = $request->header('Range');

        if (! $range || ! preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
            return [0, max($fileSize - 1, 0)];
        }

        $start = $matches[1] === '' ? 0 : (int) $matches[1];
        $end = $matches[2] === '' ? $fileSize - 1 : (int) $matches[2];

        if ($start >= $fileSize || $start > $end) {
            abort(416, 'Range Not Satisfiable', ['Content-Range' => "bytes */{$fileSize}"]);
        }

        return [$start, min($end, $fileSize - 1)];
    }

    /**
     * Garante que o usuário autenticado tem vínculo com a disciplina da aula.
     */
    private function authorizeStreamAccess(Lesson $lesson): void
    {
        $disciplineId = $lesson->module?->discipline_id;
        $disciplineTeacherId = $lesson->module?->discipline?->teacher_id;

        if (Auth::guard('teacher')->check()) {
            $currentTeacherId = Auth::guard('teacher')->id();

            if ((string) $lesson->teacher_id === (string) $currentTeacherId
                || (string) $disciplineTeacherId === (string) $currentTeacherId) {
                return;
            }

            abort(403, 'Sem permissão para acessar esta aula.');
        }

        if (Auth::guard('student')->check()) {
            $student = Auth::guard('student')->user();

            if ($student && (string) $student->discipline_id === (string) $disciplineId) {
                return;
            }

            abort(403, 'Sem permissão para acessar esta aula.');
        }

        abort(403, 'Sem permissão para acessar esta aula.');
    }

    /**
     * Upload de vídeo em chunks.
     *
     * @param Request $request
     * @param int $module_id
     * @return JsonResponse
     */
    public function uploadVideo(Request $request, $module_id)
    {
        if (!$request->has('resumableIdentifier')) {
            // Lógica para upload em chunks
           // return response()->json(['success' => true]);
            return response()->json(['error' => 'Invalid request'], 400);
        }

         // Validações adicionais
        $request->validate([
            'description' => 'nullable|string|max:1000',
            'resumableChunkNumber' => 'required|integer',
            'resumableTotalChunks' => 'required|integer',
            'resumableChunkSize' => 'required|integer',
            'resumableTotalSize' => 'required|integer',
            'resumableIdentifier' => 'required|string',
            'resumableFilename' => 'required|string',
            'resumableRelativePath' => 'required|string',
        ]);
        // Verifica se o módulo pertence a uma disciplina do usuário
        if (!Auth::guard('teacher')->user()->disciplines()->whereHas('modules', function ($query) use ($module_id) {
            $query->where('id', $module_id);
        })->exists()) {
            return response()->json(['error' => 'Módulo não encontrado ou sem permissão.'], 404);
        }

        // Determina o próximo número sequencial para o título do vídeo
        $nextNumber = $this->getNextVideoNumber($module_id);
        $title = sprintf('Bloco %02d', $nextNumber);

        // Processa o upload em chunks
        $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

        if (!$receiver->isUploaded()) {
            throw new UploadMissingFileException('Arquivo não enviado.');
        }

        $fileReceived = $receiver->receive(); // Recebe o arquivo

        if ($fileReceived->isFinished()) { // Upload completo
            $file = $fileReceived->getFile(); // Obtém o arquivo

            // Valida a extensão e a duração real do vídeo no servidor.
            // A checagem de duração no navegador pode ser contornada e, no
            // passado, um arquivo rejeitado permanecia na fila do cliente e
            // gerava blocos duplicados.
            if (! $this->isAllowedVideoFile($file)) {
                unlink($file->getPathname());

                return response()->json([
                    'success' => false,
                    'error' => 'Formato de vídeo não suportado. Envie um arquivo MP4, WebM ou OGG.',
                ], 422);
            }

            if ($this->videoDurationExceedsLimit($file->getPathname())) {
                unlink($file->getPathname());

                return response()->json([
                    'success' => false,
                    'error' => 'O vídeo excede o limite de 12 minutos.',
                ], 422);
            }

            $fileName = $this->generateUniqueFileName($file); // Gera um nome único para o arquivo

            // Salva o arquivo no armazenamento (usando o disco 'private')
            $path = Storage::disk('private')->putFileAs(
                self::VIDEO_STORAGE_PATH,
                $file,
                $fileName
            );

            // Remove o arquivo temporário
            unlink($file->getPathname());

            // Cria o registro do vídeo no banco de dados
            $lesson = Lesson::create([
                "original_name" => $file->getClientOriginalName(),
                "hash_name" => $fileName,
                "path_file" => self::VIDEO_STORAGE_PATH . '/' . $fileName, // Caminho relativo ao disco 'private'
                "title" => $title,
                "description" => $request->input('description', ''), //"Descrição teste para o vídeo $fileName",
                "module_id" => $module_id,
                "teacher_id" => Auth::guard('teacher')->user()->id,
            ]);

            // Gera uma URL temporária para o stream do vídeo
            $streamUrl = URL::temporarySignedRoute(
                'media.stream',
                now()->addMinutes(2),
                ['lesson_id' => $lesson->id]
            );

            return response()->json([
                'path' => $streamUrl,
                'filename' => $fileName,
                'success' => true,
                'lesson_id' => $lesson->id,
                'module_id' => $module_id
            ]);

        }

        // Retorna o progresso do upload
        $handler = $fileReceived->handler();
        return response()->json([
            'done' => $handler->getPercentageDone(),
            'status' => true,
        ]);
    }

    /**
     * Gera o próximo número sequencial para o título do vídeo.
     *
     * @param int $module_id
     * @return int
     */
    private function getNextVideoNumber($module_id)
    {
        $lastVideo = Lesson::where('module_id', $module_id)
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastVideo ? (int) preg_replace('/\D/', '', $lastVideo->title) + 1 : 1;
    }

    /**
     * Verifica se a extensão do arquivo enviado é um vídeo suportado.
     */
    private function isAllowedVideoFile($file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($extension, self::SUPPORTED_VIDEO_EXTENSIONS, true);
    }

    /**
     * Retorna a duração do vídeo em segundos usando o ffprobe.
     * Retorna null quando a duração não pode ser determinada.
     */
    private function videoDurationSeconds(string $path): ?float
    {
        if (! $this->ffprobeAvailable()) {
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
     * Indica se o vídeo excede o limite máximo permitido.
     * Quando a duração não pode ser determinada, o upload é permitido.
     */
    private function videoDurationExceedsLimit(string $path): bool
    {
        $duration = $this->videoDurationSeconds($path);

        return $duration !== null && $duration > self::MAX_VIDEO_DURATION_SECONDS;
    }

    /**
     * Verifica se o binário ffprobe está disponível no ambiente.
     */
    private function ffprobeAvailable(): bool
    {
        static $available = null;

        if ($available === null) {
            $which = new Process(['which', 'ffprobe']);
            $which->run();
            $available = $which->isSuccessful();
        }

        return $available;
    }

    /**
     * Gera um nome único para o arquivo.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
     */
    private function generateUniqueFileName($file)
    {
        $extension = $file->getClientOriginalExtension();
        $filename = str_replace('.' . $extension, '', $file->getClientOriginalName()); // Nome sem extensão
        $filename .= '_' . md5(time()) . '.' . $extension; // Adiciona um hash único

        return $filename;
    }

    // streaming
    /**
     * Resolve o tipo MIME do arquivo com fallback seguro
     */
    private function resolveMimeType(string $path): string
    {
      $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (!array_key_exists($extension, self::SUPPORTED_MIME_TYPES)) {
            abort(415, 'Tipo de arquivo de vídeo não suportado');
        }

        return self::SUPPORTED_MIME_TYPES[$extension];
    }

/*
 * Prepara os headers HTTP para streaming
 */
private function prepareHeaders(string $mimeType, int $contentLength, string $filePath): array
{
    return [
        'Content-Type' => $mimeType,
        'Content-Length' => $contentLength,
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'private, max-age=604800', // 1 semana
        'Expires' => gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT',
        'Last-Modified' => gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT',
        'X-Content-Type-Options' => 'nosniff',
    ];
}


    /**
     * Leitura do arquivo em chunks a partir de um offset, respeitando o tamanho solicitado.
     */
    private function readFileChunked(string $path, int $start, int $length): void
    {
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            abort(500, 'Não foi possível abrir o arquivo de vídeo');
        }

        fseek($stream, $start);

        $bufferSize = 1024 * 8;
        $bytesSent = 0;

        while (!feof($stream) && $bytesSent < $length && connection_status() === CONNECTION_NORMAL) {
            $chunkSize = min($bufferSize, $length - $bytesSent);
            $chunk = fread($stream, $chunkSize);

            if ($chunk === false) {
                break;
            }

            echo $chunk;
            $bytesSent += strlen($chunk);

            if (ob_get_level() > 0) {
                ob_flush();
            }

            flush();
        }

        fclose($stream);
    }
    private function adjustMemoryLimit(int $fileSize): void
        {
            $memoryLimit = ini_get('memory_limit');

            // "-1" indica limite ilimitado; nada a ajustar.
            if ($memoryLimit === '-1') {
                return;
            }

            if ($fileSize > $this->convertToBytes($memoryLimit) * 0.5) {
                ini_set('memory_limit', (string) ($fileSize * 2));
            }
        }
       private function convertToBytes(string $memoryLimit): int
        {
            $unit = strtolower(substr($memoryLimit, -1));
            $value = (int) substr($memoryLimit, 0, -1);

            return match ($unit) {
                'g' => $value * 1024 * 1024 * 1024,
                'm' => $value * 1024 * 1024,
                'k' => $value * 1024,
                default => (int) $memoryLimit,
            };
        }

}
