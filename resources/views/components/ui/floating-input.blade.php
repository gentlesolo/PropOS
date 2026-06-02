@props([
    'id',
    'type' => 'text',
    'label',
    'model' => null,
    'defer' => false,
    'lazy' => false
])

@php
    $wireModel = '';
    if ($model) {
        $wireModel = 'wire:model';
        if ($defer) $wireModel .= '.defer';
        if ($lazy) $wireModel .= '.lazy';
        $wireModel .= '="' . $model . '"';
    }
@endphp

<div class="relative w-full">
    <input 
        type="{{ $type }}" 
        id="{{ $id }}"
        {!! $wireModel !!}
        {{ $attributes->merge(['class' => 'block px-4 pb-2.5 pt-5 w-full text-sm text-text-primary bg-surface-input rounded-xl border border-border-default appearance-none focus:outline-none focus:ring-1 focus:ring-brand-primary focus:border-brand-primary peer transition-colors']) }}
        placeholder=" "
    />
    <label 
        for="{{ $id }}" 
        class="absolute text-sm text-text-tertiary duration-300 transform -translate-y-3 scale-75 top-4 z-10 origin-[0] left-4 peer-focus:text-brand-primary peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-3 pointer-events-none">
        {{ $label }}
    </label>
</div>
