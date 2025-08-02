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
                     wire:model.change="disciplineId" placeholder="Minhas disciplinas" :options="$disciplines"
                     option-label="title" option-value="id" placeholder-value="" class="w-full !outline-none" />
             </div>
             <button class="btn btn-md bg-pink-600 text-gray-50 discipline-new flex gap-1.5 items-center"
                 wire:target="disciplineId" wire:loading.attr="disabled" id="classe-create"
                 x-on:click="$dispatch('open-form-class-create',{open:true, disciplineId: '{{ $this->disciplineId }}'})"
                 mary:key="discipline-create-button">
                 <x-mary-icon name="o-plus" class="w-5 h-5" /> <span class="capitalize">turma</span>
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
                         <span class="text-sm tracking-wider text-gray-500">Carregando turmas...</span>
                     </div>
                 </div>

                 <!-- Lista de turmas -->
                 <div wire:target="disciplineId" wire:loading.remove class="space-y-2" id="classe-list">
                     @foreach ($classes as $classe)
                         <livewire:pages.teacher.classes.modal.delete :classe="$classe"
                             wire:key="classe-delete-{{ $classe->id }}" />
                         <livewire:pages.teacher.classes.modal.edit :classe="$classe"
                             wire:key="classe-edit-{{ $classe->id }}" />
                         <div data-tour="{{ $classe->id }}"
                             class="card-classe grid grid-cols-12 gap-4 p-3 items-center hover:bg-gray-50 border-[1px] border-gray-100"
                             id="classe-{{ $classe->id }}">
                             <!-- Nome da Turma -->
                             <div class="col-span-4 md:col-span-3">
                                 <span class="text-gray-600 text-sm">{{ $classe->name }}</span>
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
                                 <a href="{{ route('teacher.classes.show', $classe) }}" class="btn btn-sm"
                                     title="Acessar" id="classe-access-{{ $classe->id }}">
                                     <x-mary-icon name="o-eye" class="w-4 h-4" />
                                     <span class="text-xs uppercase ">acessar</span>
                                 </a>
                                 <button class="btn btn-sm" title="Editar" id="classe-edit-{{ $classe->id }}"
                                     wire:key='edit-classe-modal-{{ $classe->id }}'
                                     x-on:click="$dispatch('edit-classe-modal',{id:'{{ $classe->id }}'})">
                                     <x-mary-icon name="o-pencil" class="w-4 h-4" />
                                     <span class="text-xs uppercase">Editar</span>
                                 </button>
                                 <button type="button" class="btn btn-sm" title="Deletar"
                                     id="classe-delete-{{ $classe->id }}"
                                     wire:key='delete-classe-modal-{{ $classe->id }}'
                                     x-on:click="$dispatch('delete-classe-modal',{id:'{{ $classe->id }}'})">
                                     <x-mary-icon name="o-trash" class="w-4 h-4" />
                                     <span class="text-xs uppercase">Excluir</span>
                                 </button>
                             </div>
                         </div>
                     @endforeach
                 </div>
             @else
                 <p class="text-gray-500 text-sm tracking-wider font-normal text-center">Nenhuma turma cadastrada</p>
             @endif

         </div>
     </div>
     <div class="fixed inset-0 z-40 bg-gray-900/50" x-data x-show="$store.tourguide.openMai">
         <!-- Centralizando a div -->
         <div
             class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-4xl sm:w-2/3 bg-white rounded-lg shadow-lg border-[1px] border-pink-200 p-6 flex flex-col gap-5">
             <div class="flex flex-col">
                 <div class="flex justify-end">
                     <x-mary-button icon="o-x-mark"
                         class="bg-white border-none btn-square hover:none hover:bg-pink-600 hover:text-gray-100"
                         x-on:click="$store.tourguide.cancelOptionsTourPage()" />
                 </div>
                 <h2 class="text-lg font-normal text-gray-700">Você tem as seguintes opções:</h2>
             </div>
             <div class="flex items-center gap-3 p-4 bg-gray-100">
                 <x-mary-button
                     class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                     label="Criar uma turma" x-on:click="$store.tourguide.start('StartTourClasseCreate')" />

                 @if ($classes->count() > 0)
                     <x-mary-button
                         class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                         label="Editar uma turma" @click="$store.tourguide.start('StartTourClasseEdit')" />
                     <x-mary-button
                         class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                         label="Deletar uma turma" @click="$store.tourguide.start('StartTourClasseDelete')" />
                     <x-mary-button
                         class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                         label="Acessar classe" @click="$store.tourguide.start('StartTourClasseAccess')" />
                 @endif
             </div>
         </div>
         <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
     </div>
 </div>
