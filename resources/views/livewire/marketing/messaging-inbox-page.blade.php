<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Messaging Inbox</h1>
            <p class="text-sm text-text-secondary mt-0.5">Unified email, SMS, and WhatsApp conversation history</p>
        </div>
        <button wire:click="$toggle('showCompose')" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Compose
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="glass-panel rounded-2xl border border-border-default/60 p-4 text-center">
            <div class="text-2xl font-bold text-text-primary">{{ $stats['emails_sent'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Emails Sent</div>
        </div>
        <div class="glass-panel rounded-2xl border border-border-default/60 p-4 text-center">
            <div class="text-2xl font-bold text-text-primary">{{ $stats['emails_opened'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Emails Opened</div>
        </div>
        <div class="glass-panel rounded-2xl border border-border-default/60 p-4 text-center">
            <div class="text-2xl font-bold text-text-primary">{{ $stats['sms_sent'] }}</div>
            <div class="text-xs text-text-secondary mt-1">SMS Sent</div>
        </div>
        <div class="glass-panel rounded-2xl border border-border-default/60 p-4 text-center">
            <div class="text-2xl font-bold text-text-primary">{{ $stats['sms_inbound'] }}</div>
            <div class="text-xs text-text-secondary mt-1">SMS Received</div>
        </div>
    </div>

    @if($showCompose)
    <div class="glass-panel rounded-2xl border border-border-default/60 p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">Compose Message</h2>
        <form wire:submit.prevent="sendMessage" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Channel</label>
                    <select wire:model.live="composeChannel" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        <option value="email">Email</option>
                        <option value="sms">SMS</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">To ({{ $composeChannel === 'email' ? 'Email address' : 'Phone number' }}) *</label>
                    <input wire:model="compose_to" type="{{ $composeChannel === 'email' ? 'email' : 'tel' }}" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary" placeholder="{{ $composeChannel === 'email' ? 'name@example.com' : '+2347012345678' }}">
                    @error('compose_to') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                @if($composeChannel === 'email')
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Subject *</label>
                    <input wire:model="compose_subject" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    @error('compose_subject') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                @endif
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Message *</label>
                <textarea wire:model="compose_body" rows="4" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none" placeholder="Type your message…"></textarea>
                @error('compose_body') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div class="flex gap-3">
                <button type="submit" class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
                    <span wire:loading.remove wire:target="sendMessage">Send</span>
                    <span wire:loading wire:target="sendMessage">Sending…</span>
                </button>
                <button type="button" wire:click="$set('showCompose', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
            </div>
        </form>
    </div>
    @endif

    <!-- Channel Tabs -->
    <div class="flex gap-1 mb-4 p-1 bg-surface-hover/50 rounded-xl w-fit">
        @foreach(['all'=>'All','email'=>'Email','sms'=>'SMS','whatsapp'=>'WhatsApp'] as $ch=>$label)
        <button wire:click="$set('channel', '{{ $ch }}')" class="px-4 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $channel === $ch ? 'bg-white text-text-primary shadow-sm dark:bg-surface-card' : 'text-text-secondary hover:text-text-primary' }}">{{ $label }}</button>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Message List -->
        <div class="xl:col-span-2 space-y-3">
            @if($channel !== 'sms' && $channel !== 'whatsapp')
            <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
                <div class="px-4 py-3 border-b border-border-default bg-surface-hover/30 flex items-center gap-2">
                    <svg class="w-4 h-4 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span class="text-sm font-semibold text-text-primary">Email ({{ $emailLogs->count() }})</span>
                </div>
                <div class="divide-y divide-border-default">
                    @forelse($emailLogs as $log)
                    @php $sc = $log->statusColor; @endphp
                    <div class="px-4 py-3 hover:bg-surface-hover/30 transition-colors">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-text-primary truncate">{{ $log->subject }}</p>
                                <p class="text-xs text-text-secondary">To: {{ $log->to_name ?? $log->to_email }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-50 text-{{ $sc }}-700 border border-{{ $sc }}-200">{{ ucfirst($log->status) }}</span>
                                <span class="text-xs text-text-tertiary">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="px-4 py-8 text-center text-text-tertiary text-sm">No emails sent yet.</div>
                    @endforelse
                </div>
            </div>
            @endif

            @if($channel !== 'email' && $channel !== 'whatsapp')
            <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
                <div class="px-4 py-3 border-b border-border-default bg-surface-hover/30 flex items-center gap-2">
                    <svg class="w-4 h-4 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    <span class="text-sm font-semibold text-text-primary">SMS ({{ $smsMessages->count() }})</span>
                </div>
                <div class="divide-y divide-border-default">
                    @forelse($smsMessages as $msg)
                    <div class="px-4 py-3 hover:bg-surface-hover/30 transition-colors">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-text-primary line-clamp-2">{{ $msg->body }}</p>
                                <p class="text-xs text-text-secondary mt-0.5">{{ $msg->direction === 'outbound' ? 'To: '.$msg->to_number : 'From: '.$msg->from_number }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @php $sc = $msg->statusColor; @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-50 text-{{ $sc }}-700 border border-{{ $sc }}-200">{{ ucfirst($msg->status) }}</span>
                                <span class="text-xs text-text-tertiary">{{ $msg->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="px-4 py-8 text-center text-text-tertiary text-sm">No SMS messages yet.</div>
                    @endforelse
                </div>
            </div>
            @endif
        </div>

        <!-- Contact Panel -->
        <div>
            <div class="glass-panel rounded-2xl border border-border-default/60 p-4">
                <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3">Quick Contact</p>
                <div class="space-y-1.5">
                    @foreach($contacts->take(20) as $c)
                    <button wire:click="selectContact({{ $c->id }})" class="w-full text-left px-3 py-2 rounded-lg hover:bg-surface-hover/50 transition-colors {{ $selectedContactId === $c->id ? 'bg-brand-50 border border-brand-200' : '' }}">
                        <div class="text-sm font-medium text-text-primary">{{ $c->first_name }} {{ $c->last_name }}</div>
                        <div class="text-xs text-text-tertiary">{{ $c->email ?? $c->phone ?? '' }}</div>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
