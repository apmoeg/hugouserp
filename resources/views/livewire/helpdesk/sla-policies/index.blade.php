<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('SLA Policies') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage Service Level Agreement policies') }}</p>
        </div>
        <button wire:click="openModal" class="erp-btn erp-btn-primary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('New SLA Policy') }}
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Name') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Response Time') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Resolution Time') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Business Hours') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($policies as $policy)
                    <tr>
                        <td class="px-6 py-4 font-medium">{{ $policy->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ $policy->getResponseTimeFormatted() }}</td>
                        <td class="px-6 py-4 text-sm">{{ $policy->getResolutionTimeFormatted() }}</td>
                        <td class="px-6 py-4 text-sm">{{ $policy->business_hours_only ? __('Yes') : __('No') }}</td>
                        <td class="px-6 py-4">
                            <button wire:click="toggleActive({{ $policy->id }})" class="px-2 py-1 text-xs font-semibold rounded 
                                {{ $policy->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-800' }}">
                                {{ $policy->is_active ? __('Active') : __('Inactive') }}
                            </button>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex items-center gap-2">
                                <button wire:click="openModal({{ $policy->id }})" class="text-blue-600 hover:text-blue-900">{{ __('Edit') }}</button>
                                <button wire:click="delete({{ $policy->id }})" wire:confirm="{{ __('Are you sure?') }}" class="text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">{{ __('No SLA policies found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">{{ $policies->links() }}</div>
    </div>

    {{-- Modal --}}
    @if($showModal)
        <div class="fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-slate-800 mb-6">{{ $editingId ? __('Edit SLA Policy') : __('New SLA Policy') }}</h2>
                    <form wire:submit="save" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Name') }} <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="name" class="erp-input w-full" required>
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Description') }}</label>
                            <textarea wire:model="description" rows="2" class="erp-input w-full"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Response Time (minutes)') }} <span class="text-red-500">*</span></label>
                                <input type="number" wire:model="response_time_minutes" class="erp-input w-full" min="1" required>
                                @error('response_time_minutes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Resolution Time (minutes)') }} <span class="text-red-500">*</span></label>
                                <input type="number" wire:model="resolution_time_minutes" class="erp-input w-full" min="1" required>
                                @error('resolution_time_minutes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 mb-4">
                                <input type="checkbox" wire:model.live="business_hours_only" class="rounded">
                                <span class="text-sm font-medium">{{ __('Business Hours Only') }}</span>
                            </label>
                        </div>
                        @if($business_hours_only)
                            <div class="grid grid-cols-2 gap-4 p-4 bg-slate-50 rounded-lg">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Business Hours Start') }} <span class="text-red-500">*</span></label>
                                    <input type="time" wire:model="business_hours_start" class="erp-input w-full" required>
                                    @error('business_hours_start') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Business Hours End') }} <span class="text-red-500">*</span></label>
                                    <input type="time" wire:model="business_hours_end" class="erp-input w-full" required>
                                    @error('business_hours_end') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Working Days') }}</label>
                                    <div class="grid grid-cols-7 gap-2">
                                        @foreach($daysOfWeek as $day => $name)
                                            <label class="flex items-center justify-center p-2 border rounded cursor-pointer {{ in_array($day, $working_days) ? 'bg-blue-100 border-blue-300' : 'bg-white' }}">
                                                <input type="checkbox" wire:model="working_days" value="{{ $day }}" class="sr-only">
                                                <span class="text-xs">{{ substr($name, 0, 3) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="is_active" class="rounded">
                                <span class="text-sm">{{ __('Active') }}</span>
                            </label>
                        </div>
                        <div class="flex items-center justify-end gap-3 pt-4 border-t">
                            <button type="button" wire:click="closeModal" class="erp-btn erp-btn-secondary">{{ __('Cancel') }}</button>
                            <button type="submit" class="erp-btn erp-btn-primary">{{ $editingId ? __('Update') : __('Create') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
