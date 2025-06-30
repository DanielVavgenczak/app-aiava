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

    public function mount(Classe $classe)
    {
        $this->classe = $classe;
    }

    #[On('student-registed-success')]
    public function refreshClasse(){

        $this->toast('success', 'Estudante resgistrado com sucesso!');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.pages.teacher.classes.show');
    }
    #[On('event-student')]
    public function refresh($type)
    {
        match($type){
            'updated' => $this->success('Aluno atulizado com sucesso', timeout: 4000), 
            'delete' => $this->error('Aluno deletado com sucesso', timeout: 4000),
            default => null,
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
    private function getClasse(string $id)
    {
        $this->classe = Classe::with('students')->find($id);
    }
}
