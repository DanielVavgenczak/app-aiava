<?php

use Livewire\Volt\Component;

new class extends Component {
    public string $module_id;

    public function mount(string $module_id)
    {
        $this->module_id = $module_id;
    }
}; ?>
<div x-data="chunckupload('{{ $module_id }}')">
    <div class="flex flex-col items-center justify-center">
        <div id="browser-file-{{ $module_id ?? '' }}" @click="uploadStart()"
            data-url="{{ route('media.upload', parameters: ['module_id' => $module_id]) }}"
            class="flex flex-col items-center justify-center gap-2 px-6 py-3 text-center text-gray-500 uppercase transition duration-200 rounded-lg cursor-pointer tour-guide-new-block w-60 hover:text-sky-500">
            <x-mary-icon name="o-plus-circle" class="w-5 h-5" />
            <span class="text-xs font-normal uppercase">clique aqui para adicionar o
                video</span>
        </div>

        <!-- Mensagem de erro para vídeos longos -->
        <div id="message-warning-duration-{{ $module_id }}" style="display: none"
            class="flex items-center gap-4 p-3 mt-4 bg-red-100 rounded-lg">
            <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 text-red-500" />
            <p class="text-sm font-normal text-red-500 uppercase">O vídeo deve ter no máximo 12 minutos de duração.</p>
        </div>

        <!-- Vídeo temporário para validação (oculto) -->
        <video id="temp-video-{{ $module_id }}" style="display: none;"></video>

        <!-- Mensagem de sucesso -->
        <div id="success-message-{{ $module_id }}" style="display: none"
            class="flex items-center gap-4 p-3 mt-4 bg-green-100 rounded-lg">
            <x-mary-icon name="o-check-circle" class="w-5 h-5 text-green-500" />
            <p class="text-sm font-normal text-green-500 uppercase">Upload concluído com sucesso!</p>
        </div>

        <!-- Vídeo enviado -->
        <div id="video-preview-load-{{ $module_id }}" class="items-center hidden gap-4 mt-4">
            <video id="video-upload-preview-{{ $module_id }}" controls width="500"></video>
        </div>


        <div>
            <input id="fileInputVideo-{{ $module_id }}" type="file" accept="video/*" style="display: none;" />
        </div>




        <div id="video-preview-load" class="items-center hidden gap-4 ">
            <button id="removeVideoButton" class="px-4 py-2 text-white bg-red-500 rounded hover:bg-red-600">Remover
                Vídeo</button>
            <video id="video-upload-preview" controls width="500"></video>
        </div>
        <div style="display: none" class="w-full h-[6px] mt-3 bg-gray-200 rounded-full progress dark:bg-gray-700">
            <div class="bg-pink-600  text-xs font-medium text-blue-100  p-0.5 leading-none rounded-full progress-bar progress-bar-striped progress-bar-animated text-center"
                role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"
                style="width: 75%; height: 100%">
            </div>
        </div>
        <div id="progress-bar-container-{{ $module_id }}" style="display: none"
            class="w-full h-[6px] mt-3 bg-gray-200 rounded-full progress dark:bg-gray-700">
            <div id="progress-bar-{{ $module_id }}"
                class="bg-pink-600 text-xs font-medium text-blue-100 p-0.5 leading-none rounded-full progress-bar progress-bar-striped progress-bar-animated text-center"
                role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"
                style="width: 0%; height: 100%">
            </div>
        </div>
    </div>
</div>
