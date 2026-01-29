@props([
    'disabled' => false,
    'checked' => false,
    'value' => '',
    'name' => '',
    'id' => null,
])

<input type="radio" @disabled($disabled) @checked($checked) value="{{ $value }}"
    name="{{ $name }}" id="{{ $id ?? $name }}"
    {{ $attributes->merge([
        'class' => 'border-gray-300 text-indigo-600 focus:ring-indigo-500 shadow-sm',
    ]) }}>
