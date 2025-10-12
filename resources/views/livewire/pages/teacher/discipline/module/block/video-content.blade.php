<?php

use Livewire\Volt\Component;
use App\Models\{Lesson};
use Livewire\Attributes\On;

new class extends Component {
    public $lesson;
    public $selectedTab = 'material-tab';
    public bool $sureDeleteBlock = false;
    public string $video_url = '';
    
    // shows messages 
    public bool $showEndBlock = false;
    public bool $hasNext = false;
    public bool $hasPrevius = false;

    // protected function loadBlock()
    // {
    //     $this->video_url = route('media.stream', ['lesson_id' => $this->lesson->id]);
    //     $this->dispatch('vide-load', url: $this->video_url);
    // }

    #[On('next-video-content')]
    // public function nextVideo()
    // {
    //     if (!$this->lesson) {
    //         $this->dispatch('next-video-content', id: $this->lesson->id);
    //     }
    // }

    #[On('load-video-block')]
    public function loadVideoLesson($lesson_id)
    {
        $this->lesson = Lesson::with('module')->find($lesson_id);
        if($this->lesson){
          $this->video_url = route('media.stream', ['lesson_id' => $this->lesson->id]);

          $this->updateNavigationStates();
        }
        $this->showEndBlock = false;
    }

    // 👇 MÉTODO PARA ATUALIZAR OS ESTADOS
    protected function updateNavigationStates()
    {
        if (!$this->lesson) {
            $this->hasPrevious = false;
            $this->hasNext = false;
            return;
        }

        // Verifica se existe lição ANTERIOR
        $this->hasPrevious = Lesson::where('module_id', $this->lesson->module_id)
            ->where('created_at', '<', $this->lesson->created_at)
            ->exists();

        // Verifica se existe lição PRÓXIMA  
        $this->hasNext = Lesson::where('module_id', $this->lesson->module_id)
            ->where('created_at', '>', $this->lesson->created_at)
            ->exists();
    }

    public function navigate($direction)
    {
         if (!$this->lesson) return;
          
         $operator = $direction === 'next' ? '>' : '<';
         $order = $direction === 'next' ? 'asc' : 'desc';

         $adjacentLesson = Lesson::where('module_id', $this->lesson->module_id)
              ->where('created_at', $operator, $this->lesson->created_at)
              ->orderBy('created_at', $order)
              ->first();

          if ($adjacentLesson) {
              $this->loadVideoLesson($adjacentLesson->id);
              $this->dispatch('lesson-navigate-selected', lesson_id: $adjacentLesson->id);
              $this->showEndBlock = false;
              //$this->dispatch('hide-end-message');
          } else {
              if($direction === 'next') {
                $this->showEndBlock = true;
              $this->dispatch('hide-end-message-afeter-delay', direction:$direction);
              }
              //session()->flash('info', "Não há {$direction} vídeo");
          }
    }
      #[On('hide-end-message-afeter-delay')]
        public function hideEndMessage()
        {
            sleep(3);
            $this->showEndMessage = false;
        }

    public function deleteBlock()
    {
        try {
            $lesson = Lesson::with('module')->where('id', $this->lesson->id)->firstOrFail();
            $path = $lesson->path_file;
            if ($path && Storage::disk('private')->exists($path)) {
                Storage::disk('private')->delete($path);
            }
            $discipline = $lesson->module->discipline;
            $lesson->delete();
            session()->flash('delete-with-files', 'Aula excluída com sucesso!');
            $this->sureDeleteBlock = false;
            return $this->redirect(route('teacher.discipline.show', $discipline), navigate: true);
        } catch (\Exception $e) {
            $this->handleDeleteError($e);
        }
    }
    protected function handleDeleteError(\Exception $e)
    {
        $msg = $e->getMessage() ?: 'Erro ao deletar aula';
        $this->error($msg, timeout: 1000, position: 'toast-bottom toast-start');
    }
}; ?>

