<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;

class LessonUploadController extends Controller
{
    // Diretório de armazenamento de vídeos
    private const VIDEO_STORAGE_PATH = 'videos/lesson';

    /**
     * Stream do vídeo.
     *
     * @param int $lesson_id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function stream($lesson_id)
    {
        $lesson = Lesson::findOrFail($lesson_id);

        $headers = [
            'Cache-Control' => 'no-cache, must-revalidate',
        ];

        return response()->file(storage_path('app/private/' . $lesson->path_file), $headers);
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
        // Validação básica do módulo
        // Verifica se o módulo pertence a uma disciplina do usuário
        if (!Auth::user()->disciplines()->whereHas('modules', function ($query) use ($module_id) {
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

            // Salva o arquivo no armazenamento
            $path = Storage::disk(config('filesystems.default'))->putFileAs(
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
                "path_file" => self::VIDEO_STORAGE_PATH . '/' . $fileName,
                "title" => $title,
                "description" => "Descrição teste para o vídeo $fileName",
                "module_id" => $module_id,
                "user_id" => Auth::id(),
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
}
