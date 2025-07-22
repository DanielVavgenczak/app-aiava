<?php

use Livewire\Volt\Component;
use App\Models\{Lesson};
use Livewire\Attributes\On;

new class extends Component {
    public $selectedTab = 'material-tab';
    public $lesson;
    public bool $sureDeleteBlock = false;
    public string $video_url = '';

    // public function mount()
    // {
    //     $this->lesson = $lesson;
    //     // if ($this->lesson) {
    //     //     //$this->loadBlock();
    //     // }
    // }
    protected function loadBlock()
    {
        $this->video_url = route('media.stream', ['lesson_id' => $this->lesson->id]);
        $this->dispatch('vide-load', url: $this->video_url);
    }
    #[On('next-video-content')]
    public function nextVideo()
    {
        if (!$this->lesson) {
            $this->dispatch('next-video-content', id: $this->lesson->id);
        }
    }
    #[On('select-video-block')]
    public function loadVideoLesson($lesson_id)
    {
        //dd($lesson_id);
        $this->lesson = Lesson::where('id', $lesson_id)->first();
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

        <!-- Navegação -->
        <div class="flex items-center justify-between p-5">
            <x-mary-button label="Anterior" icon="o-chevron-left"
                x-on:click="$dispatch('previous-video-content', { id: '{{ $lesson->id }}' })" />
            <x-mary-button label="Próximo" icon-right="o-chevron-right" x-on:click="$wire.nextVideo()" />
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
            <x-mary-icon name="o-video-camera-slash" class="w-12 h-12 text-gray-400" />
            <p class="mt-2 text-gray-500">Nenhum bloco selecionado</p>
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
