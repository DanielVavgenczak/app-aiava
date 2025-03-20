<?php

namespace App\Livewire\Forms;

use App\Models\Discipline;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DisciplineForm extends Form
{
    #[Validate(rule: 'required|min:3')]
    public string $title = '';

    // TODO: Mudar estrutura na tabela do banco
    #[Validate(rule: 'required')]
    public string $description = '';

    public function disciplineStore()
    {
       $this->validate();
        Discipline::create([
            "user_id" => Auth::user()->id,
            "title" => $this->title,
            "description" => $this->description
        ]);
   }

    public function resetForm()
    {
        $this->title = '';
        $this->description = '';
    }

}
