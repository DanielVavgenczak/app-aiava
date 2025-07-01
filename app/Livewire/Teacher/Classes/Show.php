<?php

namespace App\Livewire\Teacher\Classes;

use App\Models\Teacher\Classe;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class Show extends Component
{
    use Toast;
    
    public string $id;
    public Classe $classe;
    public string $search = '';
    
    // Carrega a turma com os alunos filtrados
    public function mount(Classe $classe)
    {
        $this->classe = $classe->load(['students' => function($query) {
            $query->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('full_name', 'like', '%'.$this->search.'%')
                      ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            });
        }]);
    }
    
    // Atualiza a busca quando o termo de pesquisa muda
    public function updatedSearch()
    {
        $this->mount($this->classe);
    }
    
    #[On('student-registed-success')]
    public function refreshClasse()
    {
        $this->toast('success', 'Estudante registrado com sucesso!');
        //$this->mount($this->classe); // Recarrega os alunos
    }
    
    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.pages.teacher.classes.show');
    }
    
    #[On('event-student')]
    public function refresh($type)
    {
        match($type) {
            'updated' => $this->success('Aluno atualizado com sucesso', timeout: 4000), 
            'delete' => $this->error('Aluno deletado com sucesso', timeout: 4000),
            default => null,
        };
        
    //    $this->mount($this->classe); // Recarrega os alunos após ação
    }
}