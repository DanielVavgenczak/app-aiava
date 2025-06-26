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

    private function getClasse(string $id)
    {
        $this->classe = Classe::with('students')->find($id);
    }
}
