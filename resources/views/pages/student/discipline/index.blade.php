<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Teacher\{Discipline};

new #[Layout('layouts::student')] class extends Component {
    public $teacher_id;
    public Discipline $discipline;

    public function mount()
    {
        $this->getDiscipline();
    }
    protected function getDiscipline()
    {
        $this->discipline = Discipline::with([
            'teacher' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'email'); // Apenas colunas necessárias
            },
            'modules' => function ($q) {
                $q->orderBy('created_at', 'ASC'); // Ordem crescente
            },
        ])
            ->where('id', Auth::guard('student')->user()->discipline_id)
            ->first(); // Especificar colunas

        return $this->discipline;
    }
}; ?>

<div>
    <header class="py-4">
        <a href="#">
            <x-mary-icon name="o-arrow-left" class="w-4" />
            <span>voltar</span>
        </a>
    </header>
    <div class="p-5 bg-white">
        <div class="w-full py-4">
            <h2 class="text-gray-800 tracking-wide text-lg font-semibold">Disciplinas</h2>
            <p class="text-sm text-gray-500">Minhas disciplinas</p>
        </div>
        <div class="mt-4">
            <div class="card w-3/12 border-[1px] border-gray-200 shadow-sm  rounded-sm">
                <div class="card-body">
                    <div class="flex flex-col items-center gap-2">
                        <x-mary-icon name="o-academic-cap" class="w-8 h-8 text-blue-400" />
                        <h2 class="text-lg font-medium tracking-wide text-gray-600">{{ $discipline->title }}</h2>
                    </div>
                    <div>
                        <ul>
                            <li class="text-gray-600"><span
                                    class="font-semibold tracking-wide mr-1">Prefessor:</span>{{ $discipline->teacher->fullName() }}
                            </li>
                            <li class="text-gray-600">
                                <span
                                  class="font-semibold tracking-wide mr-1">
                                  Aulas:
                                </span>{{ $discipline->modules->count() }}
                            </li>
                        </ul>
                    </div>
                    <div class="mt-6">
                        <a class="btn btn-ghost btn-block"
                            href="{{ route('student.lessons', $discipline->id) }}">Acessar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
