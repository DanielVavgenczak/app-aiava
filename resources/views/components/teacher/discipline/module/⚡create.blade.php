<?php

use Livewire\Component;
use Livewire\Attributes\{On, Validate};
use App\Models\{Module};
use App\Models\Teacher\Discipline;

new class extends Component {
    public bool $open = false;

    #[Validate('required', message: 'O título do aula é obrigatório!')]
    public string $title = '';
    public string $disciplineId;
    public string $module_id;

    #[On('teacher::create-new-lesson')]
    public function open(string $disciplineId): void
    {
        $this->open = true;
        $this->disciplineId = $disciplineId;

    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|min:3',
        ]);

        $teacherId = auth('teacher')->id();

        $discipline = Discipline::forTeacher($teacherId)->find($this->disciplineId);

        if (! $discipline) {
            abort(404);
        }

         $exists = Module::where('discipline_id', $discipline->id)
                ->whereNormalizedTitle($this->title)
                ->exists();

        if ($exists) {
            $this->addError('title', 'Já existe uma aula com este nome nesta disciplina.');
            return;
        }
       Module::create([
            'title' => $this->title,
            'discipline_id' => $discipline->id,
        ]);
        $this->reset('open', 'title');
        $this->dispatch('teacher::module-event', type: 'create', msg: 'Aula criada com sucesso!');
        $this->open = false;
        //$this->redirect(route('teacher.discipline.show', $this->disciplineId), navigate: true);
    }


    #[On('tour-teacher::close-modal-new-lesson')]
    public function close($open)
    {
        $this->open = $open;
        $this->title = '';
    }
    public function cancel()
    {
        $this->reset('title', 'open');
    }

    /** events with tour **/
    #[On('tour::close-opened-modals')]
    public function closeTourActived()
    {
        $this->open = false;
        $this->js("console.log('enviando pelo alpinejs')");
    }
}; ?>
<x-mary-modal wire:model="open" title="Nova aula" subtitle="Crie uma nova aula"
    data-tour="new-lesson-{{ $disciplineId }}">
    <div>
        <form wire:submit.prevent="save" id="new-lesson-and-block">
            <div class="my-3" id="lesson-title">
              <input
                type="text"
                class="input w-full !outline-none lesson-input-title focus:border-gray-400"
                wire:model="title"
                placeholder="Título da aula"/>
              @error('title')
                <div id="title_module_error">
                  <span class="text-xs text-pink-700">{{ $message }}</span>
                </div>
              @enderror
            </div>
            <div class="flex items-center justify-end gap-4 mt-5" id="lesson-actions-buttons">
                <button type="button" wire:click="cancel" id="module-input-cancel"
                    class="btn btn-sm bg-ghost">Cancelar</button>
                <button type="submit" spinner id="module-input-create"
                    class="btn btn-sm text-gray-50 bg-blue-400 hover:bg-blue-500">Registrar
                </button>
            </div>
        </form>
    </div>
</x-mary-modal>
