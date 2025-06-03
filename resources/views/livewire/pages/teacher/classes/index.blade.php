 <div>
     <div class="py-4">
         <a href="{{ route('teacher.discipline') }}" wire:navigate>
             <x-mary-icon name="o-arrow-left" class="w-4" />
             <span class="text-xs font-semibold text-gray-600 uppercase">voltar</span>
         </a>
     </div>
     <div class="p-5 bg-white">
         <div class="mb-5">
             <h2 class="text-lg font-semibold text-gray-600">Minhas turmas</h2>
         </div>
         <div class="flex items-center justify-between">
             <x-mary-input wire:model="search" placeholder="Pesquisar turma" icon="o-magnifying-glass"
                 class="class-input-search !outline-none" />
             <button class="btn bg-pink-600 text-gray-50 discipline-new flex gap-1.5 items-center"
                 id="discipline-create" x-on:click="$dispatch('open-form-class-create',{open:true})"
                 mary:key="discipline-create-button">
                 <x-mary-icon name="o-plus" class="w-5 h-5" /> <span class="text-sm tracking-wide">turma</span>
             </button>
             <livewire:pages.teacher.classes.modal.create />
         </div>
         <div>
             @if ($classes->count() > 0)
                 <ul class="list bg-base-100 rounded-box shadow-md">
                     @foreach ($classes as $classe)
                         <li class="list-row flex justify-between">
                             <div>
                                 <div>{{ $classe->name }}</div>
                             </div>
                             <div>
                                 <span class="text-xs uppercase font-semibold opacity-60">
                                     <x-mary-icon name="o-users" class="w-5 h-5" />
                                     {{ count($classe->students) }}
                                 </span>
                             </div>
                             <div>
                                 <x-mary-button class="btn btn-square btn-ghost" icon="o-arrow-uturn-right"
                                     link="{{ route('teacher.classes.show', $classe) }}" />
                                 <button class="btn btn-square btn-ghost">
                                     <x-mary-icon name="o-pencil" class="w-5 h-5" />
                                 </button>
                                 <button class="btn btn-square btn-ghost">
                                     <x-mary-icon name="o-trash" class="w-5 h-5" />
                                 </button>
                             </div>
                         </li>
                     @endforeach
                 </ul>
             @endif
         </div>
     </div>
 </div>
