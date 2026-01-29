<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{On, Validate};
use App\Models\Module;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public bool $open = false;
    public string $id = '';
    public ?Module $module = null;

    #[On('open-delete-module')]
    public function openModal($id)
    {
        if ($this->id === $id) {
            $this->open = true;
            $this->module = Module::findOrFail($id);
        }
    }

    public function deleteModule()
    {
        try {
            if (!$this->module->exists) {
                throw new \Exception('Módulo inexistente!');
            }

            DB::beginTransaction();
            $lessons = $this->module->lessons;
            if ($lessons->isNotEmpty()) {
                $deletou = $this->deleteVideoBlockFile($lessons);
                if (!$deletou) {
                    throw new \Exception('Erro ao deletar os arquivos das aulas');
                }
                $lessons->each->delete();
                $this->module->unsetRelation('lessons');
            }

            if ($this->module->activitTasks()->exists()) {
                $this->module->activitTasks()->delete();
            }
            $this->module->unsetRelation('activitTasks');
            $discipline = $this->module->discipline;
            if (!$this->module->delete()) {
                throw new \Exception('Falha ao deletar o módulo');
            }

            DB::commit();
            session()->flash('delete-with-files', 'Aula excluída com sucesso!');
            $this->open = false;
            return $this->redirect(route('teacher.discipline.show', $discipline), navigate: true);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->handleDeleteError($e);
        }
    }

    public function cancelEdit()
    {
        $this->open = false;
    }

    protected function deleteVideoBlockFile($lessons): bool
    {
        $allDeleted = true;
        $lessons->each(function ($lesson) use (&$allDeleted) {
            try {
                if ($lesson->path_file) {
                    $path = $lesson->path_file;
                    if (Storage::disk('private')->exists($path)) {
                        if (!Storage::disk('private')->delete($path)) {
                            \Log::warning("Falha ao deletar o arquivo: {$path}");
                            $allDeleted = false;
                        }
                    } else {
                        \Log::info("Arquivo não encontrado para exclusão: {$path}");
                        // File not existing isn't necessarily an error
                    }
                }
            } catch (\Throwable $e) {
                \Log::error("Erro ao deletar vídeo da aula [ID {$lesson->id}]: " . $e->getMessage());
                $allDeleted = false;
            }
        });
        return $allDeleted;
    }

    protected function handleDeleteError(\Exception $e)
    {
        $msg = $e->getMessage() ?: 'Erro ao deletar aula';
        $this->error($msg, timeout: 1000, position: 'toast-bottom toast-start');
    }
}; ?>
<x-mary-modal wire:model="open">
    @if ($module)
        <div class="flex flex-col items-center justify-center gap-3" wire:loading.remove>
            <x-mary-icon name="o-trash" class="text-red-500 w-10 h-10" />
            <p class="text-sm text-red-500 tracking-wide font-normal">
                Deletar a aula: {{ $module->title ?? 'Aula não disponível' }}
            </p>
        </div>
        {{-- Modal: botões de ação --}}
        <div class="flex justify-end gap-2 items-center mt-3.5" id="module-delete-actions-{{ $module->id ?? '' }}"
            wire:loading.remove>
            <button type="button" class="btn btn-sm btn-ghost" wire:click="cancelEdit">
                Cancelar
            </button>

            <button type="button" class="btn btn-sm bg-red-500 text-gray-50" wire:click="deleteModule"
                wire:loading.remove>
                Deletar
            </button>
        </div>
    @endif
    {{-- Modal: estado de carregamento ao deletar --}}
    <div class="flex flex-col items-center w-full gap-2 p-5 text-center" wire:loading wire:target="deleteModule">
        <div class="mb-2">
            <x-mary-loading class="progress-error w-7 h-7" />
        </div>
        <p class="text-xs tracking-wider text-pink-600">Deletando</p>
    </div>
</x-mary-modal>
