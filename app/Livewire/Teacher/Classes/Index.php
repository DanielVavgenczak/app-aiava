<?php

namespace App\Livewire\Teacher\Classes;

use App\Models\Discipline;
use App\Models\Teacher\{Classe};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Livewire\Attributes\Layout;
use Livewire\Attributes\{On, Lazy};
use Livewire\{Component, WithPagination};
use Mary\Traits\Toast;

class Index extends Component
{
    use Toast, WithPagination;
    public string $search = '';
    public bool $loading = false;
    public string $disciplineId = '';
    public Collection $classes;
    public Collection $disciplines;
    public ?Discipline $disciplineSelected = null;

    public function mount()
    {
        $this->loadClasses();
        $this->getDisciplines();
        sleep(1);
    }
    public function updating($property, $value)
    {
        if (!empty($value)) {
            sleep(2);
        }
    }
    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.pages.teacher.classes.index');
    }

    public function updated()
    {
        $this->loadClasses();
    }

    private function getDisciplines()
    {
        $this->disciplines = Discipline::where('user_id', Auth::user()->id)
            ->get();
    }
    private function loadClasses()
    {
        $query = Classe::with('students')
            ->where('teacher_id', Auth::user()->id);

        if (!empty($this->disciplineId)) {
            $this->loading = true;
            $this->disciplineSelected = Discipline::find($this->disciplineId);
            $query->where('discipline_id', $this->disciplineId);
        }
        $this->classes = $query->latest()->get();
        $this->loading = false;
    }

    #[On('action-event-classe')]
    public function refresh($type)
    {
        match($type){
            'delete' => $this->handlerAfterActionWithToast($type, 'Classe deletada com sucesso!'),
            'create' => $this->handlerAfterActionWithToast($type,'Classe criada com sucesso!'),
            'update' => $this->handlerAfterActionWithToast($type,'Classe editada com sucesso!'),
            default => $this->loadClasses(),
        };
    }

    protected function handlerAfterActionWithToast(string $type, string $message)
    {
        if($type !== 'delete') {
            $this->success($message, timeout: 4000);
            $this->loadClasses();
            return;
        }
        $this->error($message, timeout: 4000);
        return;
    }

}
