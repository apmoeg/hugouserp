{{-- resources/views/components/ui/loading-overlay.blade.php --}}
@props([
    'show' => false,
    'message' => null,
    'spinner' => true,
    'backdrop' => true,
])

@if($show)
<div {{ $attributes->merge(['class' => 'loading-overlay flex items-center justify-center']) }}>
    @if($backdrop)
    <div class="absolute inset-0 bg-slate-900/50 dark:bg-slate-950/70 backdrop-blur-sm transition-opacity"></div>
    @endif
    
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-8 max-w-sm mx-4">
        <div class="flex flex-col items-center">
            @if($spinner)
            <svg class="animate-spin h-12 w-12 text-emerald-600 dark:text-emerald-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            @endif
            
            @if($message)
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 text-center">
                {{ $message }}
            </p>
            @else
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 text-center">
                {{ __('Loading...') }}
            </p>
            @endif
            
            @if($slot->isNotEmpty())
            <div class="mt-4 text-xs text-slate-500 dark:text-slate-400 text-center">
                {{ $slot }}
            </div>
            @endif
        </div>
    </div>
</div>
@endif
