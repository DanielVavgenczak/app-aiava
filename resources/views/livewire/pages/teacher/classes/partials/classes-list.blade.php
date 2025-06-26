<?php

use Livewire\Volt\Component;
use Illuminate\Support\Collection;
new class extends Component {
    public Collection $classes;

    public function mount()
    {
        sleep(2);
    }

    public function placeholder()
    {
        return Blade::render('
            <div class="flex flex-col items-center justify-center gap-3 p-5 text-sm font-normal text-center text-gray-600 rounded">
                <x-mary-loading />
                <span class="tracking-wider text-sm text-gray-600">Carregando turmas</span>
            </div>');
    }
}; ?>

<div>
    @if ($classes->isNotEmpty())
        @foreach ($classes as $classe)
            <livewire:pages.teacher.classes.partials.class-item :classe="$classe" />
        @endforeach
    @else
</div>
