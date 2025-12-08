<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Ticket Categories') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage ticket categories and subcategories') }}</p>
        </div>
        <button wire:click="openModal" class="erp-btn erp-btn-primary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('New Category') }}
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Name') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Parent') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Default Assignee') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Tickets') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-slate-500 uppercase">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($categories as $category)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                @if($category->color)
                                    <span class="w-3 h-3 rounded-full" style="background-color: {{ $category->color }}"></span>
                                @endif
                                <span class="font-medium">{{ $category->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm">{{ $category->parent?->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $category->defaultAssignee?->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $category->tickets_count }}</td>
                        <td class="px-6 py-4">
                            <button wire:click="toggleActive({{ $category->id }})" class="px-2 py-1 text-xs font-semibold rounded 
                                {{ $category->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-800' }}">
                                {{ $category->is_active ? __('Active') : __('Inactive') }}
                            </button>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex items-center gap-2">
                                <button wire:click="openModal({{ $category->id }})" class="text-blue-600 hover:text-blue-900">{{ __('Edit') }}</button>
                                <button wire:click="delete({{ $category->id }})" wire:confirm="{{ __('Are you sure?') }}" class="text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">{{ __('No categories found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">{{ $categories->links() }}</div>
    </div>

    {{-- Modal --}}
    @if($showModal)
        <div class="fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-slate-800 mb-6">{{ $editingId ? __('Edit Category') : __('New Category') }}</h2>
                    <form wire:submit="save" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Name (English)') }} <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="name" class="erp-input w-full" required>
                                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Name (Arabic)') }}</label>
                                <input type="text" wire:model="name_ar" class="erp-input w-full" dir="rtl">
                                @error('name_ar') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Description') }}</label>
                            <textarea wire:model="description" rows="3" class="erp-input w-full"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Parent Category') }}</label>
                                <select wire:model="parent_id" class="erp-input w-full">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach($parentCategories as $parent)
                                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Default Assignee') }}</label>
                                <select wire:model="default_assignee_id" class="erp-input w-full">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Color') }}</label>
                                <input type="color" wire:model="color" class="erp-input w-full h-10">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Sort Order') }}</label>
                                <input type="number" wire:model="sort_order" class="erp-input w-full" min="0">
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" wire:model="is_active" class="rounded">
                                    <span class="text-sm">{{ __('Active') }}</span>
                                </label>
                            </div>
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
