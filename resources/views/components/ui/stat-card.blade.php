@props([
    'title',
    'value',
    'icon' => null,
    'iconColor' => 'blue',
    'trend' => null, // positive, negative, neutral
    'trendValue' => null,
    'trendText' => null,
    'loading' => false,
    'href' => null,
    'subtitle' => null,
])

@php
    $trendColors = [
        'positive' => 'text-green-600 bg-green-100',
        'negative' => 'text-red-600 bg-red-100',
        'neutral' => 'text-gray-600 bg-gray-100',
    ];
    
    $iconColorClasses = [
        'blue' => 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300',
        'green' => 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-300',
        'red' => 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-300',
        'yellow' => 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-300',
        'purple' => 'bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-300',
        'indigo' => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900 dark:text-indigo-300',
    ];
@endphp

@if($href)
<a href="{{ $href }}" class="block">
@endif

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow {{ $href ? 'cursor-pointer' : '' }}">
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                {{ $title }}
            </p>
            
            @if($loading)
            <div class="mt-2 space-y-2">
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                @if($subtitle)
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-2/3"></div>
                @endif
            </div>
            @else
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                {{ $value }}
            </p>
            
            @if($subtitle)
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $subtitle }}
            </p>
            @endif
            
            @if($trend && $trendValue)
            <div class="mt-3 flex items-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $trendColors[$trend] ?? $trendColors['neutral'] }}">
                    @if($trend === 'positive')
                    <svg class="w-4 h-4 mr-1 rtl:ml-1 rtl:mr-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                    </svg>
                    @elseif($trend === 'negative')
                    <svg class="w-4 h-4 mr-1 rtl:ml-1 rtl:mr-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd"/>
                    </svg>
                    @else
                    <svg class="w-4 h-4 mr-1 rtl:ml-1 rtl:mr-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                    </svg>
                    @endif
                    {{ $trendValue }}
                </span>
                @if($trendText)
                <span class="ml-2 rtl:mr-2 rtl:ml-0 text-sm text-gray-600 dark:text-gray-400">
                    {{ $trendText }}
                </span>
                @endif
            </div>
            @endif
            @endif
        </div>
        
        @if($icon)
        <div class="flex-shrink-0 ml-4 rtl:mr-4 rtl:ml-0">
            <div class="w-12 h-12 rounded-lg {{ $iconColorClasses[$iconColor] ?? $iconColorClasses['blue'] }} flex items-center justify-center">
                <span class="text-2xl">{{ $icon }}</span>
            </div>
        </div>
        @endif
    </div>
    
    @if($href)
    <div class="mt-4 flex items-center text-sm font-medium text-blue-600 dark:text-blue-400">
        <span>{{ __('View details') }}</span>
        <svg class="ml-2 rtl:mr-2 rtl:ml-0 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </div>
    @endif
</div>

@if($href)
</a>
@endif
