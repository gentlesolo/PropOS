<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary flex items-center gap-3">
                <span class="text-3xl">💬</span> WhatsApp Business
            </h1>
            <p class="mt-2 text-text-secondary">Send templated messages, track delivery, and manage your WhatsApp contact flow.</p>
        </div>
        <button wire:click="$toggle('showCompose')" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 bg-success-600 text-white rounded-xl text-sm font-bold hover:bg-success-700 transition-colors" wire:loading.attr="disabled" wire:target="$toggle">
                <span wire:loading.remove wire:target="$toggle">{{ $showCompose ? 'Cancel' : '+ New Message' }}</span>
                <span wire:loading wire:target="$toggle" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-4 mb-8">
        @foreach(['total_sent' => 'Sent', 'delivered' => 'Delivered', 'read' => 'Read', 'failed' => 'Failed'] as $key => $label)
        <div class="bg-surface-card p-5 rounded-2xl border border-border-default text-center">
            <p class="text-2xl font-black text-text-primary">{{ $stats[$key] }}</p>
            <p class="text-xs text-text-secondary mt-1 font-medium">{{ $label }}</p>
        </div>
        @endforeach
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 border-b border-border-default mb-6">
        @foreach(['messages' => 'Messages', 'templates' => 'Templates'] as $tab => $label)
        <button wire:click="$set('activeTab', '{{ $tab }}')"
            class="disabled:opacity-70 disabled:cursor-not-allowed relative px-5 py-2.5 border-b-2 font-bold text-sm transition-colors
            {{ $activeTab === $tab ? 'border-success-500 text-success-600' : 'border-transparent text-text-secondary hover:text-text-primary' }}" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">{{ $label }}</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
        @endforeach
    </div>

    <!-- Compose Form -->
    @if($showCompose && $activeTab === 'messages')
    <div class="bg-surface-card rounded-2xl border border-success-200 bg-success-50/30 p-5 mb-6">
        <h3 class="text-sm font-bold text-text-primary mb-4">Compose Message</h3>
        <form wire:submit.prevent="sendMessage" class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">To (Phone Number) *</label>
                    <input wire:model.defer="compose_to" type="text" placeholder="+234 800 000 0000" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-success-500 focus:ring-1 focus:ring-success-500">
                    @error('compose_to') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Contact (optional)</label>
                    <select wire:model.defer="compose_contact_id" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-success-500 focus:ring-1 focus:ring-success-500">
                        <option value="">No linked contact</option>
                        @foreach($contacts as $c)
                        <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }} — {{ $c->phone }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Template</label>
                <select wire:model="compose_template_id" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-success-500 focus:ring-1 focus:ring-success-500">
                    <option value="">Custom message (no template)</option>
                    @foreach($templates->where('status', 'approved') as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Message *</label>
                <textarea wire:model.defer="compose_body" rows="3" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-success-500 focus:ring-1 focus:ring-success-500 resize-none"></textarea>
                @error('compose_body') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2 bg-success-600 text-white rounded-xl text-sm font-bold hover:bg-success-700 transition-colors">
                    <span wire:loading.remove wire:target="sendMessage">Send Message</span>
                    <span wire:loading wire:target="sendMessage">Sending...</span>
                </button>
            </div>
        </form>
    </div>
    @endif

    @if($activeTab === 'messages')
    <!-- Message History -->
    <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead><tr class="bg-surface-sunken/50 border-b border-border-default/40">
                    <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">To</th>
                    <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Message</th>
                    <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Direction</th>
                    <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Status</th>
                    <th class="py-3 px-5 text-xs font-bold text-text-tertiary uppercase tracking-wider">Sent</th>
                </tr></thead>
                <tbody class="divide-y divide-border-default/40">
                    @forelse($messages as $msg)
                    <tr class="hover:bg-surface-raised/20 transition-colors">
                        <td class="py-3 px-5">
                            <p class="text-sm font-bold text-text-primary">{{ $msg->contact?->first_name ?? $msg->to_number }}</p>
                            <p class="text-xs text-text-secondary">{{ $msg->to_number }}</p>
                        </td>
                        <td class="py-3 px-5 max-w-xs">
                            <p class="text-sm text-text-secondary truncate">{{ Str::limit($msg->body, 60) }}</p>
                        </td>
                        <td class="py-3 px-5">
                            <span class="text-xs font-bold {{ $msg->direction === 'inbound' ? 'text-info-600' : 'text-text-secondary' }} uppercase">{{ $msg->direction }}</span>
                        </td>
                        <td class="py-3 px-5">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                @if($msg->status === 'delivered' || $msg->status === 'read') bg-success-100 text-success-700
                                @elseif($msg->status === 'sent') bg-info-100 text-info-700
                                @elseif($msg->status === 'failed') bg-danger-100 text-danger-700
                                @else bg-surface-sunken text-text-secondary @endif">
                                {{ $msg->status }}
                            </span>
                        </td>
                        <td class="py-3 px-5 text-xs text-text-secondary">{{ $msg->created_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-10 text-center text-sm text-text-secondary">No messages yet. Send your first message above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-border-default">{{ $messages->links() }}</div>
    </div>

    @else
    <!-- Templates -->
    @if($showTemplateForm)
    <div class="bg-surface-card rounded-2xl border border-border-default p-5 mb-5">
        <h3 class="text-sm font-bold text-text-primary mb-4">New Template</h3>
        <form wire:submit.prevent="saveTemplate" class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Template Name *</label>
                    <input wire:model.defer="template_name" type="text" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Category</label>
                    <select wire:model.defer="template_category" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        <option value="marketing">Marketing</option>
                        <option value="utility">Utility</option>
                        <option value="authentication">Authentication</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Message Body * <span class="text-text-tertiary">(use {{1}}, {{2}} for variables)</span></label>
                <textarea wire:model.defer="template_body" rows="4" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page resize-none"></textarea>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" wire:click="$set('showTemplateForm', false)" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button type="submit" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled">
                <span wire:loading.remove>Save Template</span>
                <span wire:loading class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
        </form>
    </div>
    @else
    <div class="flex justify-end mb-4">
        <button wire:click="$set('showTemplateForm', true)" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">+ New Template</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($templates as $template)
        <div class="bg-surface-card rounded-2xl border border-border-default p-5">
            <div class="flex items-start justify-between mb-2">
                <h3 class="text-sm font-bold text-text-primary">{{ $template->name }}</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                    @if($template->status === 'approved') bg-success-100 text-success-700
                    @elseif($template->status === 'pending_approval') bg-warning-100 text-warning-700
                    @else bg-surface-sunken text-text-secondary @endif">
                    {{ str_replace('_', ' ', $template->status) }}
                </span>
            </div>
            <p class="text-xs text-text-secondary mb-3 capitalize">{{ $template->category }}</p>
            <div class="p-3 bg-surface-sunken/40 rounded-xl border border-border-default/40 mb-3">
                <p class="text-xs text-text-primary leading-relaxed">{{ Str::limit($template->body, 120) }}</p>
            </div>
            <button wire:click="useTemplate({{ $template->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-1.5 border border-success-300 text-success-600 rounded-xl text-xs font-bold hover:bg-success-50 transition-colors" wire:loading.attr="disabled" wire:target="useTemplate">
                <span wire:loading.remove wire:target="useTemplate">Use Template</span>
                <span wire:loading wire:target="useTemplate" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
        </div>
        @empty
        <div class="col-span-3 text-center py-10 text-sm text-text-secondary">No templates yet. Create one above.</div>
        @endforelse
    </div>
    @endif
</div>



