@props([
    'label' => null,
    'name' => null,
    'checked' => false,
    'disabled' => false,
    'hint' => null,
    'error' => null,
    'value' => '1',
])

@php
$checkboxId = $name ?? 'checkbox-' . Str::random(8);
$hasError = $error || ($errors->has($name) && $name);
$errorMessage = $error ?? ($name ? $errors->first($name) : null);
@endphp

<div {{ $attributes->only('class') }}>
    <div class="flex items-start">
        <div class="flex items-center h-5">
            <input
                type="checkbox"
                id="{{ $checkboxId }}"
                name="{{ $name }}"
                value="{{ $value }}"
                class="w-4 h-4 text-emerald-600 bg-white border-slate-300 rounded focus:ring-emerald-500 focus:ring-2 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                @if($checked) checked @endif
                @if($disabled) disabled @endif
                @if($hasError) aria-invalid="true" aria-describedby="{{ $checkboxId }}-error" @endif
                @if($hint && !$hasError) aria-describedby="{{ $checkboxId }}-hint" @endif
                {{ $attributes->except(['class', 'label', 'name', 'checked']) }}
            />
        </div>
        @if($label)
        <div class="ml-3 text-sm">
            <label for="{{ $checkboxId }}" class="font-medium text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                {{ $label }}
            </label>
            @if($hint && !$hasError)
            <p id="{{ $checkboxId }}-hint" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{ $hint }}
            </p>
            @endif
        </div>
        @endif
    </div>

    @if($hasError)
    <p id="{{ $checkboxId }}-error" class="mt-1.5 ml-7 text-xs text-red-600 dark:text-red-400 flex items-start gap-1" role="alert">
        <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span>{{ $errorMessage }}</span>
    </p>
    @endif
</div>
