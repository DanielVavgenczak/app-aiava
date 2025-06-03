<?php

namespace App\Livewire\Teacher\Classes;

use App\Models\Teacher\Classe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class Index extends Component
{
    use Toast;
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
        $this->classes = Classe::with('students')
            ->where('teacher_id', Auth::user()->id)
            ->latest()
            ->get();
    }

    #[On('student-created-success')]
    public function refresh(){

    }
}
