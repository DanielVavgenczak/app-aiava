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

class LessonUploadController extends Controller
{
    // Diretório de armazenamento de vídeos
    private const VIDEO_STORAGE_PATH = 'videos/lessons';
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
    public function stream($lesson_id)
    {
            $lesson = Lesson::findOrFail($lesson_id);

            if (!Storage::disk('private')->exists($lesson->path_file)) {
                abort(404, message: 'Arquivo não encontrado');
            }

            $path = Storage::disk('private')->path($lesson->path_file);
            $fileSize = filesize($path);
            $mimeType = $this->resolveMimeType($path);

            $this->adjustMemoryLimit($fileSize);

            return response()->stream(function () use ($path, $fileSize) {
                $this->readFileChunked($path, $fileSize);
            }, 200, $this->prepareHeaders($mimeType, $fileSize, $path));
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
private function prepareHeaders(string $mimeType, int $fileSize, string $filePath): array
{
    return [
        'Content-Type' => $mimeType,
        'Content-Length' => $fileSize,
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'private, max-age=604800', // 1 semana
        'Expires' => gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT',
        'Last-Modified' => gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT',
        'X-Content-Type-Options' => 'nosniff',
    ];
}


    /**
     * Leitura otimizada do arquivo em chunks com suporte a byte-range
     */
    private function readFileChunked(string $path, int $fileSize): void
    {
          $stream = fopen($path, 'rb');
        if ($stream === false) {
            abort(500, 'Não foi possível abrir o arquivo de vídeo');
        }

        $start = 0;
        $end = $fileSize - 1;
        $length = $fileSize;

        if (isset($_SERVER['HTTP_RANGE'])) {
            if (!preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches)) {
                header("HTTP/1.1 416 Range Not Satisfiable");
                header("Content-Range: bytes */$fileSize");
                exit;
            }

            $start = (int)($matches[1] ?? 0);
            $end = isset($matches[2]) ? (int)$matches[2] : $fileSize - 1;
            $length = $end - $start + 1;

            header("HTTP/1.1 206 Partial Content");
            header("Content-Range: bytes $start-$end/$fileSize");
        }

        fseek($stream, $start);

        $bufferSize = 1024 * 8;
        $bytesSent = 0;

        while (!feof($stream) && $bytesSent < $length && connection_status() == 0) {
            $remaining = $length - $bytesSent;
            $chunkSize = min($bufferSize, $remaining);

            echo fread($stream, $chunkSize);
            $bytesSent += $chunkSize;

            if (ob_get_level() > 0) ob_flush();
            flush();
        }

        if (is_resource($stream)) fclose($stream);
    }
    private function adjustMemoryLimit(int $fileSize): void
        {
            $memoryLimit = ini_get('memory_limit');
            if ($fileSize > $this->convertToBytes($memoryLimit) * 0.5) {
                ini_set('memory_limit', $fileSize * 2 . 'B');
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
