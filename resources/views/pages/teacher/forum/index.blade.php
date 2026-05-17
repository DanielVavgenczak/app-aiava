<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Teacher\{Discipline};

new #[Layout('layouts::teacher')] class extends Component {
  public $disciplines;
  public string $search = '';

  public function mount()
  {
      $this->disciplines = $this->getDisciplines();
  }

  private function getDisciplines(){
       $teacher_id = Auth::guard('teacher')->user()->id;
        return Discipline::where('teacher_id', $teacher_id)
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%');
            })
            ->withCount('ForumTopics')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}; ?>

<div>
      <div class="py-4" id="forum-breadcrumb">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('teacher.discipline') }}" wire:navigate class="text-gray-500 hover:text-pink-700 transition-colors">
                <x-mary-icon name="o-home" class="w-4 h-4" />
            </a>
            <x-mary-icon name="o-chevron-right" class="w-3 h-3 text-gray-400" />
            <a href="{{ route('teacher.forum') }}" wire:navigate class="text-gray-500 hover:text-pink-700">
                Fórum
            </a>
        </div>
    </div>

    {{-- Card de Postagens no Fórum --}}
<div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
    {{-- Header do Card --}}
    <div class="px-6 py-5" id="forum-header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <x-mary-icon name="o-chat-bubble-left-right" class="w-6 h-6 text-gray-900" />
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Postagens no Fórum</h2>
                    <p class="text-gray-600 text-sm">Acompanhe as discussões das suas turmas</p>
                </div>
            </div>

            {{-- Badge de Total --}}
            <div class="bg-gray-200 bg-opacity-20 backdrop-blur-sm px-4 py-2 rounded-full">
                <span class="text-blue-900 font-semibold text-sm">
                    {{ $disciplines->count() }}
                    {{ Str::plural('disciplina', $disciplines->count()) }}
                </span>
            </div>
        </div>
    </div>

    {{-- Conteúdo --}}
    <div class="p-6">
        @if($disciplines->count() > 0)
            <div id="forum-discipline-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                @foreach ($disciplines as $discipline)
                    <div
                        @if($loop->first) id="forum-discipline-card" @endif
                        class="group relative bg-gradient-to-br from-gray-50 to-white border-2 border-gray-200 rounded-xl p-4 hover:shadow-xl hover:border-blue-400 hover:-translate-y-1 transition-all duration-300"
                        wire:key="forum-discipline-{{ $discipline->id }}"
                    >

                        {{-- Ícone --}}
                        <div class="flex justify-center mb-3">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-700 to-blue-500 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
                                <x-mary-icon name="o-academic-cap" class="w-7 h-7 text-white" />
                            </div>
                        </div>

                        {{-- Título --}}
                        <h3 class="text-center text-sm font-bold text-gray-900 mb-2 line-clamp-2 min-h-[2.5rem] group-hover:text-blue-700 transition-colors">
                            {{ $discipline->title }}
                        </h3>

                        {{-- Info de Tópicos --}}
                        <div class="flex items-center justify-center gap-1 text-xs text-gray-600 mb-3">
                            <x-mary-icon name="o-chat-bubble-left-ellipsis" class="w-3 h-3" />
                            <span>
                                {{ $discipline->forum_topics_count }}
                                {{ Str::plural('tópico', $discipline->forum_topics_count) }}
                            </span>
                        </div>

                        {{-- Botão de Acesso --}}
                        <a
                            @if($loop->first) id="forum-access-btn" @endif
                            href="{{ route('teacher.forum.show', $discipline->id) }}"
                            wire:navigate
                            class="btn btn-sm btn-block bg-gradient-to-r from-blue-700 to-blue-500 text-white border-0 hover:from-blue-700 hover:to-blue-500 group-hover:shadow-lg transition-all"
                        >
                            <x-mary-icon name="o-arrow-right-circle" class="w-4 h-4" />
                            Acessar
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Estado Vazio --}}
            <div id="forum-empty-state" class="text-center py-12">
                <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <x-mary-icon name="o-inbox" class="w-10 h-10 text-gray-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma disciplina encontrada</h3>
                <p class="text-gray-500">
                    Você ainda não possui disciplinas cadastradas para criar postagens no fórum. Adicione disciplinas para começar a interagir com seus alunos!
                </p>
                <a
                    href="{{ route('teacher.discipline') }}"
                    wire:navigate
                    class="btn bt-md bg-blue-700 mt-4 text-white border-0 hover:bg-blue-500 transition-colors"
                >
                    <x-mary-icon name="o-plus-circle" class="w-5 h-5" />
                    Adicionar Disciplina
                </a>
            </div>
        @endif
    </div>
</div>

    {{-- Tour Modal --}}
    <div class="fixed inset-0 z-40 bg-gray-900/50" x-data x-show="$store.tourguide.openMai">
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2
            -translate-y-1/2 w-full max-w-4xl sm:w-2/3 bg-white rounded-lg
            shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
           <div class="flex justify-between items-center">
               <h2 class="text-lg font-normal text-gray-700">Opções do Fórum</h2>
                <div class="flex justify-end">
                    <x-mary-button icon="o-x-mark"
                        class="bg-white border-none btn-square hover:none hover:bg-pink-600 hover:text-gray-100"
                        x-on:click="$store.tourguide.cancelOptionsTourPage()" />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <x-mary-button
                    class="font-medium text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-600 hover:text-gray-100"
                    label="Visão geral do Fórum"
                    x-on:click="$store.tourguide.start('StartTourTeacherForum')" />
              <x-mary-button
                    class="font-medium text-blue-600 border-[1px] border-blue-600 btn hover:bg-blue-600 hover:text-gray-100"
                    label="Interagir"
                    x-on:click="$store.tourguide.start('StartTourTeacherForumInteract')" />
            </div>
        </div>
        {{-- <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48" /> --}}
    </div>

</div>
