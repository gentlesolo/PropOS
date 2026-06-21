<div class="flex gap-0 h-full">

    {{-- ══ Main column ══════════════════════════════════════════════════════════ --}}
    <div class="flex-1 min-w-0 overflow-auto p-6">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Email Templates</h1>
                <p class="text-sm text-text-secondary mt-0.5">Manage reusable email templates across categories</p>
            </div>
            <button wire:click="openCreate"
                class="disabled:opacity-70 disabled:cursor-not-allowed relative inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors" wire:loading.attr="disabled" wire:target="openCreate">
                <span wire:loading.remove wire:target="openCreate"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Template</span>
                <span wire:loading wire:target="openCreate" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
        </div>

        {{-- Create / Edit form --}}
        @if($showForm)
        <div class="bg-surface-card rounded-2xl border border-brand-200 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-text-primary">{{ $editingId ? 'Edit' : 'Create' }} Template</h2>
                <button wire:click="cancelForm" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-text-tertiary hover:text-text-secondary text-xl leading-none" wire:loading.attr="disabled" wire:target="cancelForm">
                <span wire:loading.remove wire:target="cancelForm">&times;</span>
                <span wire:loading wire:target="cancelForm" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
            <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Template Name *</label>
                    <input wire:model="name" type="text"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page"
                        placeholder="Welcome Email">
                    @error('name') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Category *</label>
                    <select wire:model="category"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        @foreach(['lead','listing','offer','transaction','lease','marketing','system'] as $cat)
                        <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-text-secondary mb-1">Subject Line *</label>
                    <input wire:model="subject" type="text"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page"
                        placeholder="Welcome to @{{agency_name}}!">
                    @error('subject') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    <p class="text-xs text-text-tertiary mt-1">Use &#123;&#123;variable_name&#125;&#125; for dynamic content</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-text-secondary mb-1">Email Body (HTML) *</label>
                    <textarea wire:model="body_html" rows="10"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page resize-none font-mono"
                        placeholder="<p>Dear @{{contact_name}},</p><p>...</p>"></textarea>
                    @error('body_html') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-text-secondary mb-1">Plain Text Body <span class="font-normal text-text-tertiary">(optional fallback)</span></label>
                    <textarea wire:model="body_text" rows="4"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page resize-none"
                        placeholder="Plain-text version for email clients that don't render HTML…"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-text-secondary mb-1">Available Variables <span class="font-normal text-text-tertiary">(comma-separated)</span></label>
                    <input wire:model="variables_raw" type="text"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page"
                        placeholder="contact_name, agency_name, property_address, price">
                    <p class="text-xs text-text-tertiary mt-1">Documents which variables this template supports.</p>
                </div>
                <div class="md:col-span-2 flex gap-3 pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">
                        {{ $editingId ? 'Update' : 'Create' }} Template
                    </button>
                    <button type="button" wire:click="cancelForm"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="cancelForm">
                <span wire:loading.remove wire:target="cancelForm">Cancel</span>
                <span wire:loading wire:target="cancelForm" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </div>
            </form>
        </div>
        @endif

        {{-- Search & category filter --}}
        <div class="flex flex-wrap gap-3 mb-4">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search templates…"
                class="flex-1 min-w-48 rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <select wire:model.live="categoryFilter"
                class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                <option value="">All Categories</option>
                @foreach(['lead','listing','offer','transaction','lease','marketing','system'] as $cat)
                <option value="{{ $cat }}">{{ ucfirst($cat) }} ({{ $totalByCategory[$cat] ?? 0 }})</option>
                @endforeach
            </select>
        </div>

        {{-- Templates grouped by category --}}
        @forelse($grouped as $cat => $catTemplates)
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-text-secondary uppercase tracking-wider mb-3 px-1">
                {{ ucfirst($cat) }} ({{ $catTemplates->count() }})
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($catTemplates as $template)
                <div class="bg-surface-card rounded-2xl {{ ($showPreview && $previewId === $template->id) ? 'border-2 border-brand-400' : 'border border-border-default' }} p-4 flex flex-col">
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

                    @if(!empty($template->available_variables))
                    <div class="flex flex-wrap gap-1 mb-2">
                        @foreach(array_slice($template->available_variables, 0, 4) as $var)
                        <span class="text-xs px-1.5 py-0.5 bg-surface-hover rounded font-mono text-text-tertiary">&#123;&#123;{{ $var }}&#125;&#125;</span>
                        @endforeach
                        @if(count($template->available_variables) > 4)
                        <span class="text-xs text-text-tertiary self-center">+{{ count($template->available_variables) - 4 }}</span>
                        @endif
                    </div>
                    @endif

                    <div class="flex gap-1 mt-auto pt-3 flex-wrap">
                        <button wire:click="openPreview({{ $template->id }})"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative flex-1 text-xs py-1.5 border border-border-default rounded-lg text-text-secondary hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="openPreview">
                <span wire:loading.remove wire:target="openPreview">Preview</span>
                <span wire:loading wire:target="openPreview" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="openEdit({{ $template->id }})"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative flex-1 text-xs py-1.5 border border-border-default rounded-lg text-text-secondary hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="openEdit">
                <span wire:loading.remove wire:target="openEdit">Edit</span>
                <span wire:loading wire:target="openEdit" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="toggleActive({{ $template->id }})"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative flex-1 text-xs py-1.5 border border-border-default rounded-lg text-text-secondary hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="toggleActive">
                <span wire:loading.remove wire:target="toggleActive">{{ $template->is_active ? 'Disable' : 'Enable' }}</span>
                <span wire:loading wire:target="toggleActive" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="duplicate({{ $template->id }})"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs py-1.5 px-2.5 border border-border-default rounded-lg text-text-secondary hover:bg-surface-hover transition-colors" title="Duplicate" wire:loading.attr="disabled" wire:target="duplicate">
                <span wire:loading.remove wire:target="duplicate">Dup</span>
                <span wire:loading wire:target="duplicate" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="delete({{ $template->id }})" wire:confirm="Delete this template?"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs py-1.5 px-2.5 border border-danger-200 rounded-lg text-danger-600 hover:bg-danger-50 transition-colors" wire:loading.attr="disabled" wire:target="delete">
                <span wire:loading.remove wire:target="delete">Del</span>
                <span wire:loading wire:target="delete" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @empty
        <div class="bg-surface-card rounded-2xl border border-border-default p-12 text-center">
            <p class="text-text-tertiary text-sm">No email templates yet.
                @if($search || $categoryFilter)
                <button wire:click="$set('search',''); $set('categoryFilter','')" class="disabled:opacity-70 disabled:cursor-not-allowed relative ml-1 text-brand-600 underline" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Clear filters</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                @else
                Create your first one above.
                @endif
            </p>
        </div>
        @endforelse
    </div>

    {{-- ══ Preview panel ════════════════════════════════════════════════════════ --}}
    @if($showPreview && $previewTemplate)
    <div class="border-l border-border-default bg-surface-card overflow-y-auto flex-shrink-0" style="width:24rem">
        <div class="p-5">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <div class="font-semibold text-text-primary text-sm">{{ $previewTemplate->name }}</div>
                    <div class="text-xs text-text-tertiary capitalize mt-0.5">{{ $previewTemplate->category }}</div>
                </div>
                <button wire:click="closePreview" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-text-tertiary hover:text-text-secondary text-xl leading-none ml-2" wire:loading.attr="disabled" wire:target="closePreview">
                <span wire:loading.remove wire:target="closePreview">&times;</span>
                <span wire:loading wire:target="closePreview" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>

            <div class="bg-surface-card rounded-xl border border-border-default p-3 mb-3">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1">Subject</div>
                <p class="text-sm text-text-primary">{{ $previewTemplate->subject }}</p>
            </div>

            @if(!empty($previewTemplate->available_variables))
            <div class="bg-surface-card rounded-xl border border-border-default p-3 mb-3">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Variables</div>
                <div class="flex flex-wrap gap-1">
                    @foreach($previewTemplate->available_variables as $var)
                    <span class="text-xs px-2 py-0.5 bg-surface-hover rounded font-mono text-text-secondary">&#123;&#123;{{ $var }}&#125;&#125;</span>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="bg-surface-card rounded-xl border border-border-default p-3 mb-3">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">HTML Preview</div>
                <div class="bg-white rounded-lg p-3 text-xs border border-border-default/40 overflow-auto max-h-96">
                    {!! $previewTemplate->body_html !!}
                </div>
            </div>

            @if($previewTemplate->body_text)
            <div class="bg-surface-card rounded-xl border border-border-default p-3 mb-3">
                <div class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Plain Text</div>
                <pre class="text-xs text-text-secondary whitespace-pre-wrap leading-relaxed">{{ $previewTemplate->body_text }}</pre>
            </div>
            @endif

            <div class="space-y-2 mt-4">
                <button wire:click="openEdit({{ $previewTemplate->id }})"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="openEdit">
                <span wire:loading.remove wire:target="openEdit">Edit Template</span>
                <span wire:loading wire:target="openEdit" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button wire:click="duplicate({{ $previewTemplate->id }})"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="duplicate">
                <span wire:loading.remove wire:target="duplicate">Duplicate</span>
                <span wire:loading wire:target="duplicate" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button wire:click="toggleActive({{ $previewTemplate->id }})"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-hover transition-colors" wire:loading.attr="disabled" wire:target="toggleActive">
                <span wire:loading.remove wire:target="toggleActive">{{ $previewTemplate->is_active ? 'Disable Template' : 'Enable Template' }}</span>
                <span wire:loading wire:target="toggleActive" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
        </div>
    </div>
    @endif

</div>



