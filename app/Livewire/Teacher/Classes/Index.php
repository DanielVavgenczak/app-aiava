<?php

namespace App\Livewire\Teacher\Classes;

use App\Models\Teacher\Classe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';
    public $title = "Ola seu bosta";
    public Collection $classes;

    public function mount(){
        $this->loadClasses();
    }
    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.pages.teacher.classes.index');
    }

    protected function loadClasses()
    {
        $this->classes = Classe::where('teacher_id', Auth::user()->id)->latest()->get();

    }
}
