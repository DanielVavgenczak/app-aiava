<?php

use Livewire\Component;
use Livewire\Attributes\{Layout, On};
use App\Models\Teacher\{Discipline};
use Mary\Traits\Toast;

new #[Layout('layouts::student')] class extends Component {
    use Toast;

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
    <livewire:global.breadcrumb :items="[
        ['label' => 'Disciplinas', 'icon' => 'o-academic-cap'],
    ]" color="pink" tour-id="discipline-breadcrumb" />
    <div class="p-5 bg-white">
        <div class="w-full py-4" id="discipline-header">
            <h2 class="text-gray-800 tracking-wide text-lg font-semibold">Disciplinas</h2>
            <p class="text-sm text-gray-500">Minhas disciplinas</p>
        </div>
        <div class="mt-4">
            <div id="discipline-card" class="card w-3/12 border-[1px] border-gray-200 shadow-sm  rounded-sm">
                <div class="card-body">
                    <div class="flex flex-col items-center gap-2">
                        <x-mary-icon name="o-academic-cap" class="w-8 h-8 text-blue-400" />
                        <h2 id="discipline-title" class="text-lg font-medium tracking-wide text-gray-600">{{ $discipline->title }}</h2>
                    </div>
                    <div>
                        <ul>
                            <li id="discipline-teacher" class="text-gray-600"><span
                                    class="font-semibold tracking-wide mr-1">Prefessor:</span>{{ $discipline->teacher->fullName() }}
                            </li>
                            <li id="discipline-modules-count" class="text-gray-600">
                                <span
                                  class="font-semibold tracking-wide mr-1">
                                  Aulas:
                                </span>{{ $discipline->modules->count() }}
                            </li>
                        </ul>
                    </div>
                    <div class="mt-6">
                        <a id="discipline-access-btn" class="btn btn-ghost btn-block"
                            href="{{ route('student.lessons', $discipline->id) }}">Acessar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- Tour Modal --}}
    <div class="fixed inset-0 z-40 bg-gray-900/50" x-data x-show="$store.tourguide.openMai">
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2
            -translate-y-1/2 w-full max-w-4xl sm:w-2/3 bg-white rounded-lg
            shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
           <div class="flex justify-between items-center">
               <h2 class="text-lg font-normal text-gray-700">Opções da Disciplina</h2>
                <div class="flex justify-end">
                    <x-mary-button
                        icon="o-x-mark"
                        class="bg-white border-none btn-square hover:none  hover:text-blue-400"
                        x-on:click="$store.tourguide.cancelOptionsTourPage()" />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <x-mary-button
                    class="font-medium text-blue-400 border-[1px] border-blue-400 btn hover:bg-blue-400 hover:text-gray-50"
                    label="Acessar sua disciplina"
                    x-on:click="$store.tourguide.start('StartTourStudentDiscipline')" />
            </div>
        </div>
    </div>
</div>

