<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public string $module_id;
    public $pdfFile = null;
    public bool $uploaded = false;

    public function mount(string $module_id)
    {
        $this->module_id = $module_id;
    }

    public function updatedPdfFile()
    {
        $this->validate([
            'pdfFile' => 'required|file|mimes:pdf|max:102400',
        ]);

        $teacher = Auth::guard('teacher')->user();

        $module = Module::whereKey($this->module_id)
            ->whereHas('discipline', fn ($q) => $q->where('teacher_id', $teacher?->id))
            ->first();

        if (! $module) {
            abort(404);
        }

        try {
            $file = $this->pdfFile;
            $teacherId = $teacher->id;

            $nextNumber = $this->getNextBlockNumber();
            $title = sprintf('Bloco %02d', $nextNumber);

            $originalName = $file->getClientOriginalName();
            $hashName = pathinfo($originalName, PATHINFO_FILENAME) . '_' . md5(uniqid());
            $fileName = $hashName . '.' . $file->getClientOriginalExtension();

            $path = Storage::disk('private')->putFileAs('pdfs/lessons', $file, $fileName);

            $lesson = Lesson::create([
                'original_name' => $originalName,
                'hash_name' => $hashName,
                'path_file' => $path,
                'title' => $title,
                'description' => '',
                'module_id' => $module->id,
                'teacher_id' => $teacherId,
                'type' => 'text',
            ]);

            $this->uploaded = true;
            $this->pdfFile = null;

            $this->dispatch('open-new-block-to-module', open: false);

            $this->dispatch('upload-video-complete',
                type: 'uploaded',
                msg: 'Bloco de texto adicionado com sucesso',
                lesson_id: $lesson->id,
                module_id: $this->module_id
            );
        } catch (\Exception $e) {
            $this->uploaded = false;
            $this->pdfFile = null;
            session()->flash('error', 'Erro ao fazer upload do PDF: ' . $e->getMessage());
        }
    }

    private function getNextBlockNumber(): int
    {
        $lastBlock = Lesson::where('module_id', $this->module_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastBlock || !$lastBlock->title) {
            return 1;
        }

        $number = (int) preg_replace('/\D/', '', $lastBlock->title);
        return $number ? $number + 1 : 1;
    }
}; ?>
<div class="max-w-3xl p-3 mx-auto" :key="'upload-content-pdf'.$module_id">
    <div class="space-y-6">
        @if (session('error'))
            <div class="p-4 border border-red-200 rounded-lg bg-red-50">
                <p class="text-sm font-medium text-red-600">{{ session('error') }}</p>
            </div>
        @endif

        @if ($uploaded)
            <div id="pdf-success-{{ $module_id }}" class="flex items-center gap-3 p-4 border border-green-200 rounded-lg bg-green-50">
                <x-mary-icon name="o-check-circle" class="w-5 h-5 text-green-600" />
                <p class="text-sm font-medium text-green-600">PDF adicionado com sucesso!</p>
            </div>
        @endif

        <div
            x-data="{ uploading: false, progress: 0 }"
            x-on:livewire-upload-start="uploading = true"
            x-on:livewire-upload-finish="uploading = false"
            x-on:livewire-upload-error="uploading = false"
            x-on:livewire-upload-progress="progress = $event.detail.progress"
        >
            <div
                id="pdf-upload-area-{{ $module_id }}"
                x-on:click="$el.querySelector('input[type=file]').click()"
                x-show="!uploading && !$wire.uploaded"
                class="flex flex-col items-center justify-center gap-4 px-5 py-5 transition-all duration-300 bg-white cursor-pointer rounded-xl hover:bg-indigo-50 hover:border-pink-400 border-2 border-dashed border-gray-300"
            >
                <x-mary-icon name="o-document-text" class="w-10 h-10 text-blue-700" />
                <span class="text-sm font-medium tracking-wide text-blue-500">Clique para adicionar um PDF</span>
                <span class="text-xs text-red-500">Apenas formato PDF (máximo 100MB)</span>
                <input
                    type="file"
                    accept=".pdf,application/pdf"
                    wire:model.live="pdfFile"
                    class="hidden"
                />
            </div>

            <div id="pdf-progress-{{ $module_id }}" x-show="uploading" class="w-full px-10 py-8 bg-white border-2 border-gray-300 rounded-xl">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Enviando PDF...</span>
                    <span class="text-sm font-medium text-blue-400" x-text="progress + '%'"></span>
                </div>
                <div class="w-full h-3 overflow-hidden bg-gray-100 rounded-full">
                    <div class="h-full transition-all duration-300 ease-out bg-blue-400 rounded-full"
                         x-bind:style="'width: ' + progress + '%'"></div>
                </div>
            </div>
        </div>

        @error('pdfFile')
            <div class="p-4 border border-red-200 rounded-lg bg-red-50">
                <p class="text-sm font-medium text-red-600">{{ $message }}</p>
            </div>
        @enderror
    </div>
</div>
