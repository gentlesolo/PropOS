<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary flex items-center gap-3">
                <span class="text-3xl">💬</span> WhatsApp Business
            </h1>
            <p class="mt-2 text-text-secondary">Send templated messages, track delivery, and manage your WhatsApp contact flow.</p>
        </div>
        <button wire:click="$toggle('showCompose')" class="px-4 py-2 bg-success-600 text-white rounded-xl text-sm font-bold hover:bg-success-700 transition-colors">
            {{ $showCompose ? 'Cancel' : '+ New Message' }}
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-4 mb-8">
        @foreach(['total_sent' => 'Sent', 'delivered' => 'Delivered', 'read' => 'Read', 'failed' => 'Failed'] as $key => $label)
        <div class="glass-panel p-5 rounded-2xl border border-border-default/60 text-center">
            <p class="text-2xl font-black text-text-primary">{{ $stats[$key] }}</p>
            <p class="text-xs text-text-secondary mt-1 font-medium">{{ $label }}</p>
        </div>
        @endforeach
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 border-b border-border-default/60 mb-6">
        @foreach(['messages' => 'Messages', 'templates' => 'Templates'] as $tab => $label)
        <button wire:click="$set('activeTab', '{{ $tab }}')"
            class="px-5 py-2.5 border-b-2 font-bold text-sm transition-colors
            {{ $activeTab === $tab ? 'border-success-500 text-success-600' : 'border-transparent text-text-secondary hover:text-text-primary' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>

    <!-- Compose Form -->
    @if($showCompose && $activeTab === 'messages')
    <div class="glass-panel rounded-2xl border border-success-200 bg-success-50/30 p-5 mb-6">
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
    <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden shadow-sm">
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
                                @else bg-slate-100 text-slate-600 @endif">
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
        <div class="px-5 py-3 border-t border-border-default/60">{{ $messages->links() }}</div>
    </div>

    @else
    <!-- Templates -->
    @if($showTemplateForm)
    <div class="glass-panel rounded-2xl border border-border-default/60 p-5 mb-5">
        <h3 class="text-sm font-bold text-text-primary mb-4">New Template</h3>
        <form wire:submit.prevent="saveTemplate" class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Template Name *</label>
                    <input wire:model.defer="template_name" type="text" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Category</label>
                    <select wire:model.defer="template_category" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        <option value="marketing">Marketing</option>
                        <option value="utility">Utility</option>
                        <option value="authentication">Authentication</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Message Body * <span class="text-text-tertiary">(use {{1}}, {{2}} for variables)</span></label>
                <textarea wire:model.defer="template_body" rows="4" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"></textarea>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" wire:click="$set('showTemplateForm', false)" class="px-4 py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors">Save Template</button>
            </div>
        </form>
    </div>
    @else
    <div class="flex justify-end mb-4">
        <button wire:click="$set('showTemplateForm', true)" class="px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors">+ New Template</button>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($templates as $template)
        <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
            <div class="flex items-start justify-between mb-2">
                <h3 class="text-sm font-bold text-text-primary">{{ $template->name }}</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                    @if($template->status === 'approved') bg-success-100 text-success-700
                    @elseif($template->status === 'pending_approval') bg-warning-100 text-warning-700
                    @else bg-slate-100 text-slate-600 @endif">
                    {{ str_replace('_', ' ', $template->status) }}
                </span>
            </div>
            <p class="text-xs text-text-secondary mb-3 capitalize">{{ $template->category }}</p>
            <div class="p-3 bg-surface-sunken/40 rounded-xl border border-border-default/40 mb-3">
                <p class="text-xs text-text-primary leading-relaxed">{{ Str::limit($template->body, 120) }}</p>
            </div>
            <button wire:click="useTemplate({{ $template->id }})" class="w-full py-1.5 border border-success-300 text-success-600 rounded-xl text-xs font-bold hover:bg-success-50 transition-colors">
                Use Template
            </button>
        </div>
        @empty
        <div class="col-span-3 text-center py-10 text-sm text-text-secondary">No templates yet. Create one above.</div>
        @endforelse
    </div>
    @endif
</div>
