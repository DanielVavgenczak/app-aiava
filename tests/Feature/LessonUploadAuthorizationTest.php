<?php

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Teacher\Discipline;
use App\Models\Teacher\Teacher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function makeUploadModule(Teacher $teacher): Module
{
    $discipline = Discipline::factory()->for($teacher)->create();

    return Module::factory()->create([
        'discipline_id' => $discipline->id,
    ]);
}

function uploadPayload(UploadedFile $file): array
{
    return [
        'resumableIdentifier' => Str::random(16),
        'resumableChunkNumber' => 1,
        'resumableTotalChunks' => 1,
        'resumableChunkSize' => 1024 * 1024,
        'resumableTotalSize' => $file->getSize(),
        'resumableFilename' => $file->getClientOriginalName(),
        'resumableRelativePath' => $file->getClientOriginalName(),
        'file' => $file,
    ];
}

function makeVideoUpload(int $seconds): ?UploadedFile
{
    if (! ffprobeAvailable()) {
        return null;
    }

    $path = tempnam(sys_get_temp_dir(), 'video_') . '.mp4';

    $process = new Symfony\Component\Process\Process([
        'ffmpeg', '-y',
        '-f', 'lavfi',
        '-i', 'color=c=black:s=16x16:r=1',
        '-t', (string) $seconds,
        '-pix_fmt', 'yuv420p',
        '-c:v', 'libx264',
        $path,
    ]);
    $process->setTimeout(120);
    $process->run();

    if (! $process->isSuccessful() || ! is_file($path)) {
        return null;
    }

    return new UploadedFile($path, 'video.mp4', 'video/mp4', null, true);
}

function ffprobeAvailable(): bool
{
    static $available = null;

    if ($available === null) {
        $which = new Symfony\Component\Process\Process(['which', 'ffprobe']);
        $which->run();
        $available = $which->isSuccessful();

        if ($available) {
            $ffmpeg = new Symfony\Component\Process\Process(['which', 'ffmpeg']);
            $ffmpeg->run();
            $available = $ffmpeg->isSuccessful();
        }
    }

    return $available;
}

it('impede upload em módulo de outro professor', function () {
    Storage::fake('private');

    $owner = Teacher::factory()->create();
    $module = makeUploadModule($owner);

    $other = Teacher::factory()->create();

    $this->actingAs($other, 'teacher')
        ->post(route('teacher.media.upload', $module->id), [
            'resumableIdentifier' => Str::random(16),
            'resumableChunkNumber' => 1,
            'resumableTotalChunks' => 1,
            'resumableChunkSize' => 1024 * 1024,
            'resumableTotalSize' => 1024,
            'resumableFilename' => 'video.mp4',
            'resumableRelativePath' => 'video.mp4',
        ])
        ->assertNotFound();

    expect(Lesson::where('module_id', $module->id)->count())->toBe(0);
});

it('rejeita vídeo com mais de 12 minutos e não cria bloco', function () {
    Storage::fake('private');

    $file = makeVideoUpload(721);
    if (! $file) {
        $this->markTestSkipped('ffmpeg/ffprobe indisponível.');
    }

    $teacher = Teacher::factory()->create();
    $module = makeUploadModule($teacher);

    $this->actingAs($teacher, 'teacher')
        ->post(route('teacher.media.upload', $module->id), uploadPayload($file))
        ->assertStatus(422);

    expect(Lesson::where('module_id', $module->id)->count())->toBe(0);
});

it('aceita vídeo válido e cria exatamente um bloco', function () {
    Storage::fake('private');

    $file = makeVideoUpload(5);
    if (! $file) {
        $this->markTestSkipped('ffmpeg/ffprobe indisponível.');
    }

    $teacher = Teacher::factory()->create();
    $module = makeUploadModule($teacher);

    $this->actingAs($teacher, 'teacher')
        ->post(route('teacher.media.upload', $module->id), uploadPayload($file))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(Lesson::where('module_id', $module->id)->count())->toBe(1);
});