<div class="flex flex-col w-full bg-gray-100">
    <!-- Modal de confirmação -->
    <x-mary-modal wire:model="sureDeleteBlock" class="backdrop-blur">
        <div class="flex flex-col items-center w-full gap-2 mb-4 justify-center">
            <x-mary-icon name="o-trash" class="text-red-500 w-6 h-6" />
            <p class="text-sm text-red-500 tracking-wide font-normal">
                @if ($lesson)
                    Deletar o bloco {{ $lesson->title }}
                @else
                    Bloco não disponível
                @endif
            </p>
        </div>
        <div id="delete-block-actions" class="flex justify-end w-full">
            <button x-on:click="$wire.sureDeleteBlock = false" class="btn btn-sm bg-ghost capitalize">
                Cancelar
            </button>
            <button x-on:click="$wire.deleteBlock" class="btn btn-sm bg-pink-600 capitalize text-gray-50">
                Excluir
            </button>
        </div>
    </x-mary-modal>

    <!-- Botões de ação -->
    <div class="flex justify-end w-full gap-3 px-3 py-3">
        <button class="btn btn-sm bg-ghost" disabled>
            <x-mary-icon name="o-arrow-path" />
            Atualizar
        </button>
        @if ($lesson)
            <button x-on:click="$wire.sureDeleteBlock = true" class="btn btn-sm bg-pink-600 text-gray-50">
                <x-mary-icon name="o-trash" class="w-5 h-5" />
                Excluir bloco
            </button>
        @endif
    </div>

    <!-- Player de vídeo -->
    @if ($lesson)
        <div>
            <livewire:pages.teacher.discipline.module.block.video :lesson_id="$lesson->id" :key="'video-block-' . $lesson->id" />
        </div>
           @if($showEndBlock)
            <div class="mx-5 mb-3 p-4 bg-pink-50 border border-pink-200 rounded-lg">
                <div class="flex items-center gap-3">
                    <x-mary-icon name="o-flag" class="w-5 h-5 text-pink-700 flex-shrink-0" />
                    <div class="flex-1">
                        <p class="text-pink-800 font-medium text-sm"> Você concluiu esta aula!</p>
                    </div>
                    <button wire:click="$set('showEndBlock', false)" class="text-pink-700 hover:text-pink-800 flex-shrink-0">
                        <x-mary-icon name="o-x-mark" class="w-4 h-4" />
                    </button>
                </div>
            </div>
            @endif
        <!-- Navegação -->
        <div class="flex items-center justify-between p-5">
        <button  
                type="button"
                class="btn btn-sm btn-ghost text-gray-600" 
                wire:click="navigate('previous')">
                <x-mary-icon name="o-chevron-left" />
                  Anterior
                </button>          
            @if($hasNext)
                <button  
                  type="button"
                  class="btn btn-sm btn-ghost text-gray-600" 
                  wire:click="navigate('next')">
                  Próximo
                  <x-mary-icon name="o-chevron-right" />
                </button>
            @else 
              <!--- Verificar se possue atividade -->
               <div class="px-4 py-2 text-gray-500 text-sm flex flex-col items-center">
                <span>Último bloco</span>
                <p>Selecione a próxima aula</p>
               </div>
            @endif
        </div>

        <!-- Conteúdo -->
        <div class="p-5 bg-white">
            <x-mary-tabs wire:model="selectedTab">
                <x-mary-tab name="material-tab" label="Materiais de apoio">
                    <div>Materiais de apoio</div>
                </x-mary-tab>
                <x-mary-tab name="infos-tab" label="Informações">
                    <div>
                        <h3>Informações do vídeo</h3>
                        <ul>
                            <li class="px-2 py-3">
                                <h3>{{ $lesson->title }}</h3>
                            </li>
                            <li class="px-2 py-3">
                                <h2>Descrição</h2>
                                <p>
                                    {{ $lesson->description ?? 'Sem descrição' }}
                                </p>
                            </li>
                        </ul>
                    </div>
                </x-mary-tab>
                <x-mary-tab name="question-tab" label="Dúvidas">
                    <div>Dúvidas</div>
                </x-mary-tab>
            </x-mary-tabs>
        </div>
    @else
        <div class="flex flex-col items-center justify-center p-10">
            <x-mary-icon name="o-video-camera-slash" class="w-8 h-8 text-gray-400" />
            <p class="text-sm tracking-wide font-mediummt-2 text-gray-500">Nenhum bloco selecionado</p>
        </div>
    @endif
</div>

@if ($lesson)
    @script
        <script>
            document.addEventListener('livewire:initialized', () => {
                const player = document.getElementById('videoPlayer-{{ $lesson->id }}');
                console.log('verificando aqui', player);
                if (player) {
                    player.src = '{{ $video_url }}';
                }
            });

            Livewire.on('video-load', (url) => {
                const player = document.getElementById('videoPlayer-{{ $lesson->id }}');
                if (player) {
                    player.src = url;
                    player.load();
                    player.play().catch(e => console.log('Autoplay prevented:', e));
                }
            });
        </script>
    @endscript
@endif
