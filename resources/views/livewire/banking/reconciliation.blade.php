<div class="container mx-auto px-4 py-6">
    <x-ui.page-header 
        title="{{ __('Bank Reconciliation Wizard') }}"
        subtitle="{{ __('Step-by-step reconciliation of bank statements') }}"
    />

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="mb-4 p-4 bg-amber-100 dark:bg-amber-900/30 border border-amber-300 dark:border-amber-700 rounded-lg text-amber-700 dark:text-amber-300">
            {{ session('warning') }}
        </div>
    @endif

    {{-- Progress Steps --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mt-6 p-6">
        <div class="flex items-center justify-between mb-8">
            @foreach([
                ['step' => 1, 'label' => __('Select Account')],
                ['step' => 2, 'label' => __('Statement Balance')],
                ['step' => 3, 'label' => __('Match Transactions')],
                ['step' => 4, 'label' => __('Review & Complete')],
            ] as $stepInfo)
                <div class="flex items-center {{ $stepInfo['step'] < $totalSteps ? 'flex-1' : '' }}">
                    <button type="button" 
                            wire:click="goToStep({{ $stepInfo['step'] }})"
                            @class([
                                'w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium transition-colors',
                                'bg-emerald-500 text-white' => $currentStep >= $stepInfo['step'],
                                'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400' => $currentStep < $stepInfo['step'],
                                'cursor-pointer hover:bg-emerald-600' => $currentStep >= $stepInfo['step'],
                                'cursor-not-allowed' => $currentStep < $stepInfo['step'],
                            ])>
                        @if($currentStep > $stepInfo['step'])
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            {{ $stepInfo['step'] }}
                        @endif
                    </button>
                    <span class="ml-2 text-sm font-medium {{ $currentStep >= $stepInfo['step'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ $stepInfo['label'] }}
                    </span>
                    @if($stepInfo['step'] < $totalSteps)
                        <div class="flex-1 mx-4 h-1 rounded {{ $currentStep > $stepInfo['step'] ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Step 1: Select Account --}}
        @if($currentStep === 1)
            <div class="space-y-6">
                <div class="text-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('Select Bank Account & Period') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Choose the bank account and date range to reconcile') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto">
                    <div class="md:col-span-3">
                        <label class="erp-label">{{ __('Bank Account') }} <span class="text-red-500">*</span></label>
                        <select wire:model="accountId" class="erp-input">
                            <option value="">{{ __('Select Account') }}</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->account_name }} - {{ $account->account_number ?? 'N/A' }}</option>
                            @endforeach
                        </select>
                        @error('accountId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="erp-label">{{ __('Start Date') }} <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="startDate" class="erp-input">
                        @error('startDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="erp-label">{{ __('End Date') }} <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="endDate" class="erp-input">
                        @error('endDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        @endif

        {{-- Step 2: Statement Balance --}}
        @if($currentStep === 2)
            <div class="space-y-6">
                <div class="text-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('Enter Statement Balance') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Enter the closing balance from your bank statement') }}</p>
                </div>

                @if($selectedAccount)
                    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4 max-w-md mx-auto">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>{{ __('Account') }}:</strong> {{ $selectedAccount->account_name }}<br>
                            <strong>{{ __('Current System Balance') }}:</strong> {{ number_format($selectedAccount->current_balance ?? 0, 2) }}
                        </p>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-xl mx-auto">
                    <div>
                        <label class="erp-label">{{ __('Statement Balance') }} <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" wire:model="statementBalance" class="erp-input text-lg" placeholder="0.00">
                        @error('statementBalance') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="erp-label">{{ __('Statement Date') }} <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="statementDate" class="erp-input">
                        @error('statementDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        @endif

        {{-- Step 3: Match Transactions --}}
        @if($currentStep === 3)
            <div class="space-y-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('Match Transactions') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Click transactions to match or unmatch them') }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" wire:click="matchAll" class="erp-btn erp-btn-secondary text-sm">
                            {{ __('Match All') }}
                        </button>
                        <button type="button" wire:click="unmatchAll" class="erp-btn erp-btn-secondary text-sm">
                            {{ __('Unmatch All') }}
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Unmatched Transactions --}}
                    <div>
                        <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            {{ __('Unmatched Transactions') }} ({{ count($unmatchedTransactions) }})
                        </h4>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @forelse($unmatchedTransactions as $transaction)
                                <div wire:click="toggleMatch({{ $transaction['id'] }})" 
                                     class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg cursor-pointer hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-medium text-gray-800 dark:text-gray-200 text-sm">{{ $transaction['description'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $transaction['date'] }} · {{ $transaction['reference'] ?? 'No Ref' }}</p>
                                        </div>
                                        <span class="{{ $transaction['amount'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} font-medium">
                                            {{ number_format($transaction['amount'], 2) }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-center text-gray-500 dark:text-gray-400 py-8">{{ __('No unmatched transactions') }}</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Matched Transactions --}}
                    <div>
                        <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-green-500"></span>
                            {{ __('Matched Transactions') }} ({{ count($matchedTransactions) }})
                        </h4>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @forelse($matchedTransactions as $transaction)
                                <div wire:click="toggleMatch({{ $transaction['id'] }})" 
                                     class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg cursor-pointer hover:bg-green-100 dark:hover:bg-green-900/40 transition-colors">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-medium text-gray-800 dark:text-gray-200 text-sm">{{ $transaction['description'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $transaction['date'] }} · {{ $transaction['reference'] ?? 'No Ref' }}</p>
                                        </div>
                                        <span class="{{ $transaction['amount'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} font-medium">
                                            {{ number_format($transaction['amount'], 2) }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-center text-gray-500 dark:text-gray-400 py-8">{{ __('No matched transactions yet') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Step 4: Review & Complete --}}
        @if($currentStep === 4)
            <div class="space-y-6">
                <div class="text-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('Review & Complete') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Review the reconciliation summary before completing') }}</p>
                </div>

                <div class="max-w-xl mx-auto">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6 space-y-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Statement Balance') }}</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ number_format($statementBalance, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Matched Transactions Total') }}</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ number_format(collect($matchedTransactions)->sum('amount'), 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Transactions Matched') }}</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ count($matchedTransactions) }}</span>
                        </div>
                        <hr class="border-gray-200 dark:border-gray-600">
                        <div class="flex justify-between text-lg">
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('Difference') }}</span>
                            <span class="font-bold {{ abs($difference) < 0.01 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ number_format($difference, 2) }}
                            </span>
                        </div>
                    </div>

                    @if(abs($difference) >= 0.01)
                        <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg">
                            <p class="text-sm text-amber-700 dark:text-amber-300">
                                <strong>{{ __('Warning') }}:</strong> {{ __('There is a difference between the statement balance and matched transactions. You may want to review the transactions before completing.') }}
                            </p>
                        </div>
                    @else
                        <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
                            <p class="text-sm text-green-700 dark:text-green-300">
                                ✓ {{ __('The reconciliation is balanced and ready to complete.') }}
                            </p>
                        </div>
                    @endif

                    <div class="mt-4">
                        <label class="erp-label">{{ __('Notes (Optional)') }}</label>
                        <textarea wire:model="notes" rows="3" class="erp-input" placeholder="{{ __('Add any notes about this reconciliation...') }}"></textarea>
                    </div>
                </div>
            </div>
        @endif

        {{-- Navigation Buttons --}}
        <div class="flex justify-between mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div>
                @if($currentStep > 1)
                    <button type="button" wire:click="previousStep" class="erp-btn erp-btn-secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        {{ __('Previous') }}
                    </button>
                @else
                    <a href="{{ route('banking.index') }}" class="erp-btn erp-btn-secondary">{{ __('Cancel') }}</a>
                @endif
            </div>

            <div>
                @if($currentStep < $totalSteps)
                    <button type="button" wire:click="nextStep" class="erp-btn erp-btn-primary">
                        {{ __('Next') }}
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                @else
                    <button type="button" wire:click="complete" class="erp-btn erp-btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ __('Complete Reconciliation') }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
