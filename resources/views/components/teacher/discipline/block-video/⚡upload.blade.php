<?php

use Livewire\Component;

new class extends Component {
    public string $module_id;

    public function mount(string $module_id)
    {
        $this->module_id = $module_id;
    }
}; ?>
<div x-data="chunkUpload('{{ $module_id }}')" class="max-w-3xl p-3 mx-auto" :key="'upload-content-block'.$module_id">
    <div class="space-y-6">
        <!-- Área de Upload -->
        <div x-show="!isFileSelected && !isUploading && !isUploaded" id="browser-file-{{ $module_id }}"
            data-url="{{ route('teacher.media.upload', ['module_id' => $module_id]) }}"
            class="flex flex-col items-center justify-center gap-4 px-5 py-5 transition-all duration-300 bg-white cursor-pointer rounded-xl hover:bg-indigo-50 hover:border-pink-400">
            <x-mary-icon name="o-video-camera" class="w-10 h-10 text-blue-700" />
            <span class="text-sm font-medium tracking-wide text-blue-500">Clique para adicionar um vídeo</span>
            <span class="text-xs text-red-500">Apenas formato MP4 (máximo até 12 minutos)</span>
        </div>

        <!-- Preview Temporário com Botão Enviar -->
        <div x-show="isFileSelected && !isUploading && !isUploaded" id="temp-preview-{{ $module_id }}"
            class="flex flex-col items-center gap-4 px-10 py-8 bg-white rounded-xl">
            <video id="temp-video-preview-{{ $module_id }}" controls
                class="w-3/4 rounded-lg shadow-md w-max-2xl"></video>
            <div class="flex gap-4" id="temp-preview-actions-{{ $module_id }}">

                <button x-on:click="cancelSelection()"
                    class="px-6 py-2 text-gray-700 transition duration-200 bg-gray-300 rounded-lg hover:bg-gray-400">
                    Cancelar
                </button>
                <button x-on:click="uploadStart()" id="lesson-send-video-block-content-{{ $module_id }}"
                    class="px-6 py-2 text-white transition duration-200 bg-blue-400 rounded-lg hover:bg-blue-500">
                    Enviar
                </button>
            </div>
        </div>

        <!-- Barra de Progresso -->
        <div x-show="isUploading" id="progress-bar-container-{{ $module_id }}"
            class="w-full px-10 py-8 bg-white border-2 border-gray-300 rounded-xl">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Enviando...</span>
                <span id="progress-percentage-{{ $module_id }}" class="text-sm font-medium text-blue-400">0%</span>
            </div>
            <div class="w-full h-3 overflow-hidden bg-gray-100 rounded-full">
                <div id="progress-bar-{{ $module_id }}"
                    class="h-full transition-all duration-300 ease-out bg-blue-400 rounded-full" style="width: 0%">
                </div>
            </div>
        </div>

        <!-- Mensagem de Erro para Vídeos Longos check-circle -->
        <div id="message-warning-duration-{{ $module_id }}" x-show="showDurationError"
            class="flex items-center gap-3 p-4 border border-red-200 rounded-lg bg-red-50">
            <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 text-red-600" />
            <p class="text-sm font-medium text-red-600">O vídeo excede o limite de 12 minutos.</p>
        </div>

        <!-- Mensagem de Sucesso -->
        <div id="success-message-{{ $module_id }}" style="display: none"
            class="flex items-center gap-3 p-4 border border-green-200 rounded-lg bg-green-50">
            <x-mary-alert icon="o-check-circle" class="alert-success w-full">
                Bloco adicionado com sucesso!
            </x-mary-alert>
        </div>

        <!-- Input de Arquivo (Oculto) -->
        <div>
            <input id="fileInputVideo-{{ $module_id }}" type="file" accept="video/*" style="display: none;" />
        </div>

        <!-- Vídeo Temporário para Validação (Oculto) -->
        <video id="temp-video-{{ $module_id }}" style="display: none;"></video>
    </div>

</div>
