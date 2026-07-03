<?php

use Livewire\Component;
use App\Models\Lesson;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public string $module_id;
    public string $link = '';
    public bool $saved = false;

    public function mount(string $module_id)
    {
        $this->module_id = $module_id;
    }

    public function save()
    {
        $this->validate([
            'link' => 'required|max:2048',
        ]);

        try {
            $teacherId = Auth::guard('teacher')->user()->id;

            $link = $this->link;
            if (!preg_match('#^https?://#i', $link)) {
                $link = 'https://' . $link;
            }

            $nextNumber = $this->getNextBlockNumber();
            $title = sprintf('Bloco %02d', $nextNumber);

            $lesson = Lesson::create([
                'module_id' => $this->module_id,
                'teacher_id' => $teacherId,
                'title' => $title,
                'link' => $link,
                'type' => 'link',
            ]);

            $this->saved = true;
            $this->link = '';

            $this->dispatch('open-new-block-to-module', open: false);

            $this->dispatch('upload-video-complete',
                type: 'uploaded',
                msg: 'Link adicionado com sucesso',
                lesson_id: $lesson->id,
                module_id: $this->module_id
            );
        } catch (\Exception $e) {
            $this->saved = false;
            session()->flash('error', 'Erro ao salvar link: ' . $e->getMessage());
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
};
?>

<div class="max-w-3xl p-3 mx-auto" :key="'upload-content-link'.$module_id">
    <div class="space-y-6">
        @if (session('error'))
            <div class="p-4 border border-red-200 rounded-lg bg-red-50">
                <p class="text-sm font-medium text-red-600">{{ session('error') }}</p>
            </div>
        @endif

        @if ($saved)
            <div id="link-success-{{ $module_id }}" class="flex items-center gap-3 p-4 border border-green-200 rounded-lg bg-green-50">
                <x-mary-icon name="o-check-circle" class="w-5 h-5 text-green-600" />
                <p class="text-sm font-medium text-green-600">Link adicionado com sucesso!</p>
            </div>
        @endif

        <div class="flex flex-col gap-4 p-6 bg-white rounded-xl border-2 border-dashed border-gray-300">
            <div class="flex items-center gap-3">
                <x-mary-icon name="o-link" class="w-8 h-8 text-blue-700" />
                <span class="text-sm font-medium tracking-wide text-blue-500">Adicione um link externo para a aula</span>
            </div>

            <div id="link-input-group-{{ $module_id }}" class="flex gap-3">
                <div class="flex-1">
                    <x-mary-input
                        type="url"
                        placeholder="https://exemplo.com/recurso"
                        wire:model="link"
                        class="w-full"
                    />
                </div>
                <button
                    id="link-save-btn-{{ $module_id }}"
                    wire:click="save"
                    wire:loading.attr="disabled"
                    class="px-6 py-2 text-white transition duration-200 bg-blue-400 rounded-lg hover:bg-blue-500 disabled:opacity-50"
                >
                    <span wire:loading.remove>Salvar</span>
                    <span wire:loading>Salvando...</span>
                </button>
            </div>

            @error('link')
                <p class="text-sm font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
