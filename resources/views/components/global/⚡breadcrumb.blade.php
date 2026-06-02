<?php

use Livewire\Component;

new class extends Component {
    public array $items = [];
    public string $color = 'blue';
    public ?string $tourId = null;

    public function mount(array $items = [], string $color = 'blue', ?string $tourId = null): void
    {
        $this->items = $items;
        $this->color = $color;
        $this->tourId = $tourId;
    }

    public function colorClasses(string $type): string
    {
        return match ($this->color) {
            'pink' => match ($type) {
                'link' => 'text-gray-500 hover:text-pink-700',
                'current' => 'text-pink-700 font-medium',
                default => '',
            },
            'green' => match ($type) {
                'link' => 'text-gray-500 hover:text-green-700',
                'current' => 'text-green-700 font-medium',
                default => '',
            },
            'gray' => match ($type) {
                'link' => 'text-gray-500 hover:text-gray-600',
                'current' => 'text-gray-600 font-medium',
                default => '',
            },
            default => match ($type) {
                'link' => 'text-gray-500 hover:text-blue-700',
                'current' => 'text-blue-700 font-medium',
                default => '',
            },
        };
    }
}; ?>

<div @if($tourId) id="{{ $tourId }}" @endif class="py-4">
    <nav aria-label="Breadcrumb">
        <ol class="flex items-center gap-2 text-sm">
            @foreach ($items as $index => $item)
                <li class="flex items-center gap-2">
                    @if ($index > 0)
                        <x-mary-icon name="o-chevron-right" class="w-3 h-3 text-gray-400" />
                    @endif

                    @if (!empty($item['route']))
                        <a href="{{ route($item['route'], $item['params'] ?? []) }}"
                            wire:navigate
                            class="inline-flex items-center gap-1.5 transition-colors {{ $this->colorClasses('link') }}">
                            @if (!empty($item['icon']))
                                <x-mary-icon :name="$item['icon']" class="w-4 h-4" />
                            @endif
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="inline-flex items-center gap-1.5 {{ $this->colorClasses('current') }}">
                            @if (!empty($item['icon']))
                                <x-mary-icon :name="$item['icon']" class="w-4 h-4" />
                            @endif
                            {{ $item['label'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
</div>
