 <div>
     <div class="py-4">
         <a href="{{ route('teacher.discipline') }}" wire:navigate>
             <x-mary-icon name="o-arrow-left" class="w-4" />
             <span class="text-xs font-semibold text-gray-600 uppercase tracking-wider">voltar</span>
         </a>
     </div>
     <div class="p-5 bg-white">
         <div class="mb-5">
             <h2 class="text-lg font-semibold text-gray-600">Minhas turmas</h2>
         </div>
         <div class="flex items-center justify-between">
             <div class="mb-4">
                 <x-mary-select label="Disciplina  (Selecine a disciplina)" icon="o-academic-cap"
                     wire:model.change="disciplineId" placeholder="Todas as turmas" :options="$disciplines"
                     option-label="title" option-value="id" placeholder-value="" class="w-full !outline-none" />
             </div>
             <button class="btn btn-sm bg-pink-600 text-gray-50 discipline-new flex gap-1.5 items-center"
                 wire:target="disciplineId" wire:loading.attr="disabled" id="discipline-create"
                 x-on:click="$dispatch('open-form-class-create',{open:true, disciplineId: '{{ $this->disciplineId }}'})"
                 mary:key="discipline-create-button">
                 <x-mary-icon name="o-plus" class="w-5 h-5" /> <span
                     class="text-sm tracking-wide font-medium capitalize">turma</span>
             </button>
             <!-- modal to register class and alunos -->
             <livewire:pages.teacher.classes.modal.create />

         </div>
         <div class="flex flex-col gap-2 relative top-2.5">
             @if (count($classes) > 0)
                 <!-- Cabeçalho da "tabela" -->
                 <div class="grid grid-cols-12 gap-4 p-3 bg-gray-100 rounded-t-lg">
                     <div class="col-span-4 md:col-span-3">
                         <span class="text-xs uppercase tracking-wider text-gray-800 font-medium">
                             Nome
                         </span>
                     </div>
                     <div class="col-span-3 md:col-span-2 text-center">
                         <span class="text-xs uppercase tracking-wider text-gray-800 font-medium">
                             Alunos
                         </span>
                     </div>
                     <div class="col-span-3 md:col-span-4">
                         <span class="text-xs uppercase tracking-wider text-gray-800 font-medium">
                             Disciplina
                         </span>
                     </div>
                     <div class="col-span-2 md:col-span-3 text-right">
                         <span class="text-xs uppercase tracking-wider text-gray-800 font-medium">
                             Ações
                         </span>
                     </div>
                 </div>

                 <!-- Estado de carregamento -->
                 <div wire:target="disciplineId" wire:loading>
                     <div class="flex flex-col justify-center items-center gap-3 py-8">
                         <x-mary-loading class="loading-spinner text-pink-700" />
                         <span class="text-sm text-gray-600">Carregando turmas...</span>
                     </div>
                 </div>

                 <!-- Lista de turmas -->
                 <div wire:target="disciplineId" wire:loading.remove class="space-y-2">
                     @foreach ($classes as $classe)
                         <livewire:pages.teacher.classes.modal.delete :classe="$classe"
                             wire:key="classe-delete-{{ $classe->id }}" />
                         <div
                             class="grid grid-cols-12 gap-4 p-3 items-center hover:bg-gray-50 border-b border-gray-100">
                             <!-- Nome da Turma -->
                             <div class="col-span-4 md:col-span-3">
                                 <span class="text-gray-700 font-medium text-[15px]">{{ $classe->name }}</span>
                             </div>

                             <!-- Contagem de Alunos -->
                             <div class="col-span-3 md:col-span-2 flex justify-center">
                                 <span
                                     class="bg-pink-100 px-2 py-1 rounded-lg text-xs font-semibold text-pink-700 border border-pink-200">
                                     {{ $classe->students->count() }}
                                 </span>
                             </div>

                             <!-- Disciplina -->
                             <div class="col-span-3 md:col-span-4">
                                 <span class="text-gray-600 text-sm">{{ $classe->discipline->title }}</span>
                             </div>

                             <!-- Ações -->
                             <div class="col-span-2 md:col-span-3 flex justify-end gap-2">
                                 <a href="{{ route('teacher.classes.show', $classe) }}"
                                     class="btn btn-sm btn-ghost text-gray-600 hover:text-pink-700" title="Acessar">
                                     <x-mary-icon name="o-eye" class="w-4 h-4" />
                                     <span class="md:hidden">Ver</span>
                                 </a>
                                 <button class="btn btn-sm btn-ghost text-gray-600 hover:text-blue-600" title="Editar">
                                     <x-mary-icon name="o-pencil" class="w-4 h-4" />
                                     <span class="md:hidden">Editar</span>
                                 </button>
                                 <button type="button" class="btn btn-sm btn-ghost text-gray-600 hover:text-red-600"
                                     title="Deletar" wire:key='delete-classe-modal-{{ $classe->id }}'
                                     x-on:click="$dispatch('delete-classe-modal',{id:'{{ $classe->id }}'})">
                                     <x-mary-icon name="o-trash" class="w-4 h-4" />
                                     <span class="md:hidden">Excluir</span>
                                 </button>
                             </div>
                         </div>
                     @endforeach
                 </div>
             @else
                 <div class="flex justify-center items-center p-8 bg-gray-50 rounded-lg">
                     <p class="text-gray-500">Nenhuma turma cadastrada</p>
                 </div>
             @endif

         </div>
     </div>
 </div>
