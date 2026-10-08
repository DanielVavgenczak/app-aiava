<?php

namespace App\Livewire\Forms;

use App\Models\Teacher\Discipline;
use Closure;
use Illuminate\Support\Facades\Auth;
use Livewire\Form;

class DisciplineForm extends Form
{
    public string $title = '';

    public ?string $disciplineId = null;

    /**
     * Regras compartilhadas entre store e update.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'min:3',
                'max:255',
                $this->uniqueTitleForTeacher(),
            ],
        ];
    }

    private function uniqueTitleForTeacher(): Closure
    {
        $teacherId = Auth::guard('teacher')->id();
        $ignoreId = $this->disciplineId;

        return function ($attribute, $value, $fail) use ($teacherId, $ignoreId) {
            if (! $teacherId) {
                $fail('Sessão inválida, faça login novamente.');
                return;
            }

            $duplicada = Discipline::withTrashed()
                ->forTeacher($teacherId)
                ->normalized($value)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->first();

            if ($duplicada === null) {
                return;
            }

            $fail($duplicada->trashed()
                ? 'Você já possui uma disciplina com esse título (excluída)! Restoure-a ou escolha outro título.'
                : 'Você já possui uma disciplina com esse título!');
        };
    }

    public function disciplineStore(): bool
    {
        $this->validate($this->rules());

        $discipline = Discipline::create([
            'teacher_id' => Auth::guard('teacher')->id(),
            'title' => $this->title,
        ]);

        $this->setDisciplineId($discipline->id);

        return true;
    }

    public function disciplineUpdate(): bool
    {
        $discipline = $this->findDiscipline();

        if ($discipline === null) {
            return false;
        }

        $this->validate($this->rules());

        $discipline->update([
            'title' => $this->title,
        ]);

        return true;
    }

    private function findDiscipline(): ?Discipline
    {
        if ($this->disciplineId === null || $this->disciplineId === '') {
            return null;
        }

        return Discipline::withTrashed()->find($this->disciplineId);
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
        $this->disciplineId = null;
    }
}
