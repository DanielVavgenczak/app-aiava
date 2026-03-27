<?php

use Livewire\Component;

new class extends Component {
    public $initiais;

    public function mount(string $initiais)
    {
        $this->initiais = substr($initiais, 0, 1);
    }
}; ?>

<div>
    <div class="flex items-center justify-center w-8 h-8 text-xl font-semibold bg-blue-600 rounded-full text-gray-50">
        {{ $initiais }}
    </div>
</div>
