<div class="flex flex-col lg:flex-row h-full min-h-[calc(100vh-4rem)] bg-[#030712] font-sans relative overflow-hidden" 
     x-data="{ 
         mobileListOpen: false 
     }">

    <style>
        .expired-row {
            position: relative;
            opacity: 0.45;
        }
        .expired-watermark {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Geist Mono', monospace;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.25em;
            color: rgba(244, 63, 94, 0.15);
            text-transform: uppercase;
            transform: rotate(-8deg);
            pointer-events: none;
        }
        .shimmer-bg {
            background: linear-gradient(90deg, transparent, rgba(16, 185, 129, 0.05), transparent);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
    </style>

    {{-- ── LEFT SIDEBAR (Offers list) ── --}}
    <div class="w-full lg:w-[320px] lg:border-r lg:border-white/5 bg-[#090d16]/60 backdrop-blur-md flex flex-col flex-shrink-0"
         :class="mobileListOpen ? 'block fixed inset-0 z-40 bg-[#030712]' : 'hidden lg:flex'">
         
        {{-- Sidebar Header --}}
        <div class="p-4 border-b border-white/5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold tracking-tight uppercase text-[#FAFAFA] font-sans">Active Offers</h2>
                    <p class="text-[10px] text-[#A1A1AA] mt-0.5 font-mono">{{ $stats['total'] }} total tracked</p>
                </div>
                <button @click="mobileListOpen = false" class="lg:hidden text-text-tertiary hover:text-text-secondary text-lg leading-none">&times;</button>
            </div>
            
            <button wire:click="openCreateForm" 
                    @click="mobileListOpen = false"
                    class="w-full py-2.5 bg-[#F59E0B] hover:bg-[#F59E0B]/90 active:scale-[0.98] text-black font-semibold rounded-md shadow-[0_2px_8px_rgba(245,158,11,0.2)] transition-all duration-150 text-xs flex items-center justify-center gap-1.5 uppercase tracking-wider">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                New Offer
            </button>
            
            {{-- Search & Filters --}}
            <div class="space-y-2">
                <div class="relative">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search buyers..."
                        class="w-full h-8 bg-[#111827] border border-white/10 text-xs text-[#FAFAFA] placeholder-[#52525B] pl-8 pr-2.5 rounded-md focus:outline-none focus:border-[#10B981] transition-all">
                    <svg class="absolute left-2.5 top-2.5 h-3 w-3 text-[#52525B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <select wire:model.live="statusFilter" class="h-7 bg-[#111827] border border-white/10 text-[10px] text-[#A1A1AA] rounded-md px-1.5 focus:outline-none focus:border-[#10B981] transition-all">
                        <option value="">All Statuses</option>
                        <option value="pending">Submitted</option>
                        <option value="countered">Countered</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Declined</option>
                        <option value="expired">Expired</option>
                        <option value="withdrawn">Withdrawn</option>
                    </select>
                    <select wire:model.live="typeFilter" class="h-7 bg-[#111827] border border-white/10 text-[10px] text-[#A1A1AA] rounded-md px-1.5 focus:outline-none focus:border-[#10B981] transition-all">
                        <option value="">All Types</option>
                        <option value="sale">Sale</option>
                        <option value="rental">Rental</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Offers List Scroll Area --}}
        <div class="flex-1 overflow-y-auto divide-y divide-white/5">
            @forelse($offers as $offer)
                @php
                    $isExpired = $offer->status === 'expired' || ($offer->expiry_date && $offer->expiry_date->isPast() && $offer->status === 'pending');
                    $active = $detailOfferId === $offer->id && !$showCreateForm && !$showEditForm;
                    $statusLabel = match ($offer->status) {
                        'pending' => 'Submitted',
                        'countered' => 'Counter-Offered',
                        'accepted' => 'Accepted',
                        'rejected' => 'Declined',
                        'expired' => 'Expired',
                        'withdrawn' => 'Expired',
                        default => ucfirst($offer->status),
                    };
                    $statusColorClass = match ($offer->status) {
                        'pending' => 'bg-[#0EA5E9]/10 text-[#0EA5E9] border-[#0EA5E9]/20',
                        'countered' => 'bg-[#F59E0B]/10 text-[#F59E0B] border-[#F59E0B]/20',
                        'accepted' => 'bg-[#10B981]/10 text-[#10B981] border-[#10B981]/20',
                        'rejected' => 'bg-[#F43F5E]/10 text-[#F43F5E] border-[#F43F5E]/20',
                        'expired', 'withdrawn' => 'bg-white/5 text-[#52525B] border-white/10',
                        default => 'bg-white/5 text-[#A1A1AA] border-white/10',
                    };
                @endphp
                <div wire:click="openDetail({{ $offer->id }})"
                     @click="mobileListOpen = false"
                     class="relative p-3.5 flex gap-3 cursor-pointer transition-all duration-150 border-l-[3px]
                            {{ $active ? 'border-[#10B981] bg-[#111827]' : 'border-transparent hover:bg-white/[0.02]' }}
                            {{ $isExpired ? 'expired-row' : '' }}">
                    
                    {{-- Diagonal Watermark on Expired --}}
                    @if($isExpired)
                        <div class="expired-watermark">EXPIRED</div>
                    @endif

                    {{-- Property Thumbnail --}}
                    <div class="h-10 w-10 rounded-md bg-[#111827] border border-white/5 overflow-hidden flex-shrink-0 flex items-center justify-center">
                        @if($offer->listing && $offer->listing->coverPhoto)
                            <img src="{{ asset('storage/' . $offer->listing->coverPhoto->file_path) }}" class="h-full w-full object-cover">
                        @else
                            <svg class="h-5 w-5 text-[#52525B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                            </svg>
                        @endif
                    </div>

                    {{-- Main Info --}}
                    <div class="flex-1 min-w-0 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-mono tracking-tight font-bold text-[#FAFAFA]">
                                {{ $offer->contact?->full_name ?? 'Anonymous Buyer' }}
                            </span>
                            <span class="text-[9px] text-[#52525B] font-mono shrink-0">
                                {{ $offer->created_at->diffForHumans(null, true) }}
                            </span>
                        </div>
                        
                        <div class="text-[11px] text-[#A1A1AA] truncate">
                            {{ $offer->listing?->property?->address_line_1 ?? 'No address provided' }}
                        </div>

                        <div class="flex items-center justify-between pt-0.5">
                            <span class="font-mono text-xs font-semibold text-[#FAFAFA]">
                                {{ $currencySymbol }}{{ number_format($offer->amount) }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[8px] font-bold tracking-wider uppercase border {{ $statusColorClass }}">
                                {{ $statusLabel }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center">
                    <p class="text-xs text-[#52525B]">No offers matched filters.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination footer if needed --}}
        @if($offers->hasPages())
            <div class="p-3 border-t border-white/5 bg-[#030712]/50 text-xs">
                {{ $offers->links('livewire.shared.pagination-simple') }}
            </div>
        @endif
    </div>

    {{-- ── RIGHT PANEL (Forms or Details) ── --}}
    <div class="flex-1 overflow-y-auto bg-[#030712] p-6 lg:p-8 flex flex-col min-h-screen">
        
        {{-- Mobile Sidebar Trigger Badge --}}
        <div class="lg:hidden mb-4">
            <button @click="mobileListOpen = true" class="px-3 py-1.5 bg-[#111827] border border-white/10 rounded-md text-xs font-semibold text-[#A1A1AA] hover:text-[#FAFAFA] flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                View Offers List ({{ $stats['total'] }})
            </button>
        </div>

        @if($showCreateForm)
            {{-- ──────── SUBMIT NEW OFFER FORM ──────── --}}
            <div class="max-w-2xl mx-auto w-full space-y-6">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-[#FAFAFA]">Submit New Offer</h2>
                    <p class="text-xs text-[#A1A1AA] mt-1">Initiate a formal deal transaction offer under strict agency records.</p>
                </div>

                <form wire:submit.prevent="createOffer" class="space-y-4 bg-[#090d16] border border-white/5 p-6 rounded-lg backdrop-blur-md">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Associated Deal *</label>
                            <select wire:model="deal_id" class="w-full h-10 bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] px-3 focus:outline-none focus:border-[#10B981]">
                                <option value="">Select Deal...</option>
                                @foreach($deals as $d)
                                    <option value="{{ $d->id }}">{{ $d->title }}</option>
                                @endforeach
                            </select>
                            @error('deal_id') <p class="text-[10px] text-[#F43F5E] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Buyer (Contact) *</label>
                            <select wire:model="contact_id" class="w-full h-10 bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] px-3 focus:outline-none focus:border-[#10B981]">
                                <option value="">Select Buyer...</option>
                                @foreach($contacts as $c)
                                    <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                                @endforeach
                            </select>
                            @error('contact_id') <p class="text-[10px] text-[#F43F5E] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Offer Amount ({{ $currencySymbol }}) *</label>
                            <input wire:model="amount" type="number" min="1" placeholder="e.g. 150000000"
                                class="w-full h-10 bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] px-3 focus:outline-none focus:border-[#10B981] font-mono">
                            @error('amount') <p class="text-[10px] text-[#F43F5E] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Offer Type</label>
                            <select wire:model="type" class="w-full h-10 bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] px-3 focus:outline-none focus:border-[#10B981]">
                                <option value="sale">Sale</option>
                                <option value="rental">Rental</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Deposit Amount ({{ $currencySymbol }})</label>
                            <input wire:model="deposit_amount" type="number" min="0" placeholder="e.g. 15000000"
                                class="w-full h-10 bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] px-3 focus:outline-none focus:border-[#10B981] font-mono">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Expiry Date</label>
                            <input wire:model="expiry_date" type="date"
                                class="w-full h-10 bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] px-3 focus:outline-none focus:border-[#10B981]">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Proposed Occupation Date</label>
                            <input wire:model="proposed_occupation_date" type="date"
                                class="w-full h-10 bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] px-3 focus:outline-none focus:border-[#10B981]">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Special Conditions</label>
                            <textarea wire:model="conditions" rows="3" placeholder="e.g. Subject to bond pre-approval within 14 days, structural inspection compliance."
                                class="w-full bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] p-3 focus:outline-none focus:border-[#10B981] resize-none"></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Internal Private Notes</label>
                            <textarea wire:model="notes" rows="2" placeholder="e.g. Buyer is extremely motivated, willing to pay cash if negotiations accelerate."
                                class="w-full bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] p-3 focus:outline-none focus:border-[#10B981] resize-none"></textarea>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="px-5 py-2 bg-[#10B981] hover:bg-[#10B981]/90 text-white text-xs font-semibold rounded-md shadow-md transition-all">Submit Access Offer</button>
                        <button type="button" wire:click="$set('showCreateForm', false)" class="px-4 py-2 border border-white/10 rounded-md text-xs text-[#A1A1AA] hover:bg-white/5 transition-all">Cancel</button>
                    </div>
                </form>
            </div>
        @elseif($showEditForm)
            {{-- ──────── EDIT OFFER FORM ──────── --}}
            <div class="max-w-2xl mx-auto w-full space-y-6">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-[#FAFAFA]">Edit Offer Terms</h2>
                    <p class="text-xs text-[#A1A1AA] mt-1">Adjust terms before official counter-offers or client acceptance response.</p>
                </div>

                <form wire:submit.prevent="saveEdit" class="space-y-4 bg-[#090d16] border border-white/5 p-6 rounded-lg backdrop-blur-md">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Offer Amount ({{ $currencySymbol }}) *</label>
                            <input wire:model="edit_amount" type="number" min="1"
                                class="w-full h-10 bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] px-3 focus:outline-none focus:border-[#10B981] font-mono">
                            @error('edit_amount') <p class="text-[10px] text-[#F43F5E] mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Deposit Amount ({{ $currencySymbol }})</label>
                            <input wire:model="edit_deposit_amount" type="number" min="0"
                                class="w-full h-10 bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] px-3 focus:outline-none focus:border-[#10B981] font-mono">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Expiry Date</label>
                            <input wire:model="edit_expiry_date" type="date"
                                class="w-full h-10 bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] px-3 focus:outline-none focus:border-[#10B981]">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Proposed Occupation Date</label>
                            <input wire:model="edit_proposed_occupation_date" type="date"
                                class="w-full h-10 bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] px-3 focus:outline-none focus:border-[#10B981]">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Special Conditions</label>
                            <textarea wire:model="edit_conditions" rows="3"
                                class="w-full bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] p-3 focus:outline-none focus:border-[#10B981] resize-none"></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Internal Private Notes</label>
                            <textarea wire:model="edit_notes" rows="2"
                                class="w-full bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] p-3 focus:outline-none focus:border-[#10B981] resize-none"></textarea>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="px-5 py-2 bg-[#F59E0B] hover:bg-[#F59E0B]/90 text-black text-xs font-semibold rounded-md shadow-md transition-all">Save Adjustments</button>
                        <button type="button" wire:click="cancelEdit" class="px-4 py-2 border border-white/10 rounded-md text-xs text-[#A1A1AA] hover:bg-white/5 transition-all">Cancel</button>
                    </div>
                </form>
            </div>
        @elseif($detailOffer)
            {{-- ──────── COMPONENT: RIGHT MAIN PANEL DETAIL ──────── --}}
            <div class="flex-1 flex flex-col relative">
                
                {{-- Comparison overlay --}}
                @if($compareMode)
                    <div class="absolute inset-0 bg-[#030712] z-30 overflow-y-auto flex flex-col">
                        <div class="flex items-center justify-between pb-6 border-b border-white/5">
                            <div>
                                <h2 class="text-lg font-semibold text-[#FAFAFA] flex items-center gap-2">
                                    <svg class="h-5 w-5 text-[#10B981]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    Offer Comparison Table
                                </h2>
                                <p class="text-xs text-[#A1A1AA] mt-0.5">Comparing active offers for {{ $detailOffer->listing?->property?->address ?? 'Selected Property' }}</p>
                            </div>
                            <button wire:click="$set('compareMode', false)" class="h-8 px-3 border border-white/10 hover:bg-white/5 rounded-md text-xs font-semibold text-[#A1A1AA] hover:text-[#FAFAFA] transition-all">
                                Back to Detail
                            </button>
                        </div>

                        @php
                            $comparisonOffers = \App\Infrastructure\Persistence\Models\Offer::where('listing_id', $detailOffer->listing_id)->with('contact')->get();
                            
                            $maxAmount = $comparisonOffers->max('amount') ?? 0;
                            $maxDeposit = $comparisonOffers->max('deposit_amount') ?? 0;
                            
                            // Find earliest occupation date
                            $earliestOccupation = null;
                            foreach($comparisonOffers as $co) {
                                if($co->proposed_occupation_date) {
                                    if(!$earliestOccupation || $co->proposed_occupation_date->lt($earliestOccupation)) {
                                        $earliestOccupation = $co->proposed_occupation_date;
                                    }
                                }
                            }
                        @endphp

                        <div class="mt-6 overflow-x-auto border border-white/5 rounded-lg">
                            <table class="w-full border-collapse text-left text-xs">
                                <thead>
                                    <tr class="bg-[#090d16] border-b border-white/5">
                                        <th class="p-4 font-bold text-[#A1A1AA] uppercase tracking-wider w-[180px]">Parameters</th>
                                        @foreach($comparisonOffers as $idx => $co)
                                            <th class="p-4 border-l border-white/5">
                                                <div class="font-bold text-[#FAFAFA]">{{ $co->contact?->full_name ?? 'Buyer ' . ($idx+1) }}</div>
                                                <div class="text-[10px] text-[#A1A1AA] mt-0.5">Submitted {{ $co->created_at->format('d M Y') }}</div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    {{-- Offer Amount --}}
                                    <tr>
                                        <td class="p-4 font-semibold text-[#A1A1AA] bg-[#090d16]/30">Amount</td>
                                        @foreach($comparisonOffers as $co)
                                            @php $isBest = $co->amount >= $maxAmount; @endphp
                                            <td class="p-4 font-mono text-sm border-l border-white/5 {{ $isBest ? 'bg-[#10B981]/10 text-[#10B981] font-bold border-l-2 border-l-[#10B981]' : 'text-[#FAFAFA]' }}">
                                                {{ $currencySymbol }}{{ number_format($co->amount) }}
                                                @if($isBest)<span class="ml-1 text-[8px] tracking-wide uppercase px-1 bg-[#10B981]/20 rounded">Highest</span>@endif
                                            </td>
                                        @endforeach
                                    </tr>

                                    {{-- Deposit --}}
                                    <tr>
                                        <td class="p-4 font-semibold text-[#A1A1AA] bg-[#090d16]/30">Deposit</td>
                                        @foreach($comparisonOffers as $co)
                                            @php $isBest = $co->deposit_amount && $co->deposit_amount >= $maxDeposit; @endphp
                                            <td class="p-4 font-mono border-l border-white/5 {{ $isBest ? 'bg-[#10B981]/10 text-[#10B981] font-bold border-l-2 border-l-[#10B981]' : 'text-[#FAFAFA]' }}">
                                                {{ $co->deposit_amount ? $currencySymbol . number_format($co->deposit_amount) : '—' }}
                                                @if($isBest)<span class="ml-1 text-[8px] tracking-wide uppercase px-1 bg-[#10B981]/20 rounded">Highest</span>@endif
                                            </td>
                                        @endforeach
                                    </tr>

                                    {{-- Occupation Date --}}
                                    <tr>
                                        <td class="p-4 font-semibold text-[#A1A1AA] bg-[#090d16]/30">Occupation Date</td>
                                        @foreach($comparisonOffers as $co)
                                            @php $isBest = $co->proposed_occupation_date && $earliestOccupation && $co->proposed_occupation_date->equalTo($earliestOccupation); @endphp
                                            <td class="p-4 border-l border-white/5 {{ $isBest ? 'bg-[#10B981]/10 text-[#10B981] font-bold border-l-2 border-l-[#10B981]' : 'text-[#A1A1AA]' }}">
                                                {{ $co->proposed_occupation_date?->format('d M Y') ?? 'Immediate' }}
                                                @if($isBest)<span class="ml-1 text-[8px] tracking-wide uppercase px-1 bg-[#10B981]/20 rounded">Earliest</span>@endif
                                            </td>
                                        @endforeach
                                    </tr>

                                    {{-- Conditions --}}
                                    <tr>
                                        <td class="p-4 font-semibold text-[#A1A1AA] bg-[#090d16]/30">Conditions</td>
                                        @foreach($comparisonOffers as $co)
                                            @php $isBest = !$co->conditions || stripos($co->conditions, 'none') !== false; @endphp
                                            <td class="p-4 border-l border-white/5 {{ $isBest ? 'bg-[#10B981]/10 text-[#10B981] font-semibold border-l-2 border-l-[#10B981]' : 'text-[#A1A1AA]' }}">
                                                {{ $co->conditions ?: 'No Conditions (Clean)' }}
                                            </td>
                                        @endforeach
                                    </tr>

                                    {{-- Buyer Pre-approval --}}
                                    <tr>
                                        <td class="p-4 font-semibold text-[#A1A1AA] bg-[#090d16]/30">Buyer Pre-approval</td>
                                        @foreach($comparisonOffers as $co)
                                            @php $isBest = $co->contact && ($co->contact->preferences['pre_approved'] ?? true); @endphp
                                            <td class="p-4 border-l border-white/5 {{ $isBest ? 'bg-[#10B981]/10 text-[#10B981] font-semibold border-l-2 border-l-[#10B981]' : 'text-[#A1A1AA]' }}">
                                                {{ $isBest ? 'Pre-Approved / Cash' : 'Pending Verification' }}
                                            </td>
                                        @endforeach
                                    </tr>

                                    {{-- AI Score/Rec --}}
                                    <tr>
                                        <td class="p-4 font-semibold text-[#A1A1AA] bg-[#090d16]/30">AI Recommendation</td>
                                        @foreach($comparisonOffers as $co)
                                            @php
                                                $rec = 'Standard offer';
                                                if($co->amount >= $maxAmount) {
                                                    $rec = 'Best Price';
                                                } elseif($co->proposed_occupation_date && $earliestOccupation && $co->proposed_occupation_date->equalTo($earliestOccupation)) {
                                                    $rec = 'Fastest Close';
                                                } elseif(!$co->conditions) {
                                                    $rec = 'Cleanest Offer';
                                                }
                                            @endphp
                                            <td class="p-4 border-l border-white/5 bg-[#10B981]/5">
                                                <span class="px-2.5 py-1 rounded bg-[#10B981]/10 border border-[#10B981]/25 text-[#10B981] font-bold uppercase tracking-wider text-[9px]">
                                                    {{ $rec }}
                                                </span>
                                            </td>
                                        @endforeach
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Header information --}}
                @php
                    $detailColors = [
                        'pending' => 'bg-[#0EA5E9]/10 text-[#0EA5E9] border-[#0EA5E9]/20',
                        'countered' => 'bg-[#F59E0B]/10 text-[#F59E0B] border-[#F59E0B]/20',
                        'accepted' => 'bg-[#10B981]/10 text-[#10B981] border-[#10B981]/20',
                        'rejected' => 'bg-[#F43F5E]/10 text-[#F43F5E] border-[#F43F5E]/20',
                        'expired', 'withdrawn' => 'bg-white/5 text-[#52525B] border-white/10',
                    ];
                    $detailColor = $detailColors[$detailOffer->status] ?? 'bg-white/5 text-[#A1A1AA] border-white/10';
                    $detailLabel = match ($detailOffer->status) {
                        'pending' => 'Submitted',
                        'countered' => 'Counter-Offered',
                        'accepted' => 'Accepted',
                        'rejected' => 'Declined',
                        'expired', 'withdrawn' => 'Expired',
                        default => ucfirst($detailOffer->status),
                    };

                    // Listing prices and calculations
                    $lPrice = (float) ($detailOffer->listing->listing_price ?? ($detailOffer->amount * 1.05));
                    $oAmount = (float) $detailOffer->amount;
                    $diffPct = $lPrice > 0 ? round((($lPrice - $oAmount) / $lPrice) * 100) : 8;
                    $reccCounter = $oAmount * 1.06;

                    // Offers count for comparison badge
                    $offersCount = \App\Infrastructure\Persistence\Models\Offer::where('listing_id', $detailOffer->listing_id)->count();
                @endphp

                <div class="pb-6 border-b border-white/5 flex flex-col md:flex-row md:items-start justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="text-xl font-bold tracking-tight text-[#FAFAFA] font-sans">
                                {{ $detailOffer->listing?->property?->address_line_1 ?? 'Address line missing' }}
                            </h1>
                            <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold tracking-wider uppercase border {{ $detailColor }}">
                                {{ $detailLabel }}
                            </span>
                        </div>
                        <p class="text-xs text-[#A1A1AA]">
                            Buyer: <span class="text-[#FAFAFA] font-semibold">{{ $detailOffer->contact?->full_name }}</span> · Submitted by agent <span class="text-[#FAFAFA] font-medium">{{ $detailOffer->submittedBy?->first_name ?? 'System' }}</span> on {{ $detailOffer->created_at->format('M d, Y \a\t H:i') }}
                        </p>
                    </div>

                    <div class="flex flex-col items-end gap-1.5 shrink-0">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#52525B]">Offer Value</div>
                        <div class="text-4xl font-extrabold font-mono text-[#FAFAFA] tracking-tight">
                            {{ $currencySymbol }}{{ number_format($detailOffer->amount) }}
                        </div>
                        
                        @if($offersCount > 1)
                            <button wire:click="$set('compareMode', true)" class="mt-1 inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-[#10B981]/10 border border-[#10B981]/25 text-[10px] font-bold uppercase tracking-wider text-[#10B981] hover:bg-[#10B981]/20 transition-all">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                Compare {{ $offersCount }} Offers
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Three-Column Detail Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 py-6 border-b border-white/5">
                    
                    {{-- Column 1: Offer Terms --}}
                    <div class="space-y-3 bg-[#090d16]/30 border border-white/5 rounded-lg p-4">
                        <h3 class="text-xs font-bold text-[#FAFAFA] uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-[#10B981]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Offer Terms
                        </h3>
                        <div class="space-y-2 text-xs font-sans">
                            <div class="flex justify-between py-1 border-b border-white/5">
                                <span class="text-[#A1A1AA]">Proposed Price</span>
                                <span class="font-mono font-semibold text-[#FAFAFA]">{{ $currencySymbol }}{{ number_format($detailOffer->amount) }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-white/5">
                                <span class="text-[#A1A1AA]">Required Deposit</span>
                                <span class="font-mono font-semibold text-[#FAFAFA]">
                                    {{ $detailOffer->deposit_amount ? $currencySymbol . number_format($detailOffer->deposit_amount) : '—' }}
                                </span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-white/5">
                                <span class="text-[#A1A1AA]">Occupation Date</span>
                                <span class="text-[#FAFAFA] font-medium">{{ $detailOffer->proposed_occupation_date?->format('d M Y') ?? 'Immediate' }}</span>
                            </div>
                            <div class="pt-1.5">
                                <span class="block text-[10px] uppercase font-bold text-[#A1A1AA] mb-1">Conditions</span>
                                <p class="text-xs text-[#FAFAFA] leading-relaxed bg-[#111827]/80 p-2 border border-white/5 rounded">
                                    {{ $detailOffer->conditions ?: 'No subject-to conditions. Clean offer.' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Column 2: Buyer Profile Summary --}}
                    <div class="space-y-3 bg-[#090d16]/30 border border-white/5 rounded-lg p-4">
                        <h3 class="text-xs font-bold text-[#FAFAFA] uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-[#10B981]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Buyer Profile
                        </h3>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1 border-b border-white/5">
                                <span class="text-[#A1A1AA]">Stated Budget</span>
                                <span class="font-mono font-semibold text-[#FAFAFA]">
                                    {{ $currencySymbol }}{{ number_format($detailOffer->contact->preferences['budget'] ?? ($detailOffer->amount * 1.1)) }}
                                </span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-white/5">
                                <span class="text-[#A1A1AA]">Pre-approval Status</span>
                                <span class="text-[#10B981] font-semibold flex items-center gap-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#10B981]"></span>
                                    {{ $detailOffer->contact->preferences['pre_approved'] ?? true ? 'Pre-Approved' : 'Cash Buyer' }}
                                </span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-white/5">
                                <span class="text-[#A1A1AA]">Active CRM Offers</span>
                                <span class="text-[#FAFAFA] font-medium">{{ $detailOffer->contact->offers()->count() }} total</span>
                            </div>
                            <div class="pt-1.5">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[10px] uppercase font-bold text-[#A1A1AA]">Intent Score</span>
                                    <span class="text-[10px] font-mono font-bold text-[#10B981]">{{ $detailOffer->contact->intent_score ?? 85 }}%</span>
                                </div>
                                <div class="h-1.5 w-full bg-[#111827] rounded-full overflow-hidden border border-white/5">
                                    <div class="h-full bg-[#10B981]" style="width: {{ $detailOffer->contact->intent_score ?? 85 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Column 3: Property Context --}}
                    <div class="space-y-3 bg-[#090d16]/30 border border-white/5 rounded-lg p-4">
                        <h3 class="text-xs font-bold text-[#FAFAFA] uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-[#10B981]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            Property Context
                        </h3>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1 border-b border-white/5">
                                <span class="text-[#A1A1AA]">Asking Price</span>
                                <span class="font-mono font-semibold text-[#FAFAFA]">{{ $currencySymbol }}{{ number_format($lPrice) }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-white/5">
                                <span class="text-[#A1A1AA]">Days on Market</span>
                                <span class="text-[#FAFAFA] font-medium">{{ $detailOffer->listing->days_on_market ?? 28 }} Days</span>
                            </div>
                            
                            <div class="pt-1">
                                <span class="block text-[10px] uppercase font-bold text-[#A1A1AA] mb-1.5">Comparable Sales (Ikoyi/Sub-market)</span>
                                @php
                                    $comparables = $currencySymbol === 'R' 
                                        ? [['addr' => 'Sea Point Studio', 'price' => 'R4,100,000', 'date' => '2w ago'], ['addr' => 'Green Point Apt', 'price' => 'R4,350,000', 'date' => '1m ago'], ['addr' => 'Bantry Bay 1BR', 'price' => 'R4,600,000', 'date' => '2m ago']]
                                        : [['addr' => 'Ikoyi 3BR Flat', 'price' => '₦190M', 'date' => '3w ago'], ['addr' => 'Ikoyi Penthouse', 'price' => '₦210M', 'date' => '1m ago'], ['addr' => 'Victoria Island 3BR', 'price' => '₦185M', 'date' => '2m ago']];
                                @endphp
                                <div class="space-y-1.5 max-h-[70px] overflow-y-auto pr-1">
                                    @foreach($comparables as $comp)
                                        <div class="flex items-center justify-between text-[10px] text-[#A1A1AA] bg-[#111827]/40 px-2 py-1 rounded border border-white/5">
                                            <span class="truncate max-w-[100px]">{{ $comp['addr'] }}</span>
                                            <span class="font-mono font-bold text-[#FAFAFA]">{{ $comp['price'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- AI Insight Card --}}
                <div class="my-6 p-4 rounded-lg bg-[#10B981]/5 border-l-4 border-[#10B981] flex items-start gap-3 relative overflow-hidden shimmer-bg">
                    <div class="h-6 w-6 rounded-md bg-[#10B981]/10 flex items-center justify-center border border-[#10B981]/25 text-[#10B981] shrink-0">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="space-y-1 font-sans">
                        <div class="text-[10px] font-black uppercase tracking-wider text-[#10B981]">Neural AI Recommendation</div>
                        <p class="text-xs text-[#FAFAFA] leading-relaxed">
                            This offer is <span class="font-bold text-[#10B981]">{{ $diffPct }}% below</span> asking. Based on comparable sales in this sub-market this quarter, the property is realistically priced. The buyer has strong mortgage pre-approval verified status. **Recommend**: counter at <span class="font-mono font-bold text-[#F59E0B]">{{ $currencySymbol }}{{ number_format($reccCounter) }}</span> with 14-day validity.
                        </p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap gap-3 items-center">
                    @if($detailOffer->status === 'pending')
                        <button wire:click="acceptOffer({{ $detailOffer->id }})" class="h-10 px-5 bg-[#10B981] hover:bg-[#10B981]/90 text-white font-semibold rounded-md text-xs flex items-center justify-center gap-1.5 transition-all shadow-[0_2px_8px_rgba(16,185,129,0.2)]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            Accept Offer
                        </button>
                        <button wire:click="openCounterForm({{ $detailOffer->id }})" class="h-10 px-5 bg-[#F59E0B] hover:bg-[#F59E0B]/90 text-black font-semibold rounded-md text-xs flex items-center justify-center gap-1.5 transition-all shadow-[0_2px_8px_rgba(245,158,11,0.2)]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5L12 10.5L15.75 7.5M12 10.5V16.5"/></svg>
                            Counter-Offer
                        </button>
                        <button wire:click="rejectOffer({{ $detailOffer->id }})" class="h-10 px-5 border border-[#F43F5E] text-[#F43F5E] hover:bg-[#F43F5E]/10 font-semibold rounded-md text-xs transition-all">
                            Decline
                        </button>
                        <button wire:click="openEditForm({{ $detailOffer->id }})" class="h-10 px-4 border border-white/10 text-[#A1A1AA] hover:text-[#FAFAFA] hover:bg-white/5 font-semibold rounded-md text-xs transition-all">
                            Edit Terms
                        </button>
                    @elseif($detailOffer->status === 'countered')
                        <button wire:click="acceptOffer({{ $detailOffer->id }})" class="h-10 px-5 bg-[#10B981] hover:bg-[#10B981]/90 text-white font-semibold rounded-md text-xs flex items-center justify-center gap-1.5 transition-all">
                            Accept Counter
                        </button>
                        <button wire:click="openCounterForm({{ $detailOffer->id }})" class="h-10 px-5 bg-[#F59E0B] hover:bg-[#F59E0B]/90 text-black font-semibold rounded-md text-xs flex items-center justify-center gap-1.5 transition-all">
                            Re-Counter
                        </button>
                        <button wire:click="rejectOffer({{ $detailOffer->id }})" class="h-10 px-5 border border-[#F43F5E] text-[#F43F5E] hover:bg-[#F43F5E]/10 font-semibold rounded-md text-xs transition-all">
                            Decline
                        </button>
                    @else
                        @if(in_array($detailOffer->status, ['pending', 'expired', 'withdrawn', 'rejected']))
                            <button wire:click="deleteOffer({{ $detailOffer->id }})" onclick="return confirm('Delete this offer record?')" class="h-10 px-5 border border-[#F43F5E] text-[#F43F5E] hover:bg-[#F43F5E]/10 font-semibold rounded-md text-xs transition-all">
                                Delete Record
                            </button>
                        @endif
                    @endif
                </div>

                {{-- Counter-Offer Inline Form --}}
                @if($showCounterForm && $counterOfferId === $detailOffer->id)
                    <div class="mt-6 p-5 border border-[#F59E0B]/20 bg-[#F59E0B]/5 rounded-lg space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-[#F59E0B] uppercase tracking-wider">Propose Counter-Offer Terms</h4>
                            <button wire:click="$set('showCounterForm', false)" class="text-[#A1A1AA] hover:text-[#FAFAFA] text-lg leading-none">&times;</button>
                        </div>

                        <form wire:submit.prevent="submitCounter" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Counter Amount ({{ $currencySymbol }}) *</label>
                                <input wire:model="counter_amount" type="number" min="1" placeholder="e.g. 195000000"
                                    class="w-full h-10 bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] px-3 focus:outline-none focus:border-[#F59E0B] font-mono">
                                @error('counter_amount') <p class="text-[10px] text-[#F43F5E] mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Validity Period (Expiry)</label>
                                <input type="date" value="{{ now()->addDays(3)->toDateString() }}"
                                    class="w-full h-10 bg-[#111827] border border-white/10 rounded-md text-xs text-[#A1A1AA] px-3 focus:outline-none focus:border-[#F59E0B]">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Message / Adjustments to conditions</label>
                                <textarea wire:model="counter_notes" rows="2" placeholder="e.g. We propose to sell at ₦195M but can offer immediate occupation if preferred."
                                    class="w-full bg-[#111827] border border-white/10 rounded-md text-xs text-[#FAFAFA] p-3 focus:outline-none focus:border-[#F59E0B] resize-none"></textarea>
                            </div>

                            <div class="md:col-span-2 flex gap-3">
                                <button type="submit" class="px-5 py-2 bg-[#F59E0B] hover:bg-[#F59E0B]/90 text-black text-xs font-bold rounded-md shadow-md transition-all">Send Counter-Offer</button>
                                <button type="button" wire:click="$set('showCounterForm', false)" class="px-4 py-2 border border-white/10 rounded-md text-xs text-[#A1A1AA] hover:bg-white/5 transition-all">Cancel</button>
                            </div>
                        </form>
                    </div>
                @endif

                {{-- History of counters (Timeline thread) --}}
                <div class="mt-8 pt-6 border-t border-white/5">
                    <h3 class="text-xs font-bold text-[#FAFAFA] uppercase tracking-wider mb-4 flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-[#A1A1AA]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Negotiation Thread
                    </h3>
                    
                    <div class="relative pl-6 border-l border-white/10 space-y-5">
                        
                        {{-- Thread item 2: Counter offer (if countered) --}}
                        @if($detailOffer->status === 'countered' || $detailOffer->counter_amount)
                            <div class="relative">
                                <span class="absolute -left-[30px] top-1.5 h-2 w-2 rounded-full bg-[#F59E0B] ring-4 ring-[#030712]"></span>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-[#F59E0B]">Counter-Offer Proposed</span>
                                        <span class="text-[9px] text-[#52525B] font-mono">{{ $detailOffer->responded_at?->format('d M Y H:i') ?? 'Just now' }}</span>
                                    </div>
                                    <p class="text-xs text-[#FAFAFA]">
                                        Counter value set at <span class="font-mono font-bold text-[#FAFAFA]">{{ $currencySymbol }}{{ number_format($detailOffer->counter_amount) }}</span>.
                                    </p>
                                    @if($detailOffer->counter_notes)
                                        <p class="text-[11px] text-[#A1A1AA] italic leading-relaxed bg-[#111827]/40 border border-white/5 p-2 rounded">
                                            "{{ $detailOffer->counter_notes }}"
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Thread item 1: Original Offer --}}
                        <div class="relative">
                            <span class="absolute -left-[30px] top-1.5 h-2 w-2 rounded-full bg-[#0EA5E9] ring-4 ring-[#030712]"></span>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-[#0EA5E9]">Original Offer Submitted</span>
                                    <span class="text-[9px] text-[#52525B] font-mono">{{ $detailOffer->created_at->format('d M Y H:i') }}</span>
                                </div>
                                <p class="text-xs text-[#FAFAFA]">
                                    {{ $detailOffer->contact?->full_name }} submitted a formal proposal for <span class="font-mono font-semibold text-[#FAFAFA]">{{ $currencySymbol }}{{ number_format($detailOffer->amount) }}</span>.
                                </p>
                                @if($detailOffer->notes)
                                    <p class="text-[11px] text-[#A1A1AA] italic leading-relaxed bg-[#111827]/40 border border-white/5 p-2 rounded">
                                        "{{ $detailOffer->notes }}"
                                    </p>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        @else
            {{-- Empty State (No offer selected/exists) --}}
            <div class="flex-1 flex flex-col items-center justify-center py-12 text-center">
                <div class="h-12 w-12 bg-[#111827] border border-white/5 rounded-md flex items-center justify-center text-[#52525B] mb-4">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 12H4"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-[#FAFAFA]">No active offers</h3>
                <p class="text-xs text-[#A1A1AA] mt-1">Submit a new offer or adjust filter terms to begin negotiation comparisons.</p>
            </div>
        @endif

    </div>

</div>
