{{-- resources/views/livewire/admin/users/form.blade.php --}}
<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800">
                {{ $userId ? __('Edit User') : __('Create User') }}
            </h1>
            <p class="text-sm text-slate-500">
                {{ __('Basic information and branch.') }}
            </p>
        </div>
    </div>

    <form wire:submit.prevent="save" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">
                    {{ __('Name') }}
                </label>
                <input type="text" wire:model="form.name" class="erp-input">
                @error('form.name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">
                    {{ __('Email') }}
                </label>
                <input type="email" wire:model="form.email" class="erp-input">
                @error('form.email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">
                    {{ __('Phone') }}
                </label>
                <input type="text" wire:model="form.phone" class="erp-input">
                @error('form.phone')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">
                    {{ __('Username') }}
                </label>
                <input type="text" wire:model="form.username" class="erp-input">
                @error('form.username')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">
                    {{ __('Branch') }}
                </label>
                <select wire:model="form.branch_id" class="erp-input">
                    <option value="">{{ __('Select branch') }}</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                @error('form.branch_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">
                    {{ __('Language') }}
                </label>
                <select wire:model="form.locale" class="erp-input">
                    <option value="ar">{{ __('Arabic') }}</option>
                    <option value="en">{{ __('English') }}</option>
                </select>
                @error('form.locale')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">
                    {{ __('Timezone') }}
                </label>
                <select wire:model="form.timezone" class="erp-input">
                    <option value="Africa/Cairo">Africa/Cairo (EET, UTC+2)</option>
                    <option value="Asia/Riyadh">Asia/Riyadh (AST, UTC+3)</option>
                    <option value="Asia/Dubai">Asia/Dubai (GST, UTC+4)</option>
                    <option value="Asia/Kuwait">Asia/Kuwait (AST, UTC+3)</option>
                    <option value="Asia/Bahrain">Asia/Bahrain (AST, UTC+3)</option>
                    <option value="Asia/Qatar">Asia/Qatar (AST, UTC+3)</option>
                    <option value="Asia/Amman">Asia/Amman (EET, UTC+2)</option>
                    <option value="Asia/Beirut">Asia/Beirut (EET, UTC+2)</option>
                    <option value="Asia/Damascus">Asia/Damascus (EET, UTC+2)</option>
                    <option value="Asia/Jerusalem">Asia/Jerusalem (IST, UTC+2)</option>
                    <option value="Europe/London">Europe/London (GMT, UTC+0)</option>
                    <option value="Europe/Paris">Europe/Paris (CET, UTC+1)</option>
                    <option value="America/New_York">America/New_York (EST, UTC-5)</option>
                    <option value="America/Los_Angeles">America/Los_Angeles (PST, UTC-8)</option>
                    <option value="UTC">UTC (UTC+0)</option>
                </select>
                @error('form.timezone')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">
                    {{ $userId ? __('New Password (optional)') : __('Password') }}
                </label>
                <input type="password" wire:model="form.password" class="erp-input">
                @error('form.password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">
                    {{ __('Confirm Password') }}
                </label>
                <input type="password" wire:model="form.password_confirmation" class="erp-input">
            </div>

            <div class="space-y-1">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 mt-6">
                    <input type="checkbox" wire:model="form.is_active"
                           class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                    <span>{{ __('Active') }}</span>
                </label>
                @error('form.is_active')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>


        {{-- Roles (web guard) --}}
        @if (!empty($availableRoles))
            <div class="rounded-2xl border border-slate-200 bg-white/80 p-3 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800 mb-2">
                    {{ __('Roles & Permissions') }}
                </h2>
                <p class="text-xs text-slate-500 mb-2">
                    {{ __('Select one or more roles for this user (web guard).') }}
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach ($availableRoles as $role)
                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="checkbox"
                                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                   value="{{ $role['id'] }}"
                                   wire:model="selectedRoles">
                            <span>{{ $role['name'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif


        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn-primary">
                {{ $userId ? __('Save changes') : __('Create user') }}
            </button>
        </div>
    </form>
</div>
