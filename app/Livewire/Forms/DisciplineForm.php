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

    public ?string $disciplineId = null;

    public function disciplineStore()
    {
       $this->validate();
        $discipline = Discipline::create([
            "user_id" => Auth::user()->id,
            "title" => $this->title,
        ]);
        $this->setDisciplineId($discipline->id);
   }

   private function setDisciplineId(string $id)
   {
       $this->disciplineId = $id;
   }
   public function getDisciplineId()
   {
    return $this->disciplineId ?? null;
   }
    public function resetForm()
    {
        $this->title = '';
    }

}
