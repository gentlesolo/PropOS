<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Email Templates</h1>
            <p class="text-sm text-text-secondary mt-0.5">Manage reusable email templates across categories</p>
        </div>
        <button wire:click="openCreate" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Template
        </button>
    </div>

    @if($showCreateForm)
    <div class="glass-panel rounded-2xl border border-border-default/60 p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">{{ $editingId ? 'Edit' : 'Create' }} Template</h2>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Template Name *</label>
                    <input wire:model="name" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary" placeholder="Welcome Email">
                    @error('name') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Category *</label>
                    <select wire:model="category" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        @foreach(['lead','listing','offer','transaction','lease','marketing','system'] as $cat)
                        <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-text-secondary mb-1">Subject Line *</label>
                    <input wire:model="subject" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary" placeholder="Welcome to {{agency_name}}!">
                    @error('subject') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    <p class="text-xs text-text-tertiary mt-1">Use {{variable_name}} for dynamic content</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-text-secondary mb-1">Email Body (HTML) *</label>
                    <textarea wire:model="body_html" rows="10" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none font-mono" placeholder="<p>Dear {{contact_name}},</p><p>...</p>"></textarea>
                    @error('body_html') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">{{ $editingId ? 'Update' : 'Create' }} Template</button>
                <button type="button" wire:click="$set('showCreateForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    <!-- Templates by Category -->
    @forelse($grouped as $category => $templates)
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-text-secondary uppercase tracking-wider mb-3 px-1">{{ ucfirst($category) }} ({{ $templates->count() }})</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($templates as $template)
            <div class="glass-panel rounded-2xl border border-border-default/60 p-4">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div class="min-w-0">
                        <p class="font-medium text-text-primary text-sm truncate">{{ $template->name }}</p>
                        <p class="text-xs text-text-secondary truncate mt-0.5">{{ $template->subject }}</p>
                    </div>
                    <div class="shrink-0">
                        @if($template->is_active)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success-50 text-success-700 border border-success-200">Active</span>
                        @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-secondary-50 text-secondary-600 border border-secondary-200">Inactive</span>
                        @endif
                    </div>
                </div>
                <div class="flex gap-2 mt-3">
                    <button wire:click="openEdit({{ $template->id }})" class="flex-1 text-xs py-1.5 border border-border-default rounded-lg text-text-secondary hover:bg-surface-hover transition-colors">Edit</button>
                    <button wire:click="toggleActive({{ $template->id }})" class="flex-1 text-xs py-1.5 border border-border-default rounded-lg text-text-secondary hover:bg-surface-hover transition-colors">{{ $template->is_active ? 'Disable' : 'Enable' }}</button>
                    <button wire:click="delete({{ $template->id }})" wire:confirm="Delete this template?" class="text-xs py-1.5 px-2.5 border border-danger-200 rounded-lg text-danger-600 hover:bg-danger-50 transition-colors">Del</button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="glass-panel rounded-2xl border border-border-default/60 p-12 text-center">
        <p class="text-text-tertiary text-sm">No email templates yet. Create your first one above.</p>
    </div>
    @endforelse
</div>
