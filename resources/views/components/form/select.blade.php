@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'disabled' => false,
    'hint' => null,
    'error' => null,
    'placeholder' => null,
    'multiple' => false,
])

@php
$selectId = $name ?? 'select-' . Str::random(8);
$hasError = $error || ($errors->has($name) && $name);
$errorMessage = $error ?? ($name ? $errors->first($name) : null);
$selectClasses = 'erp-input w-full transition-colors duration-200';

if ($hasError) {
    $selectClasses .= ' border-red-500 focus:border-red-600 focus:ring-red-500';
} else {
    $selectClasses .= ' border-slate-300 focus:border-emerald-500 focus:ring-emerald-500';
}

if ($disabled) {
    $selectClasses .= ' bg-slate-100 cursor-not-allowed opacity-60';
}
@endphp

<div {{ $attributes->only('class') }}>
    @if($label)
    <label for="{{ $selectId }}" class="erp-label block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
        {{ $label }}
        @if($required)
        <span class="text-red-500 ml-0.5" aria-label="{{ __('Required field') }}">*</span>
        @endif
    </label>
    @endif

    <select
        id="{{ $selectId }}"
        name="{{ $name }}"
        class="{{ $selectClasses }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($multiple) multiple @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $selectId }}-error" @endif
        @if($hint && !$hasError) aria-describedby="{{ $selectId }}-hint" @endif
        {{ $attributes->except(['class', 'label', 'name']) }}
    >
        @if($placeholder && !$multiple)
        <option value="">{{ $placeholder }}</option>
        @endif
        {{ $slot }}
    </select>

    @if($hint && !$hasError)
    <p id="{{ $selectId }}-hint" class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
        {{ $hint }}
    </p>
    @endif

    @if($hasError)
    <p id="{{ $selectId }}-error" class="mt-1.5 text-xs text-red-600 dark:text-red-400 flex items-start gap-1" role="alert">
        <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span>{{ $errorMessage }}</span>
    </p>
    @endif
</div>
