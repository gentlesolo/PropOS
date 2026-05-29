<div class="h-[calc(100vh-8rem)] flex flex-col">
    <div class="mb-6 flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Pipeline Board</h1>
            <p class="mt-2 text-text-secondary">Drag and drop deals across stages to track momentum.</p>
        </div>
        <div class="flex items-center space-x-3">
            <select wire:model="pipelineType" class="bg-surface-card border border-border-default/60 text-text-primary rounded-xl px-4 py-2 text-sm font-semibold focus:ring-brand-primary focus:border-brand-primary">
                <option value="sale">Sales Pipeline</option>
                <option value="rental">Rental Pipeline</option>
            </select>
            <button wire:click="$set('showNewDealModal', true)" class="bg-brand-primary text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md hover:bg-brand-secondary transition-colors hover-spring">
                + New Deal
            </button>
        </div>
    </div>

    <!-- Stale Deal Alert -->
    @if($staleDeals->isNotEmpty())
    <div class="mb-4 p-3 bg-warning-50 border border-warning-200 rounded-xl flex items-center gap-3 shrink-0">
        <div class="h-2 w-2 rounded-full bg-warning-500 animate-pulse shrink-0"></div>
        <p class="text-sm text-warning-800 font-medium">
            <strong>{{ $staleDeals->count() }} stale deal{{ $staleDeals->count() > 1 ? 's' : '' }}</strong> with no activity in 14+ days:
            {{ $staleDeals->take(3)->map(fn($d) => $d->title)->implode(', ') }}{{ $staleDeals->count() > 3 ? '...' : '' }}
        </p>
    </div>
    @endif

    <!-- Kanban Board Container -->
    <div class="flex-1 overflow-x-auto pb-4">
        <div class="flex h-full space-x-6 min-w-max px-1">
            @foreach($stages as $stage)
                <div class="flex flex-col w-80 max-h-full shrink-0">
                    <!-- Stage Header -->
                    <div class="flex items-center justify-between mb-3 px-1">
                        <div class="flex items-center space-x-2">
                            <h3 class="font-bold text-text-primary uppercase tracking-wider text-sm">{{ $stage->name }}</h3>
                            <span class="bg-surface-raised border border-border-default/60 text-text-secondary text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ $stage->deals->count() }}
                            </span>
                            @if($stage->is_won)
                                <span class="text-[10px] bg-success-100 text-success-700 px-1.5 py-0.5 rounded font-bold uppercase">Won</span>
                            @elseif($stage->is_lost)
                                <span class="text-[10px] bg-danger-100 text-danger-700 px-1.5 py-0.5 rounded font-bold uppercase">Lost</span>
                            @endif
                        </div>
                        <div class="text-xs font-black text-text-tertiary">
                            ₦{{ number_format($stage->deals->sum('value') / 1000000, 1) }}M
                        </div>
                    </div>

                    <!-- Stage Column (Sortable Zone) -->
                    <div
                        class="flex-1 bg-surface-card/50 border border-border-default/40 rounded-3xl p-3 overflow-y-auto space-y-3"
                        id="stage-{{ $stage->id }}"
                        x-data="{}"
                        x-init="
                            Sortable.create($el, {
                                group: 'pipeline',
                                animation: 150,
                                ghostClass: 'opacity-50',
                                onEnd: function(evt) {
                                    if(evt.from !== evt.to) {
                                        let dealId = evt.item.dataset.id;
                                        let newStageId = evt.to.id.replace('stage-', '');
                                        @this.updateDealStage(dealId, newStageId);
                                    }
                                }
                            });
                        "
                    >
                        @foreach($stage->deals as $deal)
                            <div data-id="{{ $deal->id }}" class="bg-surface-card border border-border-default/60 rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow cursor-grab active:cursor-grabbing hover-spring relative group">
                                <div class="flex justify-between items-start mb-3">
                                    <a href="{{ route('crm.deal.detail', $deal) }}" class="text-sm font-bold text-text-primary group-hover:text-brand-primary transition-colors line-clamp-2">
                                        {{ $deal->title }}
                                    </a>
                                    <a href="{{ route('crm.deal.detail', $deal) }}" class="text-text-tertiary hover:text-brand-primary opacity-0 group-hover:opacity-100 transition-opacity shrink-0 ml-2">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>

                                <div class="space-y-2 mb-4">
                                    <div class="flex items-center text-xs text-text-secondary">
                                        <svg class="h-3.5 w-3.5 mr-1.5 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        {{ $deal->contact?->first_name }} {{ $deal->contact?->last_name }}
                                    </div>
                                    @if($deal->listing)
                                    <div class="flex items-center text-xs text-text-secondary">
                                        <svg class="h-3.5 w-3.5 mr-1.5 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                        {{ $deal->listing->property->address_line_1 }}
                                    </div>
                                    @endif
                                    <div class="text-[10px] text-text-tertiary">Updated {{ $deal->updated_at->diffForHumans() }}</div>
                                </div>

                                <div class="flex items-center justify-between border-t border-border-default/40 pt-3">
                                    <span class="text-sm font-black tracking-tight text-text-primary">₦{{ number_format($deal->value) }}</span>
                                    <div class="flex items-center space-x-1 px-2 py-0.5 rounded-lg border
                                        {{ $deal->momentum_score >= 70 ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-600' : ($deal->momentum_score >= 40 ? 'bg-orange-500/10 border-orange-500/20 text-orange-600' : 'bg-rose-500/10 border-rose-500/20 text-rose-600') }}">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                        <span class="text-[10px] font-bold">{{ $deal->momentum_score }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- New Deal Modal -->
    @if($showNewDealModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="$set('showNewDealModal', false)"></div>
        <div class="relative bg-surface-card rounded-2xl border border-border-default/60 shadow-2xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-lg font-bold text-text-primary">New Deal</h2>
                <button wire:click="$set('showNewDealModal', false)" class="text-text-secondary hover:text-text-primary">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form wire:submit.prevent="saveDeal" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Deal Title *</label>
                    <input wire:model.defer="title" type="text" placeholder="e.g. Lekki apartment sale" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary text-sm">
                    @error('title') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Contact *</label>
                    <select wire:model.defer="contact_id" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary text-sm">
                        <option value="">Select contact...</option>
                        @foreach($contacts as $c)
                        <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                        @endforeach
                    </select>
                    @error('contact_id') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Listing (optional)</label>
                    <select wire:model.defer="listing_id" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary text-sm">
                        <option value="">No listing attached</option>
                        @foreach($listings as $l)
                        <option value="{{ $l->id }}">{{ $l->property->address_line_1 }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Deal Value (₦) *</label>
                    <input wire:model.defer="value" type="number" min="0" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary text-sm">
                    @error('value') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Notes</label>
                    <textarea wire:model.defer="notes" rows="2" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary text-sm resize-none"></textarea>
                </div>
                <button type="submit" class="w-full py-2.5 bg-brand-primary text-white rounded-xl font-semibold hover:bg-brand-secondary transition-colors">
                    <span wire:loading.remove wire:target="saveDeal">Create Deal</span>
                    <span wire:loading wire:target="saveDeal">Creating...</span>
                </button>
            </form>
        </div>
    </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
</div>
