<?php

use Livewire\Component;
use Livewire\Attributes\{On, Validate};
use App\Models\Module;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public bool $open = false;
    public string $id = '';
    public ?Module $module = null;

    public function mount(Module $module)
    {
        $this->fill($module);
        $this->title = $module->title;
    }

    #[On('open-delete-module')]
    public function open($id)
    {
        if ($this->module->id === $id) {
            $this->open = true;
        }
    }

    public function deleteModule()
    {
        $startedTransaction = false;

        try {
            $teacher = auth('teacher')->user();
            if (!$teacher || $this->module->discipline->teacher_id !== $teacher->id) {
                throw new \Exception('Você não tem permissão para deletar esta aula');
            }

            if (!$this->module->exists) {
                throw new \Exception('Módulo inexistente!');
            }

            DB::beginTransaction();
            $startedTransaction = true;
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
            $startedTransaction = false;
            session()->flash('delete-with-files', 'Aula excluída com sucesso!');
            $this->open = false;
            $this->redirect(route('teacher.discipline.show', $discipline), navigate: true);
        } catch (\Exception $e) {
            if ($startedTransaction) {
                DB::rollBack();
            }

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

    /** Events with tour **/
    #[On('tour::close-delete-lesson-modal')]
    public function closeWithTour()
    {
        $this->open = false;
    }
}; ?>
<x-mary-modal wire:model="open" title="Deletar aula">
    <div id="module-delete-lesson-{{ $module->id }}">
    @if ($module)
        <div class="flex flex-col items-center justify-center gap-3" >
            <x-mary-icon name="o-trash" class="text-red-500 w-10 h-10" />
            <p class="text-sm text-red-500 tracking-wide font-normal text-center">
                Tem certeza que deseja deletar a aula "{{ $module->title }}"?
            </p>
            <p class="text-xs text-gray-400 text-center">
                Esta ação também removerá todos os vídeos e atividades vinculados a esta aula.
            </p>
        </div>
        <div class="flex justify-end gap-2 items-center mt-4"
            id="module-delete-actions-{{ $module->id ?? '' }}">
            <button type="button" class="btn btn-sm btn-ghost" wire:click="cancelEdit">
                Cancelar
            </button>
            <button type="button" class="btn btn-sm bg-red-500 text-gray-50 hover:bg-red-600"
                wire:click="deleteModule"
                wire:loading.class="opacity-50" wire:target="deleteModule">
                <span wire:loading.remove wire:target="deleteModule">Deletar</span>
                <span wire:loading wire:target="deleteModule">Deletando...</span>
            </button>
        </div>
        <div wire:loading wire:target="deleteModule">
            <div class="flex flex-col items-center w-full gap-2 p-5 text-center">
                <div class="mb-2">
                    <x-mary-loading class="progress-error w-7 h-7" />
                </div>
                <p class="text-xs tracking-wider text-pink-600">Deletando vídeos e registros...</p>
            </div>
        </div>
    @endif

    </div>
</x-mary-modal>
