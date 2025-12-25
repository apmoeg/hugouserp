<div class="container mx-auto px-4 py-6">
    <x-ui.page-header 
        title="{{ __('Bank Reconciliation') }}"
        subtitle="{{ __('Reconcile bank statements with transactions') }}"
    />

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mt-6 p-6">
        <form wire:submit.prevent="reconcile">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Bank Account') }}
                    </label>
                    <select wire:model="accountId" class="erp-input">
                        <option value="">{{ __('Select Account') }}</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Start Date') }}
                    </label>
                    <input type="date" wire:model="startDate" class="erp-input">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('End Date') }}
                    </label>
                    <input type="date" wire:model="endDate" class="erp-input">
                </div>
            </div>

            <div class="mt-6">
                <p class="text-gray-600 dark:text-gray-400 text-center py-8">
                    {{ __('Bank reconciliation feature coming soon') }}
                </p>
            </div>
        </form>
    </div>
</div>
