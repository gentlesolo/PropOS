<div class="flex gap-0 h-full">

    {{-- ══ Main column ══════════════════════════════════════════════════════════ --}}
    <div class="flex-1 min-w-0 overflow-auto p-6">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-text-primary">Messaging Inbox</h1>
                <p class="text-sm text-text-secondary mt-0.5">Email threads, SMS, and WhatsApp in one place</p>
            </div>
            <button onclick="Livewire.dispatch('openEmailComposer', {})"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:opacity-90 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Compose
            </button>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-surface-card rounded-2xl border border-border-default p-4 text-center">
                <div class="text-2xl font-bold text-text-primary">{{ $stats['email_threads'] }}</div>
                <div class="text-xs text-text-secondary mt-1">Email Threads</div>
                @if($stats['email_unread'] > 0)
                <div class="text-xs text-brand-primary font-semibold mt-0.5">{{ $stats['email_unread'] }} unread</div>
                @endif
            </div>
            <div class="bg-surface-card rounded-2xl border border-border-default p-4 text-center">
                <div class="text-2xl font-bold text-text-primary">{{ $stats['sms_sent'] }}</div>
                <div class="text-xs text-text-secondary mt-1">SMS Sent</div>
            </div>
            <div class="bg-surface-card rounded-2xl border border-border-default p-4 text-center">
                <div class="text-2xl font-bold text-text-primary">{{ $stats['sms_inbound'] }}</div>
                <div class="text-xs text-text-secondary mt-1">SMS Received</div>
            </div>
            <div class="bg-surface-card rounded-2xl border border-border-default p-4 text-center">
                <div class="text-2xl font-bold text-text-primary">{{ $stats['wa_total'] }}</div>
                <div class="text-xs text-text-secondary mt-1">WhatsApp Total</div>
            </div>
        </div>

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

            {{-- Thread filter (email only) --}}
            @if(in_array($channel, ['all', 'email']))
            <select wire:model.live="threadFilter"
                    class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                <option value="all">All Threads</option>
                <option value="mine">Assigned to me</option>
                <option value="unassigned">Unassigned</option>
                <option value="unread">Unread only</option>
            </select>
            @endif
        </div>

        {{-- Main grid --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            {{-- Left: thread/message lists --}}
            <div class="xl:col-span-2 space-y-3">

                {{-- ── Email threads ── --}}
                @if(in_array($channel, ['all', 'email']))
                <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
                    <div class="px-4 py-3 border-b border-border-default bg-surface-hover/30 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm font-semibold text-text-primary">Email Threads ({{ $emailThreads->count() }})</span>
                        </div>
                        <a href="{{ route('settings.email-accounts') }}"
                           class="text-xs text-text-tertiary hover:text-brand-primary transition">
                            Manage accounts →
                        </a>
                    </div>

                    <div class="divide-y divide-border-default">
                        @forelse($emailThreads as $thread)
                        <button wire:click="selectThread({{ $thread->id }})"
                                class="w-full text-left px-4 py-3 hover:bg-surface-hover/30 transition-colors {{ $selectedThreadId === $thread->id ? 'bg-brand-primary/5 border-l-2 border-brand-primary' : '' }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        @if($thread->unread_count > 0)
                                        <span class="w-2 h-2 rounded-full bg-brand-primary shrink-0"></span>
                                        @endif
                                        <p class="text-sm font-{{ $thread->unread_count > 0 ? 'semibold' : 'medium' }} text-text-primary truncate">
                                            {{ $thread->subject }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        @if($thread->contact)
                                        <span class="text-xs text-brand-primary font-medium">{{ $thread->contact->full_name }}</span>
                                        <span class="text-xs text-text-tertiary">·</span>
                                        @endif
                                        <span class="text-xs text-text-tertiary truncate">
                                            {{ implode(', ', array_slice((array) $thread->participants, 0, 2)) }}
                                        </span>
                                    </div>
                                    @if($thread->assignedAgent)
                                    <span class="text-xs text-text-tertiary">→ {{ $thread->assignedAgent->first_name }}</span>
                                    @endif
                                </div>
                                <div class="flex flex-col items-end gap-1 shrink-0">
                                    <span class="text-xs text-text-tertiary">{{ $thread->last_message_at?->diffForHumans() }}</span>
                                    @if($thread->unread_count > 0)
                                    <span class="px-1.5 py-0.5 bg-brand-primary text-white text-xs rounded-full font-semibold min-w-[1.25rem] text-center">
                                        {{ $thread->unread_count }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </button>
                        @empty
                        <div class="px-4 py-10 text-center">
                            <svg class="w-10 h-10 mx-auto mb-2 text-text-tertiary opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-sm text-text-tertiary">No email threads yet.</p>
                            <p class="text-xs text-text-tertiary mt-1">
                                <a href="{{ route('settings.email-accounts') }}" class="text-brand-primary hover:underline">Connect an email account</a> to start receiving emails.
                            </p>
                        </div>
                        @endforelse
                    </div>
                </div>
                @endif

                {{-- ── SMS ── --}}
                @if(in_array($channel, ['all', 'sms']))
                <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
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
                                    <button wire:click="selectContact({{ $msg->contact->id }})" class="text-xs text-brand-primary hover:underline mt-0.5">{{ $msg->contact->full_name }}</button>
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

                {{-- ── WhatsApp ── --}}
                @if(in_array($channel, ['all', 'whatsapp']))
                <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
                    <div class="px-4 py-3 border-b border-border-default bg-surface-hover/30 flex items-center gap-2">
                        <span class="text-sm font-semibold text-text-primary">WhatsApp ({{ $whatsAppMessages->count() }})</span>
                    </div>
                    <div class="divide-y divide-border-default">
                        @forelse($whatsAppMessages as $msg)
                        <div class="px-4 py-3 hover:bg-surface-hover/30 transition-colors">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="text-xs px-1.5 py-0.5 rounded {{ $msg->direction === 'inbound' ? 'bg-green-50 text-green-700' : 'bg-brand-50 text-brand-700' }}">
                                            {{ $msg->direction === 'inbound' ? 'In' : 'Out' }}
                                        </span>
                                        @if($msg->contact)
                                        <button wire:click="selectContact({{ $msg->contact->id }})" class="text-xs font-medium text-brand-primary hover:underline">{{ $msg->contact->full_name }}</button>
                                        @else
                                        <span class="text-xs text-text-secondary">{{ $msg->to_number }}</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-text-primary line-clamp-2">{{ $msg->body }}</p>
                                </div>
                                <span class="text-xs text-text-tertiary shrink-0">{{ ($msg->sent_at ?? $msg->created_at)?->diffForHumans() }}</span>
                            </div>
                        </div>
                        @empty
                        <div class="px-4 py-8 text-center text-text-tertiary text-sm">No WhatsApp messages yet.</div>
                        @endforelse
                    </div>
                </div>
                @endif

                {{-- SMS/WA compose form --}}
                @if($showCompose && in_array($composeChannel, ['sms', 'whatsapp']))
                <div class="bg-surface-card rounded-2xl border border-brand-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-text-primary">Compose {{ ucfirst($composeChannel) }}</h2>
                        <button wire:click="$set('showCompose', false)" class="text-text-tertiary hover:text-text-secondary text-xl leading-none">&times;</button>
                    </div>
                    <form wire:submit.prevent="sendMessage" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">To (phone number) *</label>
                            <input wire:model="compose_to" type="tel"
                                class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary"
                                placeholder="+2347012345678">
                            @error('compose_to') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Message *</label>
                            <textarea wire:model="compose_body" rows="4"
                                class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary resize-none"
                                placeholder="Type your message…"></textarea>
                            @error('compose_body') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" wire:loading.attr="disabled"
                                class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-medium hover:opacity-90 transition">Send</button>
                            <button type="button" wire:click="$set('showCompose', false)"
                                class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition">Cancel</button>
                        </div>
                    </form>
                </div>
                @endif

            </div>

            {{-- ── Right panel: thread viewer or contact thread ── --}}
            <div>
                @if($selectedThread)
                {{-- Email thread view --}}
                <div class="bg-surface-card rounded-2xl border border-brand-200 overflow-hidden flex flex-col sticky top-6" style="max-height: 80vh;">
                    {{-- Thread header --}}
                    <div class="px-4 py-3 border-b border-border-default bg-brand-primary/5 shrink-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-text-primary truncate">{{ $selectedThread->subject }}</p>
                                @if($selectedThread->contact)
                                <a href="{{ route('crm.contact.detail', $selectedThread->contact) }}"
                                   class="text-xs text-brand-primary hover:underline">{{ $selectedThread->contact->full_name }}</a>
                                @endif
                                <p class="text-xs text-text-tertiary mt-0.5">
                                    {{ implode(' · ', (array) $selectedThread->participants) }}
                                </p>
                            </div>
                            <button wire:click="closeThread" class="text-text-tertiary hover:text-text-secondary text-xl leading-none shrink-0">&times;</button>
                        </div>

                        {{-- Assign to agent --}}
                        <div class="mt-2 flex items-center gap-2">
                            <span class="text-xs text-text-tertiary">Assigned:</span>
                            <select wire:change="assignThread({{ $selectedThread->id }}, $event.target.value)"
                                    class="text-xs border-0 bg-transparent text-text-secondary outline-none cursor-pointer">
                                <option value="">Unassigned</option>
                                @foreach($teamMembers as $member)
                                <option value="{{ $member->id }}" {{ $selectedThread->assigned_to === $member->id ? 'selected' : '' }}>
                                    {{ $member->first_name }} {{ $member->last_name }}
                                </option>
                                @endforeach
                            </select>
                            <button wire:click="archiveThread({{ $selectedThread->id }})"
                                    class="ml-auto text-xs text-text-tertiary hover:text-text-primary">
                                Archive
                            </button>
                        </div>
                    </div>

                    {{-- Messages --}}
                    <div class="overflow-y-auto flex-1 p-3 space-y-3">
                        @forelse($threadMessages as $msg)
                        <div class="flex {{ $msg->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[90%] {{ $msg->direction === 'outbound' ? 'bg-brand-primary/10 border border-brand-primary/20' : 'bg-surface-elevated border border-border-default' }} rounded-xl px-3 py-2.5">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-semibold text-text-primary">
                                        {{ $msg->direction === 'inbound' ? ($msg->from_name ?: $msg->from_email) : ($msg->sentBy?->first_name ?? 'You') }}
                                    </span>
                                    <span class="text-xs text-text-tertiary">{{ $msg->sent_at?->format('d M H:i') ?? $msg->created_at->format('d M H:i') }}</span>
                                    @if($msg->status && $msg->direction === 'outbound')
                                    <span class="text-xs text-text-tertiary">· {{ ucfirst($msg->status) }}</span>
                                    @endif
                                </div>
                                @if($msg->body_html)
                                <div class="text-sm text-text-primary prose prose-sm max-w-none">
                                    {!! \Illuminate\Support\Str::limit(strip_tags($msg->body_html), 400) !!}
                                </div>
                                @elseif($msg->body_text)
                                <p class="text-sm text-text-primary leading-relaxed">{{ \Illuminate\Support\Str::limit($msg->body_text, 400) }}</p>
                                @else
                                <p class="text-xs text-text-tertiary italic">{{ $msg->subject }}</p>
                                @endif
                            </div>
                        </div>
                        @empty
                        <p class="text-center text-text-tertiary text-xs py-6">No messages in this thread yet.</p>
                        @endforelse
                    </div>

                    {{-- Reply bar --}}
                    <div class="p-3 border-t border-border-default shrink-0">
                        <button wire:click="openComposeForThread"
                                class="w-full flex items-center gap-2 px-4 py-2.5 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-elevated hover:text-text-primary transition text-left">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                            Reply to this thread…
                        </button>
                    </div>
                </div>

                @elseif($selectedContact)
                {{-- SMS/WA contact thread --}}
                <div class="bg-surface-card rounded-2xl border border-brand-200 overflow-hidden flex flex-col sticky top-6" style="max-height: 70vh;">
                    <div class="px-4 py-3 border-b border-border-default bg-brand-primary/5 flex items-center justify-between shrink-0">
                        <div>
                            <p class="text-sm font-semibold text-text-primary">{{ $selectedContact->full_name }}</p>
                            <p class="text-xs text-text-tertiary">{{ $selectedContact->email ?? $selectedContact->phone ?? '' }}</p>
                        </div>
                        <button wire:click="$set('selectedContactId', null)" class="text-text-tertiary hover:text-text-secondary text-xl leading-none">&times;</button>
                    </div>
                    <div class="overflow-y-auto flex-1 p-3 space-y-2">
                        @forelse($thread as $msg)
                        <div class="flex {{ $msg['direction'] === 'outbound' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[85%] rounded-xl px-3 py-2 text-xs
                                {{ $msg['direction'] === 'outbound' ? 'bg-brand-primary text-white' : 'bg-surface-elevated text-text-primary' }}">
                                <div class="opacity-75 mb-1 flex items-center gap-1">
                                    <span>{{ strtoupper($msg['type']) }}</span>
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
                {{-- Placeholder --}}
                <div class="bg-surface-card rounded-2xl border border-border-default p-8 text-center text-text-tertiary sticky top-6">
                    <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm">Select a thread to read it</p>
                </div>
                @endif
            </div>

        </div>
    </div>

</div>
