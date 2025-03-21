<?php

use Livewire\Volt\Component;

new class extends Component {
    public $initiais;
}; ?>

<div>
    <div class="flex items-center justify-center w-16 h-16 text-xl font-semibold bg-pink-600 rounded-full text-gray-50">
        {{ $initiais }}
    </div>
</div>
