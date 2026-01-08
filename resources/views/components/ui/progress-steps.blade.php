@props([
    'steps' => [],
    'currentStep' => 1,
    'type' => 'horizontal', // horizontal, vertical
    'showLabels' => true,
    'showNumbers' => true,
])

<div class="w-full">
    @if($type === 'horizontal')
    <!-- Horizontal Progress -->
    <div class="w-full">
        <div class="flex items-center justify-between">
            @foreach($steps as $index => $step)
            @php
                $stepNumber = $index + 1;
                $isCompleted = $stepNumber < $currentStep;
                $isCurrent = $stepNumber === $currentStep;
                $isPending = $stepNumber > $currentStep;
            @endphp
            
            <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                <!-- Step Circle -->
                <div class="relative flex items-center justify-center">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all
                        {{ $isCompleted ? 'bg-green-500 border-green-500' : '' }}
                        {{ $isCurrent ? 'bg-blue-500 border-blue-500' : '' }}
                        {{ $isPending ? 'bg-gray-200 dark:bg-gray-700 border-gray-300 dark:border-gray-600' : '' }}
                    ">
                        @if($isCompleted)
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        @elseif($showNumbers)
                        <span class="text-sm font-semibold {{ $isCurrent ? 'text-white' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ $stepNumber }}
                        </span>
                        @else
                        <span class="w-3 h-3 rounded-full {{ $isCurrent ? 'bg-white' : 'bg-gray-400' }}"></span>
                        @endif
                    </div>
                    
                    @if($showLabels)
                    <div class="absolute top-12 left-1/2 -translate-x-1/2 whitespace-nowrap">
                        <div class="text-sm font-medium {{ $isCurrent ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }}">
                            {{ $step['label'] ?? "Step {$stepNumber}" }}
                        </div>
                        @if(isset($step['description']))
                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            {{ $step['description'] }}
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                
                <!-- Connector Line -->
                @if(!$loop->last)
                <div class="flex-1 h-0.5 mx-2 transition-all
                    {{ $isCompleted ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}
                "></div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    
    @else
    <!-- Vertical Progress -->
    <div class="space-y-4">
        @foreach($steps as $index => $step)
        @php
            $stepNumber = $index + 1;
            $isCompleted = $stepNumber < $currentStep;
            $isCurrent = $stepNumber === $currentStep;
            $isPending = $stepNumber > $currentStep;
        @endphp
        
        <div class="flex items-start">
            <!-- Step Circle and Line -->
            <div class="flex flex-col items-center mr-4 rtl:ml-4 rtl:mr-0">
                <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all
                    {{ $isCompleted ? 'bg-green-500 border-green-500' : '' }}
                    {{ $isCurrent ? 'bg-blue-500 border-blue-500' : '' }}
                    {{ $isPending ? 'bg-gray-200 dark:bg-gray-700 border-gray-300 dark:border-gray-600' : '' }}
                ">
                    @if($isCompleted)
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    @elseif($showNumbers)
                    <span class="text-sm font-semibold {{ $isCurrent ? 'text-white' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ $stepNumber }}
                    </span>
                    @else
                    <span class="w-3 h-3 rounded-full {{ $isCurrent ? 'bg-white' : 'bg-gray-400' }}"></span>
                    @endif
                </div>
                
                <!-- Connector Line -->
                @if(!$loop->last)
                <div class="w-0.5 h-12 my-1 transition-all
                    {{ $isCompleted ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}
                "></div>
                @endif
            </div>
            
            <!-- Step Content -->
            @if($showLabels)
            <div class="flex-1 {{ $loop->last ? '' : 'pb-8' }}">
                <div class="text-base font-medium {{ $isCurrent ? 'text-blue-600 dark:text-blue-400' : 'text-gray-900 dark:text-gray-100' }}">
                    {{ $step['label'] ?? "Step {$stepNumber}" }}
                </div>
                @if(isset($step['description']))
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ $step['description'] }}
                </div>
                @endif
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>
