<?php

namespace App\Livewire\Teacher\Classes;

use App\Models\Teacher\Classe;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Show extends Component
{
    public string $id;
    public Classe $classe;

    public function mount(Classe $classe)
    {
        $this->classe = $classe;
        //$this->getClasse($id);
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
