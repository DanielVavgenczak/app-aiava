<?php

namespace App\Livewire\Forms;

use App\Models\Discipline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DisciplineForm extends Form
{

    // #[Validate('required', message: 'O titulo do disciplina é obrigatório!')]
    // #[Validate('min:3', message: 'A disciplina deve ter no minimo 3 caracteres')]
    public string $title = '';

    public ?string $disciplineId = null;

    public function disciplineStore()
    {
            $teacher_id = Auth::guard('teacher')->user()->id;
            $this->validate([
              'title' => [
                'required',
                'min:3',
                'max:255',
                function ($attribute, $value, $fail) use ($teacher_id) {
                    $clearValue = Str::lower(trim($value));
                    if (Discipline::teacherHasDiscipline($teacher_id, $value)) {
                        $fail('Você já possui uma disciplina com esse título!');
                    }
                },
              ]
            ]);
         
            $discipline = Discipline::create([
                "teacher_id" => $teacher_id,
                "title" => $this->title,
            ]);
            $this->setDisciplineId($discipline->id);
            return true;
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
