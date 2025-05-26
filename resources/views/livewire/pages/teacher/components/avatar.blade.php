<?php

use Livewire\Volt\Component;

new class extends Component {
    public $initiais;

    public function mount(string $initiais)
    {
        $this->initiais = $initiais;
    }
}; ?>

<div>
    <div class="flex items-center justify-center w-12 h-12 text-xl font-semibold bg-pink-600 rounded-full text-gray-50">
        {{ $initiais }}
    </div>
</div>
