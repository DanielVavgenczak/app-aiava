<?php

namespace App\Livewire\Teacher\Classes;

use App\Models\Teacher\{Classe, Student};
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class Show extends Component
{
    use Toast;

    public string $id;
    public Classe $classe;
    public $students = [];
    public string $search = '';

    // Carrega a turma com os alunos filtrados
    public function mount(Classe $classe)
    {
        $this->classe = $classe;
        $this->students =$this->loadStudents();

    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.pages.teacher.classes.show');
    }

    public function updatedSearch()
    {
        $this->students = $this->loadStudents();
    }

    protected function loadStudents()
    {

        if(!empty($this->search)) {
            return Student::where('classe_id', $this->classe->id)
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('full_name', 'like', '%'.$this->search.'%')
                      ->orWhere('email', 'like', '%'.$this->search.'%')
                      ->orWhere('phone', 'like', '%'.$this->search.'%');
                });
            })->orderBy('full_name', 'asc')->get();
        }
        return Student::where('classe_id', $this->classe->id)
            ->orderBy('created_at', 'desc')
            ->get();

    }


    #[On('event-student')]
    public function updated($type)
    {
        match($type) {
            'updated', => $this->handlerAfterActionWithToast($type, 'Aluno editado com sucesso!'), //$this->success('Aluno atualizado com sucesso', timeout: 4000),
            'created' => $this->handlerAfterActionWithToast($type, 'Aluno registrado com sucesso!'), //$this->success('Aluno registrado com sucesso', timeout: 4000),
            'delete' => $this->handlerAfterActionWithToast($type, 'Aluno deletado com sucesso!'), //$this->error('Aluno deletado com sucesso', timeout: 4000),
            default => null,
        };
    }

    public function resetSearch()
    {
      $this->search = '';
      $this->students = $this->loadStudents();
    }
    protected function handlerAfterActionWithToast(string $type, string $message)
    {
        $this->students = $this->loadStudents();
        if($type !== 'delete') {
            $this->success($message, timeout: 5000);
            return;
        }
        $this->error($message, timeout: 4000);
        return;
    }
}
