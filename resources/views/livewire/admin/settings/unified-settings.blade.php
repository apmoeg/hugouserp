<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('Settings') }}</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Manage your system settings') }}</p>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/20 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px overflow-x-auto">
                @foreach($tabs as $tabKey => $tabLabel)
                    <button
                        wire:click="switchTab('{{ $tabKey }}')"
                        class="px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                            {{ $activeTab === $tabKey
                                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                        {{ __($tabLabel) }}
                    </button>
                @endforeach
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            @if($activeTab === 'general')
                <form wire:submit="saveGeneral">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Company Name') }}
                            </label>
                            <input type="text" wire:model="company_name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('company_name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Company Email') }}
                            </label>
                            <input type="email" wire:model="company_email"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('company_email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Company Phone') }}
                            </label>
                            <input type="text" wire:model="company_phone"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('company_phone') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Timezone') }}
                            </label>
                            <select wire:model="timezone"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="UTC">UTC</option>
                                <option value="Africa/Cairo">Africa/Cairo</option>
                                <option value="Asia/Dubai">Asia/Dubai</option>
                                <option value="Asia/Riyadh">Asia/Riyadh</option>
                                <option value="Europe/London">Europe/London</option>
                                <option value="America/New_York">America/New_York</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Date Format') }}
                            </label>
                            <select wire:model="date_format"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="Y-m-d">YYYY-MM-DD</option>
                                <option value="d/m/Y">DD/MM/YYYY</option>
                                <option value="m/d/Y">MM/DD/YYYY</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Default Currency') }}
                            </label>
                            <select wire:model="default_currency"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">{{ __('Select Currency') }}</option>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->code }}">
                                        {{ $currency->code }} - {{ $currency->name }} ({{ $currency->symbol }})
                                    </option>
                                @endforeach
                            </select>
                            @error('default_currency') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'branch')
                <form wire:submit="saveBranch">
                    <div class="space-y-6">
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="multi_branch" id="multi_branch"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="multi_branch" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                {{ __('Enable Multi-Branch Mode') }}
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="require_branch_selection" id="require_branch"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="require_branch" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                {{ __('Require Branch Selection') }}
                            </label>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'currencies')
                <div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('Currency management has been moved to') }}</p>
                    <a href="{{ route('admin.currencies.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        {{ __('Manage Currencies') }}
                    </a>
                </div>

            @elseif($activeTab === 'rates')
                <div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('Exchange rate management has been moved to') }}</p>
                    <a href="{{ route('admin.currency-rates.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        {{ __('Manage Exchange Rates') }}
                    </a>
                </div>

            @elseif($activeTab === 'translations')
                <div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('Translation management') }}</p>
                    @if(class_exists('\App\Livewire\Admin\Settings\TranslationManager'))
                        <livewire:admin.settings.translation-manager />
                    @else
                        <p class="text-red-600">{{ __('Translation manager component not available') }}</p>
                    @endif
                </div>

            @elseif($activeTab === 'security')
                <form wire:submit="saveSecurity">
                    <div class="space-y-6">
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="require_2fa" id="require_2fa"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="require_2fa" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                {{ __('Require Two-Factor Authentication') }}
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Session Timeout (minutes)') }}
                            </label>
                            <input type="number" wire:model="session_timeout" min="5" max="1440"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('session_timeout') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="enable_audit_log" id="enable_audit"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="enable_audit" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                {{ __('Enable Audit Logging') }}
                            </label>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'backup')
                <form wire:submit="saveBackup">
                    <div class="space-y-6">
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="auto_backup" id="auto_backup"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="auto_backup" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                {{ __('Enable Automatic Backups') }}
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Backup Frequency') }}
                            </label>
                            <select wire:model="backup_frequency"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="daily">{{ __('Daily') }}</option>
                                <option value="weekly">{{ __('Weekly') }}</option>
                                <option value="monthly">{{ __('Monthly') }}</option>
                            </select>
                            @error('backup_frequency') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Retention Period (days)') }}
                            </label>
                            <input type="number" wire:model="backup_retention_days" min="1" max="365"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('backup_retention_days') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Backup Storage') }}
                            </label>
                            <select wire:model="backup_storage"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="local">{{ __('Local Storage') }}</option>
                                <option value="s3">{{ __('Amazon S3') }}</option>
                                <option value="ftp">{{ __('FTP Server') }}</option>
                            </select>
                            @error('backup_storage') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>

            @elseif($activeTab === 'advanced')
                <form wire:submit="saveAdvanced">
                    <div class="space-y-6">
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="enable_api" id="enable_api"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="enable_api" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                {{ __('Enable API Access') }}
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="enable_webhooks" id="enable_webhooks"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="enable_webhooks" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                {{ __('Enable Webhooks') }}
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Cache TTL (seconds)') }}
                            </label>
                            <input type="number" wire:model="cache_ttl" min="60" max="86400"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('cache_ttl') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
