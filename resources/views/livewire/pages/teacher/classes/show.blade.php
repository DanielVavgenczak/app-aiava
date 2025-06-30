 <div>
     <div class="py-4">
         <a href="{{ route('teacher.classes') }}" wire:navigate>
             <x-mary-icon name="o-arrow-left" class="w-4" />
             <span class="text-xs font-semibold text-gray-600 uppercase">voltar</span>
         </a>
     </div>

     <div class="p-5 bg-white" data-tour="$classe->id">
         <div class="mb-5 flex justify-between items-center">
             <h2 class="text-lg font-semibold text-gray-600"> Turma:{{ $classe->name }}</h2>
             <button class="btn btn-sm bg-pink-600 text-gray-50" type="button" id="student-register"
                 wire:click.prevent="dispatch('open-form-register-student',{open:true, name:'{{ $classe->name }}'})">
                 <x-mary-icon name="o-user-plus" />
                 <span class="capitalize">aluno</span>
             </button>
         </div>
         <livewire:pages.teacher.classes.modal.register-student :classe="$classe" />
         <span>Alunos: {{ count($classe->students) }}</span>
         @if (count($classe->students) > 0)
             <div
                 class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden dark:bg-slate-800 dark:border-slate-700">
                 <!-- Header com controle e busca -->
                 <div
                     class="flex flex-col md:flex-row justify-between items-start md:items-center p-4 border-b border-gray-100 dark:border-slate-700">


                     <div class="flex items-center gap-3 w-full md:w-auto">
                         <div class="relative flex-1 md:w-64">
                             <x-mary-input placeholder="Buscar aluno..." icon="o-magnifying-glass"
                                 class="!pl-10 !outline-none" />
                         </div>
                     </div>
                 </div>

                 <!-- Tabela responsiva -->
                 <div class="overflow-x-auto" id="student-list">
                     <table class="w-full">
                         <thead class="bg-gray-50 dark:bg-slate-700">
                             <tr>
                                 <th
                                     class="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                     N°</th>
                                 <th
                                     class="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                     Aluno</th>
                                 <th
                                     class="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                     Contato</th>
                                 <th
                                     class="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                     Status</th>
                                 <th
                                     class="p-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                     Ações</th>
                             </tr>
                         </thead>
                         <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                             @foreach ($classe->students as $index => $student)
                                 <tr class="hover:bg-gray-100 dark:hover:bg-slate-700/50 transition-colors card-student"
                                     id="student-{{ $student->id }}" data-tour="{{ $student->id }}">
                                     <td class="p-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                         {{ $index + 1 }}
                                     </td>

                                     <td class="p-3 whitespace-nowrap">
                                         <div class="flex items-center gap-3">
                                             <div
                                                 class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 dark:bg-blue-900/50 dark:text-blue-300">
                                                 {{ strtoupper(substr($student->first_name, 0, 1)) }}
                                             </div>
                                             <div>
                                                 <div class="font-medium text-gray-900 dark:text-white">
                                                     {{ $student->full_name }}</div>
                                                 <div class="text-xs text-gray-500 dark:text-gray-400">Matrícula:
                                                     {{ $student->created_at ?? 'N/A' }}</div>
                                             </div>
                                         </div>
                                     </td>

                                     <td class="p-3 whitespace-nowrap">
                                         <div class="text-sm text-gray-900 dark:text-gray-200">{{ $student->email }}
                                         </div>
                                         <div class="text-sm text-blue-600 dark:text-blue-400">
                                             {{ $student->phone ?? 'Sem telefone' }}</div>
                                     </td>

                                     <td class="p-3 whitespace-nowrap">
                                         <span
                                             class="px-2 py-1 text-xs rounded-full {{ $student->active ? 'bg-red-100 text-red-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                             {{ $student->active ? 'Ativo' : 'Inativo' }}
                                         </span>
                                     </td>

                                     <td class="p-3 whitespace-nowrap text-right text-sm font-medium">
                                         <div class="flex justify-end gap-2">
                                             <button id="student-access-{{ $student->id }}"
                                                 x-on:click="$dispatch('show-student-details', { id: '{{ $student->id }}' })"
                                                 class="btn btn-sm text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                 title="Ver detalhes">
                                                 <x-mary-icon name="o-eye" class="w-5 h-5" />
                                             </button>

                                             <button type="button" id="student-btn-edit-{{ $student->id }}"
                                                 x-on:click="$dispatch('edit-student', { id: '{{ $student->id }}' })"
                                                 class="btn btn-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                                 title="Editar">
                                                 <x-mary-icon name="o-pencil-square" class="w-5 h-5" />
                                             </button>

                                             <button type="button" id="student-btn-delete-{{ $student->id }}"
                                                 x-on:click="$dispatch('delete-student', {id:'{{ $student->id }}'})"
                                                 class="btn btn-sm text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                 title="Remover">
                                                 <x-mary-icon name="o-trash" class="w-5 h-5" />
                                             </button>
                                         </div>
                                     </td>
                                 </tr>
                                 <livewire:pages.teacher.classes.modal.student :student="$student"
                                     mary:key="student-details-{{ $student->id }}" />
                                 <livewire:pages.teacher.classes.modal.edit-student :student="$student"
                                     mary:key="student-edit-{{ $student->id }}" />
                                 <livewire:pages.teacher.classes.modal.delete-student :student="$student"
                                     mary:key="student-delete-{{ $student->id }}" />
                             @endforeach
                         </tbody>
                     </table>
                 </div>
             @else
                 <p>Nenhum aluno resgistrado a essa turma</p>
         @endif

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
                     label="Registrar estudante"
                     x-on:click.prevent="$store.tourguide.start('StartTourStudentRegister')" />

                 @if ($classe->students->count() > 0)
                     <x-mary-button
                         class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                         label="Ver aluno" x-on:click="$store.tourguide.start('StartTourStudentAccess')" />
                     <x-mary-button
                         class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                         label="Editar um aluno" x-on:click="$store.tourguide.start('StartTourStudentEdit')" />
                     <x-mary-button
                         class="font-medium text-pink-600 border-[1px] border-pink-600 btn hover:bg-pink-600 hover:text-gray-100"
                         label="Deletar um aluno" x-on:click="$store.tourguide.start('StartTourStudentDelete')" />
                 @endif
             </div>
         </div>
         <img src="{{ asset('images/ai-sos.png') }}" class="fixed right-0 w-64 bottom-4 sm:w-48">
     </div>
 </div>
