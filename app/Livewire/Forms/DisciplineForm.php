<?php

namespace App\Livewire\Forms;

use App\Models\Discipline;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DisciplineForm extends Form
{

    #[Validate('required', message: 'O titulo do disciplina é obrigatório!')]
    #[Validate('min:3', message: 'A disciplina deve ter no minimo 3 caracteres')]
    public string $title = '';

    public ?string $disciplineId = null;

    public function disciplineStore()
    {
            $this->validate();
            $teacher_id = Auth::guard('teacher')->user()->id;
            $discipline = Discipline::create([
                "teacher_id" => $teacher_id,
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
