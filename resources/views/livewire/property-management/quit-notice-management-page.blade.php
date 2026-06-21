@php
    $statusClasses = [
        'drafted'      => 'bg-surface-raised text-text-secondary border border-border-default',
        'sent'         => 'bg-brand-primary/10 text-brand-primary border border-brand-primary/20',
        'acknowledged' => 'bg-warning-500/10 text-warning-600 border border-warning-500/20',
        'disputed'     => 'bg-danger-500/10 text-danger-600 border border-danger-500/20',
        'withdrawn'    => 'bg-surface-raised text-text-tertiary border border-border-default',
        'completed'    => 'bg-success-500/10 text-success-600 border border-success-500/20',
    ];
@endphp

<div>
    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Quit Notices</h1>
            <p class="text-sm text-text-secondary mt-0.5">Draft, send, and track tenant quit notices</p>
        </div>
        <button wire:click="$toggle('showCreateForm')"
            class="disabled:opacity-70 disabled:cursor-not-allowed relative inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-primary text-white text-sm font-semibold hover:bg-brand-secondary transition-colors shadow-sm" wire:loading.attr="disabled" wire:target="$toggle">
                <span wire:loading.remove wire:target="$toggle"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Quit Notice</span>
                <span wire:loading wire:target="$toggle" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
    </div>

    {{-- ── Stats ───────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-surface-card rounded-2xl border border-border-default p-4 text-center">
            <div class="text-2xl font-bold text-text-primary">{{ $stats['total'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Total Notices</div>
        </div>
        <div class="bg-surface-card rounded-2xl border border-brand-primary/20 p-4 text-center">
            <div class="text-2xl font-bold text-brand-primary">{{ $stats['sent'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Sent</div>
        </div>
        <div class="bg-surface-card rounded-2xl border border-warning-500/20 p-4 text-center">
            <div class="text-2xl font-bold text-warning-600">{{ $stats['acknowledged'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Acknowledged</div>
        </div>
        <div class="bg-surface-card rounded-2xl border border-danger-500/20 p-4 text-center">
            <div class="text-2xl font-bold text-danger-600">{{ $stats['disputed'] }}</div>
            <div class="text-xs text-text-secondary mt-1">Disputed</div>
        </div>
    </div>

    {{-- ── Create / Draft Form ─────────────────────────────────────────── --}}
    @if($showCreateForm)
    <div class="bg-surface-card rounded-2xl border border-border-default p-6 mb-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-base font-semibold text-text-primary">Draft Quit Notice</h2>
                <p class="text-xs text-text-tertiary mt-0.5">Use AI to generate a professional notice, then review and edit before sending.</p>
            </div>
            <button wire:click="$set('showCreateForm', false)" class="disabled:opacity-70 disabled:cursor-not-allowed relative p-1.5 rounded-lg hover:bg-surface-raised text-text-tertiary transition-colors" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
        </div>

        <form wire:submit.prevent="createNotice" class="space-y-5">

            {{-- Row 1: Lease & Vacate Date --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Lease *</label>
                    <select wire:model="lease_id" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                        <option value="">Select lease…</option>
                        @foreach($leases as $l)
                        <option value="{{ $l->id }}">
                            {{ $l->tenant?->contact?->full_name ?? 'Tenant' }} — {{ $l->listing?->property?->address_line_1 ?? 'Property' }} ({{ $l->reference }})
                        </option>
                        @endforeach
                    </select>
                    @error('lease_id') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Vacate By Date *</label>
                    <input wire:model="vacate_by_date" type="date" min="{{ now()->addDay()->toDateString() }}"
                        class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                    @error('vacate_by_date') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Row 2: Reason & Delivery --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Reason for Notice *</label>
                    <select wire:model="reason" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                        <option value="">Select reason…</option>
                        <option value="Non-payment of rent">Non-payment of rent</option>
                        <option value="Repeated late payment of rent">Repeated late payment of rent</option>
                        <option value="Breach of lease terms">Breach of lease terms</option>
                        <option value="Property damage">Property damage</option>
                        <option value="Illegal activity on premises">Illegal activity on premises</option>
                        <option value="Subletting without consent">Subletting without consent</option>
                        <option value="Expiry of lease — no renewal">Expiry of lease — no renewal</option>
                        <option value="Owner requires occupation">Owner requires occupation</option>
                        <option value="Mutual agreement">Mutual agreement</option>
                        <option value="Other">Other</option>
                    </select>
                    @error('reason') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Delivery Method *</label>
                    <select wire:model="delivery_method" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                        <option value="email">Email</option>
                        <option value="hand_delivered">Hand Delivered</option>
                        <option value="registered_post">Registered Post</option>
                        <option value="email_and_post">Email + Registered Post</option>
                    </select>
                </div>
            </div>

            {{-- AI Draft Button --}}
            <div class="flex items-center gap-3">
                <button type="button" wire:click="generateAiDraft" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-brand-primary/30 bg-brand-primary/5 text-brand-primary text-sm font-semibold hover:bg-brand-primary/10 transition-colors disabled:opacity-50">
                    <svg wire:loading wire:target="generateAiDraft" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <svg wire:loading.remove wire:target="generateAiDraft" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                    <span wire:loading.remove wire:target="generateAiDraft">Generate with AI</span>
                    <span wire:loading wire:target="generateAiDraft">Generating…</span>
                </button>
                <span class="text-xs text-text-tertiary">Select a lease, vacate date, and reason first, then click to generate a professional notice draft.</span>
            </div>

            {{-- Notice Body --}}
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Notice Content *</label>
                <textarea wire:model="notice_body" rows="12"
                    placeholder="The notice letter body will appear here after AI generation, or type your own…"
                    class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2.5 text-sm text-text-primary font-mono focus:outline-none focus:border-brand-primary/50 resize-y"></textarea>
                @error('notice_body') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Internal Notes --}}
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Internal Notes <span class="text-text-tertiary">(not included in notice)</span></label>
                <textarea wire:model="internal_notes" rows="2"
                    placeholder="e.g. Arrears amount, prior warnings issued…"
                    class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50 resize-none"></textarea>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 pt-1">
                <button type="submit"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative px-5 py-2 rounded-xl bg-brand-primary text-white text-sm font-semibold hover:bg-brand-secondary transition-colors shadow-sm" wire:loading.attr="disabled">
                <span wire:loading.remove>Save Draft</span>
                <span wire:loading class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button type="button" wire:click="$set('showCreateForm', false)"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative px-5 py-2 rounded-xl border border-border-default text-text-secondary text-sm font-semibold hover:bg-surface-raised transition-colors" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>

        </form>
    </div>
    @endif

    {{-- ── Filters ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by tenant name or reference…"
                class="w-full pl-9 pr-3 py-2 rounded-xl border border-border-default bg-surface-input text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
        </div>
        <select wire:model.live="statusFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
            <option value="">All Statuses</option>
            <option value="drafted">Drafted</option>
            <option value="sent">Sent</option>
            <option value="acknowledged">Acknowledged</option>
            <option value="disputed">Disputed</option>
            <option value="completed">Completed</option>
            <option value="withdrawn">Withdrawn</option>
        </select>
    </div>

    {{-- ── Main Layout: List + Detail ──────────────────────────────────── --}}
    <div class="flex gap-5">

        {{-- Notices List --}}
        <div class="{{ $selectedNoticeId ? 'w-[55%]' : 'w-full' }} transition-all">
            @if($notices->isEmpty())
            <div class="bg-surface-card rounded-2xl border border-border-default p-12 text-center">
                <svg class="w-10 h-10 mx-auto text-text-tertiary mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                <p class="text-sm font-medium text-text-secondary">No quit notices found</p>
                <p class="text-xs text-text-tertiary mt-1">Create your first quit notice using the button above.</p>
            </div>
            @else
            <div class="space-y-2">
                @foreach($notices as $notice)
                @php
                    $nc      = $notice->lease?->tenant?->contact;
                    $nprop   = $notice->lease?->listing?->property;
                    $isActive = $selectedNoticeId === $notice->id;
                @endphp
                <div wire:click="selectNotice({{ $notice->id }})" wire:key="notice-{{ $notice->id }}"
                    class="bg-surface-card rounded-2xl border {{ $isActive ? 'border-brand-primary/40 ring-1 ring-brand-primary/20' : 'border-border-default hover:border-border-strong' }} p-4 cursor-pointer transition-all">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-semibold text-text-primary">{{ $nc?->full_name ?? 'Unknown Tenant' }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClasses[$notice->status] ?? '' }}">
                                    {{ ucfirst($notice->status) }}
                                </span>
                            </div>
                            <p class="text-xs text-text-secondary mt-0.5 truncate">{{ $nprop?->address_line_1 ?? '—' }}</p>
                            <div class="flex items-center gap-3 mt-2 text-xs text-text-tertiary">
                                <span>{{ $notice->reference }}</span>
                                <span>&bull;</span>
                                <span>Vacate: <span class="font-medium text-text-secondary">{{ $notice->vacate_by_date->format('d M Y') }}</span></span>
                                <span>&bull;</span>
                                <span>{{ $notice->reason }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            @if(in_array($notice->status, ['drafted', 'disputed']))
                            <button wire:click.stop="sendNotice({{ $notice->id }})" wire:confirm="Send this quit notice to the tenant?"
                                class="px-3 py-1.5 rounded-lg bg-brand-primary/10 text-brand-primary text-xs font-semibold hover:bg-brand-primary/20 transition-colors">
                                Send
                            </button>
                            @endif
                            @if($notice->status === 'sent')
                            <button wire:click.stop="openResponseForm({{ $notice->id }})"
                                class="px-3 py-1.5 rounded-lg border border-warning-500/30 text-warning-600 text-xs font-semibold hover:bg-warning-500/5 transition-colors">
                                Record Response
                            </button>
                            @endif
                            @if(in_array($notice->status, ['acknowledged']))
                            <button wire:click.stop="markCompleted({{ $notice->id }})" wire:confirm="Mark this notice as completed?"
                                class="px-3 py-1.5 rounded-lg border border-success-500/30 text-success-600 text-xs font-semibold hover:bg-success-500/5 transition-colors">
                                Complete
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4">{{ $notices->links() }}</div>
            @endif
        </div>

        {{-- Detail Panel --}}
        @if($selectedNoticeId && $selectedNotice)
        <div class="flex-1 min-w-0">
            <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
                {{-- Detail Header --}}
                <div class="flex items-start justify-between p-5 border-b border-border-default">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-base font-bold text-text-primary">{{ $selectedNotice->reference }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClasses[$selectedNotice->status] ?? '' }}">
                                {{ ucfirst($selectedNotice->status) }}
                            </span>
                        </div>
                        <p class="text-xs text-text-secondary mt-0.5">
                            {{ $selectedNotice->lease?->tenant?->contact?->full_name ?? '—' }} &mdash;
                            {{ $selectedNotice->lease?->listing?->property?->address_line_1 ?? '—' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="downloadPdf({{ $selectedNotice->id }})"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative p-1.5 rounded-lg border border-border-default text-text-tertiary hover:text-text-primary hover:bg-surface-raised transition-colors" title="Download PDF" wire:loading.attr="disabled" wire:target="downloadPdf">
                <span wire:loading.remove wire:target="downloadPdf"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                <span wire:loading wire:target="downloadPdf" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="closeDetail" class="disabled:opacity-70 disabled:cursor-not-allowed relative p-1.5 rounded-lg hover:bg-surface-raised text-text-tertiary transition-colors" wire:loading.attr="disabled" wire:target="closeDetail">
                <span wire:loading.remove wire:target="closeDetail"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></span>
                <span wire:loading wire:target="closeDetail" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="flex border-b border-border-default">
                    @foreach(['notice' => 'Notice', 'details' => 'Details', 'timeline' => 'Timeline'] as $tab => $label)
                    <button wire:click="$set('detailTab', '{{ $tab }}')"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors {{ $detailTab === $tab ? 'border-brand-primary text-brand-primary' : 'border-transparent text-text-tertiary hover:text-text-secondary' }}" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">{{ $label }}</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    @endforeach
                </div>

                {{-- Tab: Notice --}}
                @if($detailTab === 'notice')
                <div class="p-5">
                    <div class="bg-warning-500/5 border border-warning-500/20 rounded-xl p-3 mb-4 flex items-center gap-3">
                        <svg class="w-5 h-5 text-warning-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <div>
                            <p class="text-xs font-semibold text-warning-700">Vacate By</p>
                            <p class="text-sm font-bold text-warning-800">{{ $selectedNotice->vacate_by_date->format('d F Y') }}</p>
                        </div>
                    </div>
                    <div class="text-xs font-medium text-text-tertiary uppercase tracking-wider mb-2">Notice Content</div>
                    <div class="text-sm text-text-primary leading-relaxed whitespace-pre-wrap bg-surface-raised rounded-xl p-4 border border-border-default font-mono">{{ $selectedNotice->notice_body }}</div>
                    @if($selectedNotice->internal_notes)
                    <div class="mt-4 pt-4 border-t border-border-default">
                        <div class="text-xs font-medium text-text-tertiary uppercase tracking-wider mb-2">Internal Notes</div>
                        <p class="text-xs text-text-secondary">{{ $selectedNotice->internal_notes }}</p>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Tab: Details --}}
                @if($detailTab === 'details')
                <div class="p-5">
                    <dl class="space-y-3">
                        @php
                            $dc = $selectedNotice->lease?->tenant?->contact;
                            $dp = $selectedNotice->lease?->listing?->property;
                        @endphp
                        <div class="flex gap-2">
                            <dt class="text-xs text-text-tertiary w-32 shrink-0 pt-0.5">Tenant</dt>
                            <dd class="text-xs font-medium text-text-primary">{{ $dc?->full_name ?? '—' }}</dd>
                        </div>
                        @if($dc?->email)
                        <div class="flex gap-2">
                            <dt class="text-xs text-text-tertiary w-32 shrink-0 pt-0.5">Email</dt>
                            <dd class="text-xs text-text-primary">{{ $dc->email }}</dd>
                        </div>
                        @endif
                        @if($dc?->phone)
                        <div class="flex gap-2">
                            <dt class="text-xs text-text-tertiary w-32 shrink-0 pt-0.5">Phone</dt>
                            <dd class="text-xs text-text-primary">{{ $dc->phone }}</dd>
                        </div>
                        @endif
                        <div class="flex gap-2">
                            <dt class="text-xs text-text-tertiary w-32 shrink-0 pt-0.5">Property</dt>
                            <dd class="text-xs font-medium text-text-primary">{{ $dp ? "{$dp->address_line_1}, {$dp->city}" : '—' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-xs text-text-tertiary w-32 shrink-0 pt-0.5">Lease Ref</dt>
                            <dd class="text-xs text-text-primary">{{ $selectedNotice->lease?->reference ?? '—' }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-xs text-text-tertiary w-32 shrink-0 pt-0.5">Reason</dt>
                            <dd class="text-xs text-text-primary">{{ $selectedNotice->reason }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-xs text-text-tertiary w-32 shrink-0 pt-0.5">Issue Date</dt>
                            <dd class="text-xs text-text-primary">{{ $selectedNotice->issue_date->format('d M Y') }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-xs text-text-tertiary w-32 shrink-0 pt-0.5">Vacate By</dt>
                            <dd class="text-xs font-bold text-warning-600">{{ $selectedNotice->vacate_by_date->format('d M Y') }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-xs text-text-tertiary w-32 shrink-0 pt-0.5">Delivery</dt>
                            <dd class="text-xs text-text-primary">{{ ucfirst(str_replace('_', ' ', $selectedNotice->delivery_method)) }}</dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-xs text-text-tertiary w-32 shrink-0 pt-0.5">Issued By</dt>
                            <dd class="text-xs text-text-primary">{{ $selectedNotice->issuedBy?->name ?? '—' }}</dd>
                        </div>
                        @if($selectedNotice->tenant_response)
                        <div class="pt-3 border-t border-border-default">
                            <div class="text-xs font-semibold text-text-secondary mb-1">Tenant Response</div>
                            <p class="text-xs text-text-primary bg-surface-raised rounded-lg p-3">{{ $selectedNotice->tenant_response }}</p>
                            @if($selectedNotice->acknowledged_at)
                            <p class="text-xs text-text-tertiary mt-1">Recorded {{ $selectedNotice->acknowledged_at->format('d M Y H:i') }}</p>
                            @endif
                        </div>
                        @endif
                    </dl>
                </div>
                @endif

                {{-- Tab: Timeline --}}
                @if($detailTab === 'timeline')
                <div class="p-5">
                    <ol class="relative border-l border-border-default ml-3 space-y-6">
                        <li class="ml-5">
                            <span class="absolute -left-2 flex h-4 w-4 items-center justify-center rounded-full bg-surface-card border-2 border-brand-primary"></span>
                            <time class="text-xs text-text-tertiary">{{ $selectedNotice->created_at->format('d M Y H:i') }}</time>
                            <p class="text-xs font-semibold text-text-primary mt-0.5">Notice Drafted</p>
                            <p class="text-xs text-text-secondary">Created by {{ $selectedNotice->issuedBy?->name ?? 'Unknown' }}</p>
                        </li>
                        @if($selectedNotice->sent_at)
                        <li class="ml-5">
                            <span class="absolute -left-2 flex h-4 w-4 items-center justify-center rounded-full bg-brand-primary/20 border-2 border-brand-primary"></span>
                            <time class="text-xs text-text-tertiary">{{ $selectedNotice->sent_at->format('d M Y H:i') }}</time>
                            <p class="text-xs font-semibold text-text-primary mt-0.5">Notice Sent</p>
                            <p class="text-xs text-text-secondary">Via {{ ucfirst(str_replace('_', ' ', $selectedNotice->delivery_method)) }}</p>
                        </li>
                        @endif
                        @if($selectedNotice->acknowledged_at)
                        <li class="ml-5">
                            <span class="absolute -left-2 flex h-4 w-4 items-center justify-center rounded-full {{ $selectedNotice->status === 'disputed' ? 'bg-danger-500/20 border-danger-600' : 'bg-warning-500/20 border-warning-500' }} border-2"></span>
                            <time class="text-xs text-text-tertiary">{{ $selectedNotice->acknowledged_at->format('d M Y H:i') }}</time>
                            <p class="text-xs font-semibold text-text-primary mt-0.5">Tenant {{ $selectedNotice->status === 'disputed' ? 'Disputed' : 'Acknowledged' }}</p>
                            @if($selectedNotice->tenant_response)
                            <p class="text-xs text-text-secondary mt-1 bg-surface-raised rounded-lg p-2">{{ $selectedNotice->tenant_response }}</p>
                            @endif
                        </li>
                        @endif
                        @if($selectedNotice->status === 'completed')
                        <li class="ml-5">
                            <span class="absolute -left-2 flex h-4 w-4 items-center justify-center rounded-full bg-success-500/20 border-2 border-success-500"></span>
                            <time class="text-xs text-text-tertiary">{{ $selectedNotice->updated_at->format('d M Y H:i') }}</time>
                            <p class="text-xs font-semibold text-success-600 mt-0.5">Notice Completed</p>
                        </li>
                        @endif
                    </ol>
                </div>
                @endif

                {{-- Actions Footer --}}
                <div class="border-t border-border-default px-5 py-3 flex items-center gap-2">
                    @if(in_array($selectedNotice->status, ['drafted', 'disputed']))
                    <button wire:click="sendNotice({{ $selectedNotice->id }})" wire:confirm="Send this quit notice to the tenant?"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 rounded-xl bg-brand-primary text-white text-xs font-semibold hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled" wire:target="sendNotice">
                <span wire:loading.remove wire:target="sendNotice">Send Notice</span>
                <span wire:loading wire:target="sendNotice" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    @endif
                    @if($selectedNotice->status === 'sent')
                    <button wire:click="openResponseForm({{ $selectedNotice->id }})"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 rounded-xl border border-warning-500/30 text-warning-600 text-xs font-semibold hover:bg-warning-500/5 transition-colors" wire:loading.attr="disabled" wire:target="openResponseForm">
                <span wire:loading.remove wire:target="openResponseForm">Record Response</span>
                <span wire:loading wire:target="openResponseForm" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    @endif
                    @if($selectedNotice->status === 'acknowledged')
                    <button wire:click="markCompleted({{ $selectedNotice->id }})" wire:confirm="Mark this notice as completed (tenant has vacated)?"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 rounded-xl border border-success-500/30 text-success-600 text-xs font-semibold hover:bg-success-500/5 transition-colors" wire:loading.attr="disabled" wire:target="markCompleted">
                <span wire:loading.remove wire:target="markCompleted">Mark Completed</span>
                <span wire:loading wire:target="markCompleted" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    @endif
                    @if(in_array($selectedNotice->status, ['drafted', 'sent']))
                    <button wire:click="withdrawNotice({{ $selectedNotice->id }})" wire:confirm="Withdraw this quit notice? This cannot be undone."
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 rounded-xl border border-border-default text-text-tertiary text-xs font-semibold hover:bg-surface-raised transition-colors" wire:loading.attr="disabled" wire:target="withdrawNotice">
                <span wire:loading.remove wire:target="withdrawNotice">Withdraw</span>
                <span wire:loading wire:target="withdrawNotice" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    @endif
                    <button wire:click="downloadPdf({{ $selectedNotice->id }})"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative ml-auto px-4 py-2 rounded-xl border border-border-default text-text-secondary text-xs font-semibold hover:bg-surface-raised transition-colors flex items-center gap-1.5" wire:loading.attr="disabled" wire:target="downloadPdf">
                <span wire:loading.remove wire:target="downloadPdf"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Download PDF</span>
                <span wire:loading wire:target="downloadPdf" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </div>
            </div>
        </div>
        @endif

    </div>{{-- end main layout --}}

    {{-- ── Tenant Response Modal ────────────────────────────────────────── --}}
    @if($showResponseForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
        <div class="bg-surface-card rounded-2xl border border-border-default w-full max-w-md shadow-xl">
            <div class="flex items-center justify-between p-5 border-b border-border-default">
                <h3 class="text-base font-semibold text-text-primary">Record Tenant Response</h3>
                <button wire:click="$set('showResponseForm', false)" class="disabled:opacity-70 disabled:cursor-not-allowed relative p-1.5 rounded-lg hover:bg-surface-raised text-text-tertiary" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
            <form wire:submit.prevent="recordTenantResponse" class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Tenant Response Status *</label>
                    <select wire:model="response_status" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50">
                        <option value="acknowledged">Acknowledged (accepts notice)</option>
                        <option value="disputed">Disputed (challenges notice)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Response Notes *</label>
                    <textarea wire:model="tenant_response" rows="4"
                        placeholder="Summarise the tenant's verbal or written response…"
                        class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-brand-primary/50 resize-none"></textarea>
                    @error('tenant_response') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="disabled:opacity-70 disabled:cursor-not-allowed relative flex-1 py-2 rounded-xl bg-brand-primary text-white text-sm font-semibold hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled">
                <span wire:loading.remove>Save Response</span>
                <span wire:loading class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    <button type="button" wire:click="$set('showResponseForm', false)"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 rounded-xl border border-border-default text-text-secondary text-sm font-semibold hover:bg-surface-raised transition-colors" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
