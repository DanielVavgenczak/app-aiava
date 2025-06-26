 <div>
     <div class="py-4">
         <a href="{{ route('teacher.classes') }}" wire:navigate>
             <x-mary-icon name="o-arrow-left" class="w-4" />
             <span class="text-xs font-semibold text-gray-600 uppercase">voltar</span>
         </a>
     </div>

     <div class="p-5 bg-white">
         <div class="mb-5 flex justify-between items-center">
             <h2 class="text-lg font-semibold text-gray-600"> {{ $classe->name }}</h2>
             <button class="btn bg-pink-700 text-gray-50" type="button"
                 wire:click.prevent="dispatch('open-form-register-student',{open:true, name:'{{ $classe->name }}'})">
                 <x-mary-icon name="o-user-plus" />
                 <span class="text-sm tracking-wide capitalize">aluno</span>
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
                 <div class="overflow-x-auto">
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
                                 <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
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
                                             <button
                                                 x-on:click="$dispatch('show-student-details', { id: '{{ $student->id }}' })"
                                                 class="btn btn-sm text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                 title="Ver detalhes">
                                                 <x-mary-icon name="o-eye" class="w-5 h-5" />
                                             </button>

                                             <button wire:click="editStudent({{ $student->id }})"
                                                 class="btn btn-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                                 title="Editar">
                                                 <x-mary-icon name="o-pencil-square" class="w-5 h-5" />
                                             </button>

                                             <button wire:click="confirmDelete({{ $student->id }})"
                                                 class="btn btn-sm text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                 title="Remover">
                                                 <x-mary-icon name="o-trash" class="w-5 h-5" />
                                             </button>
                                         </div>
                                     </td>
                                 </tr>
                                 <livewire:pages.teacher.classes.modal.student :student="$student" />
                             @endforeach
                         </tbody>
                     </table>
                 </div>
             @else
                 <p>Nenhum aluno resgistrado a essa turma</p>
         @endif

     </div>
 </div>
