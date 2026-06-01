<div class="flex gap-0 h-full">

    {{-- ══ Main column ══════════════════════════════════════════════════════════ --}}
    <div class="flex-1 min-w-0 overflow-auto p-6">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Messaging Inbox</h1>
                <p class="text-sm text-text-secondary mt-0.5">Unified email, SMS, and WhatsApp conversation history</p>
            </div>
            <button wire:click="openCompose()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Compose
            </button>
        </div>

        {{-- Stats --}}
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
                <div class="text-2xl font-bold text-text-primary">{{ $stats['wa_total'] }}</div>
                <div class="text-xs text-text-secondary mt-1">WhatsApp Total</div>
            </div>
        </div>

        {{-- Compose form --}}
        @if($showCompose)
        <div class="glass-panel rounded-2xl border border-brand-200 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-text-primary">Compose Message</h2>
                <button wire:click="$set('showCompose', false)" class="text-text-tertiary hover:text-text-secondary text-xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="sendMessage" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Channel</label>
                        <select wire:model.live="composeChannel"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="whatsapp">WhatsApp</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">
                            To ({{ $composeChannel === 'email' ? 'Email address' : 'Phone number' }}) *
                        </label>
                        <input wire:model="compose_to"
                            type="{{ $composeChannel === 'email' ? 'email' : 'tel' }}"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
                            placeholder="{{ $composeChannel === 'email' ? 'name@example.com' : '+2347012345678' }}">
                        @error('compose_to') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    </div>
                    @if($composeChannel === 'email')
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Subject *</label>
                        <input wire:model="compose_subject" type="text"
                            class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        @error('compose_subject') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    </div>
                    @endif
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Message *</label>
                    <textarea wire:model="compose_body" rows="4"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"
                        placeholder="Type your message…"></textarea>
                    @error('compose_body') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div class="flex gap-3">
                    <button type="submit" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">
                        <span wire:loading.remove wire:target="sendMessage">Send</span>
                        <span wire:loading wire:target="sendMessage">Sending…</span>
                    </button>
                    <button type="button" wire:click="$set('showCompose', false)"
                        class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Channel tabs + search --}}
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <div class="flex gap-1 p-1 bg-surface-hover/50 rounded-xl">
                @foreach(['all'=>'All','email'=>'Email','sms'=>'SMS','whatsapp'=>'WhatsApp'] as $ch=>$label)
                <button wire:click="$set('channel', '{{ $ch }}')"
                    class="px-4 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $channel === $ch ? 'bg-white text-text-primary shadow-sm dark:bg-surface-card' : 'text-text-secondary hover:text-text-primary' }}">
                    {{ $label }}
                </button>
                @endforeach
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search messages…"
                class="flex-1 min-w-48 rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
        </div>

        {{-- Main grid --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            {{-- Message lists --}}
            <div class="xl:col-span-2 space-y-3">

                {{-- Email section --}}
                @if(in_array($channel, ['all', 'email']))
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
                                    @if($log->contact)
                                    <button wire:click="selectContact({{ $log->contact->id }})" class="text-xs text-brand-600 hover:underline mt-0.5">{{ $log->contact->full_name }}</button>
                                    @endif
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

                {{-- SMS section --}}
                @if(in_array($channel, ['all', 'sms']))
                <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
                    <div class="px-4 py-3 border-b border-border-default bg-surface-hover/30 flex items-center gap-2">
                        <svg class="w-4 h-4 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        <span class="text-sm font-semibold text-text-primary">SMS ({{ $smsMessages->count() }})</span>
                    </div>
                    <div class="divide-y divide-border-default">
                        @forelse($smsMessages as $msg)
                        @php $sc = $msg->statusColor; @endphp
                        <div class="px-4 py-3 hover:bg-surface-hover/30 transition-colors">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-text-primary line-clamp-2">{{ $msg->body }}</p>
                                    <p class="text-xs text-text-secondary mt-0.5">{{ $msg->direction === 'outbound' ? 'To: '.$msg->to_number : 'From: '.$msg->from_number }}</p>
                                    @if($msg->contact)
                                    <button wire:click="selectContact({{ $msg->contact->id }})" class="text-xs text-brand-600 hover:underline mt-0.5">{{ $msg->contact->full_name }}</button>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
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

                {{-- WhatsApp section --}}
                @if(in_array($channel, ['all', 'whatsapp']))
                <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden">
                    <div class="px-4 py-3 border-b border-border-default bg-surface-hover/30 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-success-600" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12.004 2C6.48 2 2 6.48 2 12c0 1.85.504 3.584 1.379 5.074L2 22l5.09-1.355A9.942 9.942 0 0012.004 22C17.52 22 22 17.52 22 12S17.52 2 12.004 2zm0 18.18a8.162 8.162 0 01-4.17-1.144l-.297-.178-3.085.82.824-3.012-.196-.31A8.14 8.14 0 013.82 12c0-4.51 3.67-8.18 8.184-8.18 4.515 0 8.185 3.67 8.185 8.18s-3.67 8.18-8.185 8.18z"/></svg>
                            <span class="text-sm font-semibold text-text-primary">WhatsApp ({{ $whatsAppMessages->count() }})</span>
                        </div>
                        <button wire:click="openCompose()" class="text-xs px-2.5 py-1 bg-success-600 text-white rounded-lg hover:bg-success-700 transition-colors">+ New</button>
                    </div>
                    <div class="divide-y divide-border-default">
                        @forelse($whatsAppMessages as $msg)
                        <div class="px-4 py-3 hover:bg-surface-hover/30 transition-colors">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="text-xs px-1.5 py-0.5 rounded {{ $msg->direction === 'inbound' ? 'bg-success-50 text-success-700' : 'bg-brand-50 text-brand-700' }}">
                                            {{ $msg->direction === 'inbound' ? 'In' : 'Out' }}
                                        </span>
                                        @if($msg->contact)
                                        <button wire:click="selectContact({{ $msg->contact->id }})" class="text-xs font-medium text-brand-600 hover:underline">{{ $msg->contact->full_name }}</button>
                                        @else
                                        <span class="text-xs text-text-secondary">{{ $msg->to_number }}</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-text-primary line-clamp-2">{{ $msg->body }}</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @if($msg->status)
                                    <span class="text-xs text-text-tertiary capitalize">{{ $msg->status }}</span>
                                    @endif
                                    <span class="text-xs text-text-tertiary">{{ ($msg->sent_at ?? $msg->created_at)?->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="px-4 py-8 text-center text-text-tertiary text-sm">No WhatsApp messages yet.</div>
                        @endforelse
                    </div>
                </div>
                @endif

            </div>

            {{-- Contact panel / Thread --}}
            <div>
                @if($selectedContact)
                {{-- Thread view --}}
                <div class="glass-panel rounded-2xl border border-brand-200 overflow-hidden flex flex-col" style="max-height:70vh">
                    <div class="px-4 py-3 border-b border-border-default bg-brand-50/30 flex items-center justify-between shrink-0">
                        <div>
                            <p class="text-sm font-semibold text-text-primary">{{ $selectedContact->full_name }}</p>
                            <p class="text-xs text-text-tertiary">{{ $selectedContact->email ?? $selectedContact->phone ?? '' }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button wire:click="openCompose({{ $selectedContact->id }})" class="text-xs px-2.5 py-1 bg-brand-primary text-white rounded-lg hover:bg-brand-hover transition-colors">Reply</button>
                            <button wire:click="closeThread" class="text-text-tertiary hover:text-text-secondary text-xl leading-none">&times;</button>
                        </div>
                    </div>
                    <div class="overflow-y-auto flex-1 p-3 space-y-2">
                        @forelse($thread as $msg)
                        <div class="flex {{ $msg['direction'] === 'outbound' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[85%] rounded-xl px-3 py-2 text-xs
                                {{ $msg['direction'] === 'outbound' ? 'bg-brand-primary text-white' : 'bg-surface-hover text-text-primary' }}">
                                <div class="flex items-center gap-1.5 mb-1 opacity-75">
                                    @if($msg['type'] === 'email')
                                    <span>Email</span>
                                    @elseif($msg['type'] === 'sms')
                                    <span>SMS</span>
                                    @else
                                    <span>WhatsApp</span>
                                    @endif
                                    @if($msg['status'])
                                    <span>· {{ ucfirst($msg['status']) }}</span>
                                    @endif
                                </div>
                                <p class="leading-relaxed">{{ $msg['body'] }}</p>
                                <p class="mt-1 opacity-60 text-right">{{ $msg['at']?->format('d M H:i') }}</p>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-text-tertiary text-xs py-8">No messages with this contact yet.</div>
                        @endforelse
                    </div>
                </div>
                @else
                {{-- Contact list --}}
                <div class="glass-panel rounded-2xl border border-border-default/60 p-4">
                    <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3">Contacts</p>
                    <input wire:model.live.debounce.300ms="contactSearch" type="text" placeholder="Search contacts…"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-1.5 text-xs text-text-primary mb-3">
                    <div class="space-y-1">
                        @forelse($contacts as $c)
                        <button wire:click="selectContact({{ $c->id }})"
                            class="w-full text-left px-3 py-2 rounded-lg hover:bg-surface-hover/50 transition-colors">
                            <div class="text-sm font-medium text-text-primary">{{ $c->first_name }} {{ $c->last_name }}</div>
                            <div class="text-xs text-text-tertiary">{{ $c->email ?? $c->phone ?? '' }}</div>
                        </button>
                        @empty
                        <p class="text-xs text-text-tertiary text-center py-4">No contacts found.</p>
                        @endforelse
                    </div>
                </div>
                @endif
            </div>

        </div>
    </div>

</div>
