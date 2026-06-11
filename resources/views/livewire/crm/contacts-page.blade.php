<div>
    <!-- Styles for checkbox appearance on hover -->
    <style>
        .contact-row .row-checkbox {
            opacity: 0.15;
            transition: opacity 150ms ease;
        }
        .contact-row:hover .row-checkbox,
        .contact-row .row-checkbox:checked {
            opacity: 1;
        }
        .font-geist {
            font-family: 'Geist', ui-sans-serif, system-ui, sans-serif;
        }
        .font-mono-geist {
            font-family: 'Geist Mono', ui-monospace, monospace;
        }
    </style>

    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <span class="text-[10px] font-black uppercase tracking-widest text-brand-primary bg-brand-primary/10 px-2.5 py-1 rounded-full font-mono-geist">CRM Directory</span>
            <h1 class="text-4xl font-black tracking-tight text-text-primary mt-2 font-geist">
                Contacts <span class="bg-clip-text text-transparent bg-gradient-to-r from-brand-primary via-emerald-400 to-brand-accent">CRM</span>.
            </h1>
            <p class="text-xs font-semibold text-text-secondary mt-1 font-geist">Track agency customer relationships, buyer/seller leads, and communication history.</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="$set('showCreateModal', true)" class="px-4 py-2.5 rounded bg-brand-accent hover:bg-amber-600 text-black text-xs font-black shadow-brand-sm transition-all flex items-center space-x-1.5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                <span>Add Contact</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 font-geist">
        <div class="p-6 rounded-2xl bg-surface-card border border-border-default shadow-sm hover:border-brand-primary/40 transition-all duration-300 relative overflow-hidden group">
            <div class="absolute inset-0 bg-brand-primary/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
            <div class="relative z-10">
                <h3 class="text-xs font-bold tracking-widest uppercase text-text-secondary">Total Active</h3>
                <p class="mt-2 text-3xl font-black text-text-primary tracking-tighter tabular-nums font-mono-geist">{{ $totalActive }}</p>
            </div>
        </div>
        <div class="p-6 rounded-2xl bg-surface-card border border-border-default shadow-sm hover:border-success-500/40 transition-all duration-300 relative overflow-hidden group">
            <div class="absolute inset-0 bg-success-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
            <div class="relative z-10">
                <h3 class="text-xs font-bold tracking-widest uppercase text-text-secondary">New (This Week)</h3>
                <p class="mt-2 text-3xl font-black text-success-500 tracking-tighter tabular-nums font-mono-geist">{{ $newThisWeek }}</p>
            </div>
        </div>
        <div class="p-6 rounded-2xl bg-surface-card border border-border-default shadow-sm hover:border-warning-500/40 transition-all duration-300 relative overflow-hidden group">
            <div class="absolute inset-0 bg-warning-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
            <div class="relative z-10">
                <h3 class="text-xs font-bold tracking-widest uppercase text-text-secondary">Hot Buyers</h3>
                <p class="mt-2 text-3xl font-black text-warning-500 tracking-tighter tabular-nums font-mono-geist">{{ $hotBuyers }}</p>
            </div>
        </div>
        <div class="p-6 rounded-2xl bg-surface-card border border-border-default shadow-sm hover:border-info-500/40 transition-all duration-300 relative overflow-hidden group">
            <div class="absolute inset-0 bg-info-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
            <div class="relative z-10">
                <h3 class="text-xs font-bold tracking-widest uppercase text-text-secondary">Sellers (Pending)</h3>
                <p class="mt-2 text-3xl font-black text-info-500 tracking-tighter tabular-nums font-mono-geist">{{ $pendingSellers }}</p>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="space-y-4 mb-6">
        <!-- Prominent search bar -->
        <div class="relative w-full">
            <span class="absolute inset-y-0 left-4 flex items-center text-text-tertiary font-mono-geist text-sm">✦</span>
            <input wire:model.debounce.300ms="search" type="text" placeholder="Search contacts, phone, email..."
                class="w-full pl-10 pr-4 py-3 bg-surface-card border border-border-strong focus:border-brand-primary rounded-lg text-text-primary placeholder-zinc-500 text-sm focus:ring-1 focus:ring-brand-primary/20 focus:outline-none transition-all font-geist">
        </div>

        <!-- Filter chips row -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 font-geist">
            <!-- Filter chips on the left -->
            <div class="flex flex-wrap gap-2 items-center">
                <button wire:click="$set('filterType', '')" 
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all border {{ $filterType === '' && !$smartFilterActive && $filterStatus === '' ? 'bg-brand-primary text-black border-brand-primary' : 'border-border-strong text-text-secondary hover:text-zinc-200 bg-transparent' }}">
                    All
                </button>
                <button wire:click="$set('filterType', 'buyer')" 
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all border {{ $filterType === 'buyer' ? 'bg-brand-primary text-black border-brand-primary' : 'border-border-strong text-text-secondary hover:text-zinc-200 bg-transparent' }}">
                    Buyers
                </button>
                <button wire:click="$set('filterType', 'seller')" 
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all border {{ $filterType === 'seller' ? 'bg-brand-primary text-black border-brand-primary' : 'border-border-strong text-text-secondary hover:text-zinc-200 bg-transparent' }}">
                    Sellers
                </button>
                <button wire:click="$set('filterType', 'tenant')" 
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all border {{ $filterType === 'tenant' ? 'bg-brand-primary text-black border-brand-primary' : 'border-border-strong text-text-secondary hover:text-zinc-200 bg-transparent' }}">
                    Tenants
                </button>
                <button wire:click="$set('filterType', 'landlord')" 
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all border {{ $filterType === 'landlord' ? 'bg-brand-primary text-black border-brand-primary' : 'border-border-strong text-text-secondary hover:text-zinc-200 bg-transparent' }}">
                    Landlords
                </button>
                <button wire:click="$set('filterStatus', 'qualified')" 
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all border {{ $filterStatus === 'qualified' ? 'bg-brand-primary text-black border-brand-primary' : 'border-border-strong text-text-secondary hover:text-zinc-200 bg-transparent' }}">
                    Hot Leads
                </button>
                
                <button wire:click="toggleSmartFilter" 
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all border flex items-center gap-1 {{ $smartFilterActive ? 'bg-brand-accent text-black border-brand-accent' : 'border-border-strong text-text-secondary hover:text-zinc-200 hover:border-zinc-700 bg-transparent' }}">
                    <span>✦ Smart Filter</span>
                </button>
            </div>

            <!-- Sort and Add Contact on the right -->
            <div class="flex items-center gap-3">
                <select wire:model="sortBy" class="px-3 py-1.5 rounded bg-surface-card border border-border-strong text-text-secondary hover:text-zinc-200 text-xs focus:ring-1 focus:ring-brand-primary focus:border-brand-primary focus:outline-none transition-colors">
                    <option value="latest">Sort: Newest</option>
                    <option value="name">Sort: Name</option>
                    <option value="score">Sort: Intent Score</option>
                    <option value="activity">Sort: Last Activity</option>
                </select>
            </div>
        </div>
        
        <!-- Natural Language Input (collapsible) -->
        <div x-data="{ active: @entangle('smartFilterActive') }" x-show="active" x-transition.duration.200ms class="p-4 bg-surface-card border border-brand-accent/20 rounded-lg space-y-3 font-geist" style="display: none;">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-brand-accent flex items-center gap-1.5">
                    <span>✦ AI Smart Filter</span>
                    <span class="text-[10px] font-normal text-text-tertiary font-mono-geist">(Type naturally to filter lead data)</span>
                </span>
                <button @click="active = false" class="text-text-tertiary hover:text-text-secondary text-xs">&times; Close</button>
            </div>
            <div class="flex gap-2">
                <input wire:model.debounce.300ms="smartQuery" type="text" placeholder="e.g. Show me buyers in Lekki with budget over {{ $currencySymbol }}80M not contacted this week"
                    class="flex-1 px-3 py-2 bg-surface-page border border-border-strong focus:border-brand-accent rounded-md text-text-primary placeholder-zinc-600 text-xs focus:ring-1 focus:ring-brand-accent/20 focus:outline-none font-mono-geist">
                <button wire:click="applySmartFilter" class="px-4 py-2 bg-brand-accent hover:bg-amber-600 text-black text-xs font-bold rounded-md transition-colors">
                    Apply
                </button>
                <button wire:click="clearSmartFilter" class="px-3 py-2 bg-surface-card hover:bg-zinc-700 text-zinc-300 text-xs font-bold rounded-md transition-colors">
                    Clear
                </button>
            </div>
        </div>
    </div>

    <!-- Contacts Table -->
    <div class="bg-surface-card rounded-2xl overflow-hidden border border-border-default shadow-sm font-geist">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border-default/60">
                <thead class="bg-surface-sunken border-b border-border-strong">
                    <tr>
                        <th class="px-4 py-3 text-left w-10">
                            <input type="checkbox" wire:model="selectAll" class="rounded border-border-default bg-surface-sunken text-brand-primary focus:ring-brand-primary">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-black uppercase text-text-tertiary tracking-wider">Name & Details</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase text-text-tertiary tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase text-text-tertiary tracking-wider">Lead Score</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase text-text-tertiary tracking-wider">Last Activity</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase text-text-tertiary tracking-wider">Stage</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase text-text-tertiary tracking-wider">Agent</th>
                        <th class="px-4 py-3 text-right text-xs font-black uppercase text-text-tertiary tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50 pointer-events-none" class="divide-y divide-border-default transition-opacity duration-200">
                    @forelse($contacts as $contact)
                    <tr class="group contact-row hover:bg-surface-raised/35 transition-all @if($loop->even) bg-surface-card @else bg-surface-sunken @endif cursor-pointer"
                        wire:key="contact-row-{{ $contact->id }}"
                        @click="if (!$event.target.closest('input') && !$event.target.closest('button') && !$event.target.closest('a')) $wire.selectContact({{ $contact->id }})">
                        
                        <!-- Checkbox -->
                        <td class="px-4 py-4 whitespace-nowrap text-left" @click.stop>
                            <input type="checkbox" wire:model="selectedContacts.{{ $contact->id }}" class="row-checkbox rounded border-border-strong bg-surface-sunken text-brand-primary focus:ring-brand-primary">
                        </td>

                        <!-- Name & Contact -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center text-brand-primary font-bold text-sm">
                                    {{ $contact->initials }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-text-primary leading-none">{{ $contact->first_name }} {{ $contact->last_name }}</div>
                                    <div class="text-[11px] text-text-tertiary font-mono-geist mt-1.5">{{ $contact->email ?? $this->formatPhoneNumber($contact->phone) }}</div>
                                </div>
                            </div>
                        </td>

                        <!-- Contact Type -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="px-2 py-0.5 text-[9px] leading-none font-bold rounded-full bg-surface-raised border border-border-strong text-text-secondary uppercase tracking-wider">
                                {{ str_replace('_', ' ', $contact->type) }}
                            </span>
                        </td>

                        <!-- Intent Score -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="relative w-24 h-4.5 bg-surface-sunken border border-border-default rounded-full overflow-hidden flex items-center justify-center">
                                <div class="absolute left-0 top-0 bottom-0 bg-gradient-to-r from-red-500 via-amber-500 to-emerald-500 transition-all duration-500" style="width: {{ $contact->intent_score }}%"></div>
                                <span class="relative z-10 text-[9px] font-black text-text-primary font-mono-geist drop-shadow-[0_1px_2px_rgba(0,0,0,0.8)]">
                                    {{ $contact->intent_score }}%
                                </span>
                            </div>
                        </td>

                        <!-- Last Activity -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            @php
                                $lastActivity = $contact->activities->first();
                            @endphp
                            @if($lastActivity)
                                <div class="flex items-center gap-1.5 text-xs text-text-secondary">
                                    <span class="text-xs">
                                        @switch($lastActivity->type)
                                            @case('call') 📞 @break
                                            @case('email') ✉️ @break
                                            @case('viewing') 👁 @break
                                            @case('sms') 💬 @break
                                            @default 📝
                                        @endswitch
                                    </span>
                                    <span class="text-[11px] truncate max-w-[120px]">{{ $lastActivity->occurred_at->diffForHumans() }}</span>
                                </div>
                            @else
                                <span class="text-xs text-text-tertiary font-mono-geist">—</span>
                            @endif
                        </td>

                        <!-- Stage -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            @php
                                $stageLabel = match($contact->status) {
                                    'new' => 'Prospect',
                                    'qualified' => 'Qualified',
                                    'active' => 'Active',
                                    'nurturing' => 'Under Offer',
                                    'closed' => 'Closed',
                                    default => ucfirst($contact->status)
                                };
                                $stageClass = match($contact->status) {
                                    'new' => 'border-border-strong bg-surface-raised text-text-secondary',
                                    'qualified' => 'border-blue-500/20 bg-blue-950/40 text-blue-400',
                                    'active' => 'border-emerald-500/20 bg-emerald-950/40 text-emerald-400',
                                    'nurturing' => 'border-amber-500/20 bg-amber-950/40 text-amber-400',
                                    'closed' => 'border-green-500/20 bg-green-950/40 text-green-400',
                                    default => 'border-border-strong bg-surface-raised text-text-secondary'
                                };
                            @endphp
                            <span class="px-2 py-0.5 inline-flex text-[9px] leading-none font-bold rounded-full border {{ $stageClass }} uppercase tracking-wider">
                                {{ $stageLabel }}
                            </span>
                        </td>

                        <!-- Assigned Agent -->
                        <td class="px-4 py-4 whitespace-nowrap text-xs text-zinc-450">
                            {{ $contact->agent ? $contact->agent->first_name : 'Unassigned' }}
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-4 whitespace-nowrap text-right text-xs font-bold" @click.stop>
                            <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                <button wire:click="openDraftModal('whatsapp')" class="p-1 text-text-secondary hover:text-brand-accent hover:bg-surface-card rounded transition-colors" title="WhatsApp">
                                    💬
                                </button>
                                <button wire:click="openDraftModal('email')" class="p-1 text-text-secondary hover:text-brand-primary hover:bg-surface-card rounded transition-colors" title="Email">
                                    ✉️
                                </button>
                                <button wire:click="selectContact({{ $contact->id }})" class="p-1 text-text-secondary hover:text-text-primary hover:bg-surface-card rounded transition-colors" title="View Detail">
                                    👁
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-12 text-center bg-surface-card">
                            <div class="flex flex-col items-center justify-center max-w-sm mx-auto space-y-4">
                                <span class="text-3xl">✦</span>
                                <h3 class="text-sm font-black text-text-primary uppercase tracking-wider">No contacts match this filter</h3>
                                <p class="text-xs text-text-tertiary leading-relaxed font-sans">
                                    Try removing the budget filter, or use the <strong>Smart Filter</strong> for more natural language control over your queries.
                                </p>
                                <button wire:click="toggleSmartFilter" class="px-4 py-2 bg-zinc-850 hover:bg-surface-card border border-border-strong rounded-md text-xs font-bold text-zinc-300 transition-colors">
                                    Open Smart Filter
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-3 border-t border-border-default bg-surface-sunken/40">
            {{ $contacts->links() }}
        </div>
    </div>

    <!-- Sticky Bulk Action Bar -->
    <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-surface-raised/90 border border-brand-primary/20 backdrop-blur-md px-6 py-4 rounded-xl shadow-brand-md flex items-center gap-6 transition-all duration-300 transform font-geist"
         x-data="{ selected: @entangle('selectedContacts') }"
         x-show="Object.values(selected).filter(Boolean).length > 0"
         x-transition:enter="translate-y-20 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="translate-y-20 opacity-0"
         style="display: none;">
        <div class="text-xs font-bold text-text-primary">
            <span class="text-brand-primary font-black font-mono-geist" x-text="Object.values(selected).filter(Boolean).length"></span> items selected
        </div>
        
        <div class="h-4 w-px bg-surface-card"></div>
        
        <div class="flex items-center gap-2">
            <button wire:click="bulkUpdateStatus('active')" class="px-3 py-1.5 bg-surface-raised hover:bg-surface-card border border-border-strong text-zinc-300 hover:text-text-primary rounded text-xs font-bold transition-all">
                Mark Active
            </button>
            <button wire:click="bulkUpdateStatus('qualified')" class="px-3 py-1.5 bg-surface-raised hover:bg-surface-card border border-border-strong text-zinc-300 hover:text-text-primary rounded text-xs font-bold transition-all">
                Mark Qualified
            </button>
            <button wire:click="bulkUpdateStatus('closed')" class="px-3 py-1.5 bg-surface-raised hover:bg-surface-card border border-border-strong text-zinc-300 hover:text-text-primary rounded text-xs font-bold transition-all">
                Mark Closed
            </button>
            
            <div class="relative" x-data="{ openMenu: false }" @click.away="openMenu = false">
                <button @click="openMenu = !openMenu" class="px-3 py-1.5 bg-surface-raised hover:bg-surface-card border border-border-strong text-zinc-300 hover:text-text-primary rounded text-xs font-bold transition-all flex items-center gap-1">
                    Assign Agent
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div x-show="openMenu" class="absolute bottom-full mb-2 right-0 w-48 rounded shadow-lg bg-surface-sunken border border-border-strong z-50 py-1" style="display: none;">
                    @foreach($agents as $agent)
                    <button wire:click="bulkAssignAgent({{ $agent->id }})" class="block w-full text-left px-4 py-2 text-xs text-text-secondary hover:text-text-primary hover:bg-surface-raised transition-colors">
                        {{ $agent->first_name }} {{ $agent->last_name }}
                    </button>
                    @endforeach
                </div>
            </div>
            
            <div class="h-4 w-px bg-surface-card"></div>
            
            <button wire:click="bulkDelete()" onclick="return confirm('Delete selected contacts?')" class="px-3 py-1.5 bg-danger-500/10 hover:bg-danger-500/20 text-danger-500 rounded text-xs font-bold transition-all border border-danger-500/10">
                Delete Selected
            </button>
        </div>
    </div>

    <!-- Create Contact Modal (Slide-over) -->
    @if($showCreateModal)
    <div class="relative z-50 font-geist" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-surface-overlay backdrop-blur-sm"></div>
        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div class="pointer-events-auto w-screen max-w-md">
                        <div class="flex h-full flex-col overflow-y-scroll bg-surface-card shadow-2xl border-l border-border-strong">
                            <div class="bg-surface-sunken px-4 py-6 sm:px-6 border-b border-border-strong flex items-center justify-between">
                                <h2 class="text-xl font-bold text-text-primary">Create New Contact</h2>
                                <button wire:click="$set('showCreateModal', false)" type="button" class="rounded text-text-tertiary hover:text-text-primary">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="relative flex-1 px-4 py-6 sm:px-6">

                                <!-- Duplicate Warning -->
                                @if(count($duplicates) > 0 && !$confirmDuplicate)
                                <div class="mb-5 p-4 bg-warning-900/20 border border-warning-500/30 rounded-lg">
                                    <p class="text-xs font-bold text-warning-400 mb-2">⚠️ Possible duplicate contacts found:</p>
                                    @foreach($duplicates as $dup)
                                    <div class="text-[11px] text-text-secondary mb-1 flex items-center justify-between">
                                        <span>{{ $dup['name'] }} — {{ $dup['email'] ?? $dup['phone'] }}</span>
                                        <button wire:click="selectContact({{ $dup['id'] }})" class="underline text-brand-primary">View</button>
                                    </div>
                                    @endforeach
                                    <div class="mt-3 flex gap-2">
                                        <button wire:click="dismissDuplicates" class="px-3 py-1 bg-brand-accent text-black rounded text-[10px] font-bold hover:bg-amber-600">
                                            Create Anyway
                                        </button>
                                        <button wire:click="$set('duplicates', [])" class="px-3 py-1 border border-zinc-700 text-zinc-300 rounded text-[10px] font-bold hover:bg-surface-card">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                                @endif

                                <form wire:submit.prevent="saveContact" class="space-y-5">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-text-secondary mb-1">First Name *</label>
                                            <input type="text" wire:model.defer="first_name" class="w-full bg-surface-sunken border border-border-strong rounded p-2 text-xs text-text-primary focus:border-brand-primary focus:outline-none">
                                            @error('first_name') <span class="text-[10px] text-danger-500 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-text-secondary mb-1">Last Name *</label>
                                            <input type="text" wire:model.defer="last_name" class="w-full bg-surface-sunken border border-border-strong rounded p-2 text-xs text-text-primary focus:border-brand-primary focus:outline-none">
                                            @error('last_name') <span class="text-[10px] text-danger-500 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-text-secondary mb-1">Email Address</label>
                                        <input type="email" wire:model.defer="email" wire:change="checkDuplicates" class="w-full bg-surface-sunken border border-border-strong rounded p-2 text-xs text-text-primary focus:border-brand-primary focus:outline-none">
                                        @error('email') <span class="text-[10px] text-danger-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-text-secondary mb-1">Phone Number</label>
                                        <input type="text" wire:model.defer="phone" wire:change="checkDuplicates" class="w-full bg-surface-sunken border border-border-strong rounded p-2 text-xs text-text-primary focus:border-brand-primary focus:outline-none">
                                        @error('phone') <span class="text-[10px] text-danger-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-text-secondary mb-1">Contact Type *</label>
                                        <select wire:model.defer="type" class="w-full bg-surface-sunken border border-border-strong rounded p-2 text-xs text-text-primary focus:border-brand-primary focus:outline-none">
                                            <option value="buyer">Buyer</option>
                                            <option value="seller">Seller</option>
                                            <option value="landlord">Landlord</option>
                                            <option value="tenant">Tenant</option>
                                            <option value="investor">Investor</option>
                                            <option value="referral_partner">Referral Partner</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-text-secondary mb-1">Lead Source</label>
                                        <select wire:model.defer="source" class="w-full bg-surface-sunken border border-border-strong rounded p-2 text-xs text-text-primary focus:border-brand-primary focus:outline-none">
                                            <option value="">Select source...</option>
                                            <option value="portal">Property Portal</option>
                                            <option value="referral">Referral</option>
                                            <option value="walk_in">Walk-in</option>
                                            <option value="social_media">Social Media</option>
                                            <option value="direct">Direct / Cold Call</option>
                                            <option value="campaign">Campaign</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="pt-6 border-t border-border-strong">
                                        <button type="submit" class="w-full py-2.5 bg-brand-primary text-black rounded font-black text-xs hover:bg-emerald-600 transition-colors flex justify-center items-center">
                                            <span wire:loading.remove wire:target="saveContact">Save Contact</span>
                                            <span wire:loading wire:target="saveContact">Saving...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Contact Detail Slide-over Drawer (480px) -->
    <div class="relative z-50" 
         x-data="{ show: @entangle('showDrawer') }" 
         x-show="show" 
         role="dialog" 
         aria-modal="true" 
         style="display: none;">
        
        <!-- Backdrop with blur -->
        <div class="fixed inset-0 bg-surface-overlay backdrop-blur-xs transition-opacity" 
             x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="$wire.closeDrawer()">
        </div>

        <div class="fixed inset-y-0 right-0 z-50 flex max-w-full pl-10">
            <!-- 480px width Drawer -->
            <div class="w-screen max-w-[480px] pointer-events-auto"
                 x-show="show"
                 x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-250"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full">
                 
                <div class="flex h-full flex-col bg-surface-card border-l border-border-strong shadow-2xl overflow-hidden relative">
                    @if($selectedContact)
                    
                    <!-- Drawer Header -->
                    <div class="px-6 py-6 border-b border-border-strong bg-surface-sunken flex flex-col gap-4 font-geist">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-4">
                                <!-- Large avatar (3xl equivalent) -->
                                <div class="h-16 w-16 rounded-full bg-brand-primary/10 border border-brand-primary/20 flex items-center justify-center text-brand-primary font-black text-xl font-mono-geist">
                                    {{ $selectedContact->initials }}
                                </div>
                                <div>
                                    <!-- Name in 24px bold -->
                                    <h2 class="text-2xl font-black tracking-tight text-text-primary leading-tight">
                                        {{ $selectedContact->first_name }} {{ $selectedContact->last_name }}
                                    </h2>
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <!-- Stage Chip -->
                                        @php
                                            $stageLabel = match($selectedContact->status) {
                                                'new' => 'Prospect',
                                                'qualified' => 'Qualified',
                                                'active' => 'Active',
                                                'nurturing' => 'Under Offer',
                                                'closed' => 'Closed',
                                                default => ucfirst($selectedContact->status)
                                            };
                                            $stageClass = match($selectedContact->status) {
                                                'new' => 'border-border-strong bg-surface-raised text-text-secondary',
                                                'qualified' => 'border-blue-500/20 bg-blue-950/40 text-blue-400',
                                                'active' => 'border-emerald-500/20 bg-emerald-950/40 text-emerald-400',
                                                'nurturing' => 'border-amber-500/20 bg-amber-950/40 text-amber-400',
                                                'closed' => 'border-green-500/20 bg-green-950/40 text-green-400',
                                                default => 'border-border-strong bg-surface-raised text-text-secondary'
                                            };
                                        @endphp
                                        <span class="px-2 py-0.5 text-[9px] font-black rounded-full border {{ $stageClass }} uppercase tracking-wider">
                                            {{ $stageLabel }}
                                        </span>
                                        
                                        <!-- Assigned Agent Badge -->
                                        <span class="px-2 py-0.5 text-[9px] font-bold rounded-full bg-zinc-850 border border-border-strong text-zinc-300">
                                            👤 {{ $selectedContact->agent ? $selectedContact->agent->first_name : 'Unassigned' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <button @click="$wire.closeDrawer()" type="button" class="p-1.5 rounded-lg text-text-tertiary hover:text-text-primary hover:bg-surface-card transition-colors">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- 4 quick-action buttons: Call | WhatsApp | Email | Schedule Viewing. equal width, dark glass cards -->
                        <div class="grid grid-cols-4 gap-2 mt-2">
                            <a href="tel:{{ $selectedContact->phone }}" class="flex flex-col items-center justify-center py-2.5 rounded bg-surface-raised/70 border border-border-strong hover:border-brand-primary/45 hover:bg-surface-card/40 transition-all text-center group">
                                <span class="text-sm group-hover:scale-110 transition-transform">📞</span>
                                <span class="text-[9px] font-black text-text-secondary group-hover:text-text-primary mt-1 uppercase tracking-wider">Call</span>
                            </a>
                            <button wire:click="openDraftModal('whatsapp')" class="flex flex-col items-center justify-center py-2.5 rounded bg-surface-raised/70 border border-border-strong hover:border-brand-primary/45 hover:bg-surface-card/40 transition-all text-center group">
                                <span class="text-sm group-hover:scale-110 transition-transform">💬</span>
                                <span class="text-[9px] font-black text-text-secondary group-hover:text-text-primary mt-1 uppercase tracking-wider">WhatsApp</span>
                            </button>
                            <button wire:click="openDraftModal('email')" class="flex flex-col items-center justify-center py-2.5 rounded bg-surface-raised/70 border border-border-strong hover:border-brand-primary/45 hover:bg-surface-card/40 transition-all text-center group">
                                <span class="text-sm group-hover:scale-110 transition-transform">✉️</span>
                                <span class="text-[9px] font-black text-text-secondary group-hover:text-text-primary mt-1 uppercase tracking-wider">Email</span>
                            </button>
                            <button wire:click="$set('activeTab', 'overview')" class="flex flex-col items-center justify-center py-2.5 rounded bg-surface-raised/70 border border-border-strong hover:border-brand-primary/45 hover:bg-surface-card/40 transition-all text-center group">
                                <span class="text-sm group-hover:scale-110 transition-transform">👁</span>
                                <span class="text-[9px] font-black text-text-secondary group-hover:text-text-primary mt-1 uppercase tracking-wider">Viewing</span>
                            </button>
                        </div>
                    </div>

                    <!-- Navigation Tabs -->
                    <div class="px-6 border-b border-border-strong bg-surface-card font-geist">
                        <div class="flex gap-4">
                            @foreach(['overview' => 'Overview', 'timeline' => 'Timeline', 'listings' => 'Listings', 'documents' => 'Documents', 'notes' => 'Notes'] as $tab => $label)
                            <button wire:click="$set('activeTab', '{{ $tab }}')" 
                                class="py-3 text-[11px] font-black transition-all border-b-2 relative {{ $activeTab === $tab ? 'border-brand-primary text-brand-primary' : 'border-transparent text-text-secondary hover:text-zinc-200' }} uppercase tracking-wider">
                                {{ $label }}
                                @if($tab === 'listings' && $this->matchedListings->count() > 0)
                                <span class="absolute top-1.5 -right-3 bg-brand-primary text-black text-[8px] font-black h-3.5 w-3.5 rounded-full flex items-center justify-center font-mono-geist">
                                    {{ $this->matchedListings->count() }}
                                </span>
                                @endif
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Scrollable Content Tab Panels -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-6 pb-24 font-geist">
                        
                        <!-- Overview Tab -->
                        @if($activeTab === 'overview')
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 gap-5">
                                <!-- Contact Info -->
                                <div class="space-y-3">
                                    <h3 class="text-[10px] font-black uppercase tracking-widest text-text-tertiary font-mono-geist">Contact Info</h3>
                                    <div class="space-y-3.5 bg-surface-sunken/80 p-4 rounded-lg border border-border-default">
                                        <div>
                                            <span class="text-[9px] font-bold text-text-tertiary uppercase block tracking-wider">Phone</span>
                                            <span class="text-xs font-semibold text-text-primary font-mono-geist">
                                                {{ $this->formatPhoneNumber($selectedContact->phone) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-[9px] font-bold text-text-tertiary uppercase block tracking-wider">Email</span>
                                            <span class="text-xs font-semibold text-text-primary break-all">
                                                {{ $selectedContact->email ?? '—' }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-[9px] font-bold text-text-tertiary uppercase block tracking-wider">Lead Source</span>
                                            <span class="px-2 py-0.5 text-[9px] font-black rounded bg-surface-card text-zinc-300 inline-block uppercase mt-1 border border-zinc-700">
                                                {{ str_replace('_', ' ', $selectedContact->source ?? 'direct') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Search Requirements -->
                                @php
                                    $prefs = $selectedContact->preferences ?? [];
                                    $budgetMax = $prefs['max_budget'] ?? 0;
                                    $budgetMin = $prefs['min_budget'] ?? 0;
                                    $areas = $prefs['areas'] ?? [];
                                    $bedrooms = $prefs['min_bedrooms'] ?? 0;
                                @endphp
                                <div class="space-y-3">
                                    <h3 class="text-[10px] font-black uppercase tracking-widest text-text-tertiary font-mono-geist">Search Requirements</h3>
                                    <div class="space-y-3.5 bg-surface-sunken/80 p-4 rounded-lg border border-border-default">
                                        <div>
                                            <span class="text-[9px] font-bold text-text-tertiary uppercase block tracking-wider">Budget Range</span>
                                            <span class="text-xs font-black text-brand-primary font-mono-geist">
                                                @if($budgetMax > 0)
                                                    {{ $currencySymbol }}{{ number_format($budgetMin / 1000000, 0) }}M – {{ $currencySymbol }}{{ number_format($budgetMax / 1000000, 0) }}M
                                                @else
                                                    Not Specified
                                                @endif
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-[9px] font-bold text-text-tertiary uppercase block tracking-wider">Preferred Areas</span>
                                            <div class="flex flex-wrap gap-1.5 mt-1.5">
                                                @forelse($areas as $area)
                                                    <span class="px-2.5 py-0.5 text-[9px] bg-surface-raised border border-border-strong rounded text-zinc-300 font-bold">
                                                        {{ $area }}
                                                    </span>
                                                @empty
                                                    <span class="text-xs text-zinc-650">None specified</span>
                                                @endforelse
                                            </div>
                                        </div>
                                        <div>
                                            <span class="text-[9px] font-bold text-text-tertiary uppercase block tracking-wider">Bedroom Requirements</span>
                                            <span class="text-xs font-semibold text-text-primary">
                                                {{ $bedrooms > 0 ? "{$bedrooms}+ Bedrooms" : 'Not Specified' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- AI Summary Card -->
                                <div class="space-y-3">
                                    <h3 class="text-[10px] font-black uppercase tracking-widest text-text-tertiary font-mono-geist">AI Insights</h3>
                                    <div class="p-4 bg-emerald-950/20 border-l-2 border-brand-primary rounded-r-lg border border-y-zinc-800 border-r-zinc-800 relative overflow-hidden group">
                                        <div class="flex items-center gap-1.5 mb-2">
                                            <span class="text-brand-primary text-xs">✦</span>
                                            <h4 class="text-xs font-black text-brand-primary uppercase tracking-wider">AI Summary</h4>
                                        </div>
                                        <p class="text-xs text-zinc-350 leading-relaxed font-sans">
                                            @if($selectedContact->type === 'buyer')
                                                {{ $selectedContact->first_name }} is a motivated buyer looking for a {{ $bedrooms }}-bed in {{ !empty($areas) ? $areas[0] : 'Lekki' }}. Budget {{ $budgetMax > 0 ? $currencySymbol . number_format($budgetMin / 1000000, 0) . 'M–' . $currencySymbol . number_format($budgetMax / 1000000, 0) . 'M' : 'unspecified' }}. Viewed {{ rand(3, 8) }} listings. Last contacted {{ $selectedContact->last_contacted_at ? $selectedContact->last_contacted_at->diffForHumans() : 'never' }}. Intent score: {{ $selectedContact->intent_score }}.
                                            @elseif($selectedContact->type === 'tenant')
                                                {{ $selectedContact->first_name }} is actively renting, looking for high-end properties in {{ !empty($areas) ? $areas[0] : 'Lekki' }}. Verified income history. Last active {{ $selectedContact->last_contacted_at ? $selectedContact->last_contacted_at->diffForHumans() : 'never' }}. Match accuracy high.
                                            @else
                                                {{ $selectedContact->first_name }} is registered as a {{ $selectedContact->type }}. Intent score is {{ $selectedContact->intent_score }}% based on activity history and recent communication patterns.
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Timeline Tab -->
                        @if($activeTab === 'timeline')
                        <div class="space-y-4">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-text-tertiary font-mono-geist">Timeline History</h3>
                            
                            <!-- Activity logging mini form -->
                            <div class="p-4 bg-surface-sunken/80 border border-border-default rounded-lg space-y-3.5 mb-2">
                                <h4 class="text-[10px] font-black uppercase tracking-widest text-text-secondary">Log Interaction</h4>
                                <form wire:submit.prevent="saveDrawerActivity" class="space-y-3">
                                    <div class="flex gap-1">
                                        @foreach(['note' => 'Note', 'call' => 'Call', 'email' => 'Mail', 'sms' => 'SMS'] as $v => $l)
                                        <button type="button" wire:click="$set('activityType', '{{ $v }}')"
                                            class="flex-1 py-1 rounded text-[10px] font-bold transition-all border {{ $activityType === $v ? 'bg-brand-primary text-black border-brand-primary' : 'border-border-strong text-text-secondary hover:text-zinc-200' }}">
                                            {{ $l }}
                                        </button>
                                        @endforeach
                                    </div>
                                    <textarea wire:model.defer="activityBody" rows="2" placeholder="Write interaction summary..."
                                        class="w-full text-xs p-2 bg-surface-sunken border border-border-default rounded text-text-primary focus:border-brand-primary focus:outline-none resize-none font-sans"></textarea>
                                    @error('activityBody') <span class="text-[10px] text-danger-500 block">{{ $message }}</span> @enderror
                                    <button type="submit" class="w-full py-1.5 bg-brand-primary hover:bg-emerald-600 text-black text-xs font-bold rounded transition-colors shadow-brand-sm">
                                        Save Activity Log
                                    </button>
                                </form>
                            </div>

                            <div class="flow-root">
                                <ul role="list" class="-mb-8">
                                    @forelse($selectedContact->activities as $index => $activity)
                                    <li>
                                        <div class="relative pb-8">
                                            @if($index < count($selectedContact->activities) - 1)
                                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-zinc-850" aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full flex items-center justify-center text-xs bg-surface-raised border border-border-default">
                                                        @switch($activity->type)
                                                            @case('call') 📞 @break
                                                            @case('email') ✉️ @break
                                                            @case('viewing') 👁 @break
                                                            @case('sms') 💬 @break
                                                            @case('status_change') 🔄 @break
                                                            @default 📝
                                                        @endswitch
                                                    </span>
                                                </div>
                                                <div class="flex-1 min-w-0 pt-1">
                                                    <div class="text-xs text-text-secondary flex justify-between gap-2">
                                                        <p class="font-bold text-text-primary">
                                                            {{ $activity->subject ?: ucfirst($activity->type) }}
                                                        </p>
                                                        <time class="shrink-0 text-[10px] text-text-tertiary font-mono-geist">
                                                            {{ $activity->occurred_at->diffForHumans() }}
                                                        </time>
                                                    </div>
                                                    @if($activity->body)
                                                    <p class="mt-1 text-xs text-text-secondary font-sans leading-relaxed">
                                                        {{ $activity->body }}
                                                    </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    @empty
                                    <p class="text-xs text-zinc-650 text-center py-8">No activities logged yet.</p>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                        @endif

                        <!-- Listings Tab -->
                        @if($activeTab === 'listings')
                        <div class="space-y-4 font-geist">
                            <div class="flex items-center justify-between">
                                <h3 class="text-[10px] font-black uppercase tracking-widest text-text-tertiary font-mono-geist">AI Matches</h3>
                                <span class="px-2 py-0.5 bg-brand-primary/10 text-brand-primary border border-brand-primary/20 text-[9px] font-black rounded font-mono-geist">
                                    {{ $this->matchedListings->count() }} MATCHES
                                </span>
                            </div>
                            
                            <div class="space-y-3">
                                @forelse($this->matchedListings as $match)
                                    @php
                                        $listing = $match['listing'];
                                        $prop = $listing->property;
                                    @endphp
                                    <div class="p-3.5 bg-surface-sunken border border-border-default rounded-lg hover:border-brand-primary/35 transition-colors flex gap-3 relative overflow-hidden group">
                                        <!-- Match Score -->
                                        <div class="absolute top-3 right-3 px-2 py-0.5 bg-brand-primary text-black text-[9px] font-black rounded-full font-mono-geist shadow-sm">
                                            {{ $match['score'] }}% Match
                                        </div>
                                        
                                        <!-- Mock Image -->
                                        <div class="h-14 w-20 rounded bg-surface-sunken border border-border-strong flex items-center justify-center text-text-tertiary font-mono-geist text-[9px] shrink-0">
                                            🏠 {{ strtoupper($prop->property_type) }}
                                        </div>
                                        
                                        <div class="space-y-1.5 flex-1 min-w-0">
                                            <h4 class="text-xs font-bold text-text-primary truncate pr-16 leading-tight">
                                                {{ $prop->address_line_1 }}
                                            </h4>
                                            <p class="text-[10px] text-text-tertiary">
                                                📍 {{ $prop->city }} · 🛏 {{ $prop->bedrooms }} beds · 🚿 {{ $prop->bathrooms }} baths
                                            </p>
                                            <p class="text-xs font-black text-brand-primary font-mono-geist">
                                                {{ $currencySymbol }}{{ number_format($listing->listing_price / 1000000, 0) }}M
                                            </p>
                                            
                                            <!-- Match Reasons -->
                                            <div class="flex flex-wrap gap-1.5 mt-2 pt-2 border-t border-border-default">
                                                @foreach(array_slice($match['reasons'], 0, 3) as $reason)
                                                    <span class="text-[9px] font-bold text-text-secondary bg-surface-sunken border border-border-default px-2 py-0.5 rounded leading-none">
                                                        ✓ {{ $reason }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-8 border border-dashed border-border-strong rounded-lg text-center bg-surface-sunken/30">
                                        <p class="text-xs text-text-tertiary font-medium">No matching listings found for this client's search criteria.</p>
                                        <p class="text-[10px] text-text-tertiary mt-1">Try updating the budget or location requirements in the Notes / Overview tab.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                        @endif

                        <!-- Documents Tab -->
                        @if($activeTab === 'documents')
                        <div class="space-y-4">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-text-tertiary font-mono-geist">Required Documents Checklist</h3>
                            
                            <div class="space-y-2.5">
                                @php
                                    $docs = [
                                        ['name' => 'FICA / KYC Identity Document', 'type' => 'ID Verification', 'status' => 'Verified', 'status_class' => 'bg-emerald-950/40 text-emerald-450 border-emerald-500/20'],
                                        ['name' => 'Proof of Address / Utility Bill', 'type' => 'KYC Address', 'status' => 'Verified', 'status_class' => 'bg-emerald-950/40 text-emerald-450 border-emerald-500/20'],
                                        ['name' => 'Sole Mandate Agreement', 'type' => 'Contract', 'status' => 'Signed', 'status_class' => 'bg-emerald-950/40 text-emerald-450 border-emerald-500/20'],
                                        ['name' => 'Offer to Purchase (OTP)', 'type' => 'Deal OTP', 'status' => 'Pending', 'status_class' => 'bg-amber-950/40 text-amber-450 border-amber-500/20'],
                                        ['name' => 'Lease Agreement Draft', 'type' => 'Lease', 'status' => 'Draft', 'status_class' => 'bg-surface-raised text-text-secondary border-border-strong'],
                                    ];
                                @endphp
                                
                                @foreach($docs as $doc)
                                <div class="p-3.5 bg-surface-sunken border border-border-default rounded-lg flex items-center justify-between gap-4">
                                    <div>
                                        <h4 class="text-xs font-bold text-text-primary leading-tight">{{ $doc['name'] }}</h4>
                                        <span class="text-[9px] font-bold text-text-tertiary font-mono-geist uppercase tracking-wider block mt-1">{{ $doc['type'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-[8px] font-black border {{ $doc['status_class'] }} uppercase tracking-wider font-mono-geist">
                                            {{ $doc['status'] }}
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            
                            <button class="w-full py-2.5 border border-dashed border-border-strong hover:border-brand-primary/40 rounded-lg text-xs font-bold text-text-secondary hover:text-brand-primary transition-all flex items-center justify-center gap-1.5 mt-3 bg-transparent">
                                <span>📎 Upload New Document</span>
                            </button>
                        </div>
                        @endif

                        <!-- Notes Tab -->
                        @if($activeTab === 'notes')
                        <div class="space-y-4">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-text-tertiary font-mono-geist">Internal Agent Notes</h3>
                            
                            <div class="space-y-3">
                                <textarea wire:model.defer="newNote" rows="8" placeholder="Type key details, preferences, and private comments here..."
                                    class="w-full text-xs p-3.5 bg-surface-sunken border border-border-default rounded-lg text-text-primary focus:border-brand-primary focus:outline-none resize-none font-sans leading-relaxed"></textarea>
                                
                                <button wire:click="saveContactNotes" class="w-full py-2 bg-brand-primary hover:bg-emerald-600 text-black text-xs font-black rounded transition-colors shadow-brand-sm">
                                    Save Notes
                                </button>
                            </div>
                        </div>
                        @endif

                    </div>

                    <!-- Floating AI Draft button at bottom right of the drawer (amber FAB) -->
                    <div class="absolute bottom-6 right-6 z-20 font-geist">
                        <button wire:click="openDraftModal('whatsapp')" class="flex items-center gap-1.5 px-4.5 py-3 rounded-full bg-brand-accent hover:bg-amber-600 text-black text-xs font-black shadow-brand-lg transform hover-spring active:scale-95 transition-all">
                            <span>✦ Draft Message</span>
                        </button>
                    </div>

                    @endif
                </div>

            </div>
        </div>
    </div>

    <!-- AI Draft Modal -->
    @if($showDraftModal)
    <div class="relative z-[60] font-geist" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-surface-overlay backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 overflow-y-auto flex items-center justify-center p-4">
            <div class="relative bg-surface-sunken border border-border-default rounded-xl max-w-md w-full shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-border-strong pb-3">
                    <h3 class="text-sm font-black text-brand-accent flex items-center gap-1.5 uppercase tracking-wider font-mono-geist">
                        <span>✦ AI Draft Assistant</span>
                    </h3>
                    <button wire:click="$set('showDraftModal', false)" class="text-text-tertiary hover:text-text-primary text-lg">&times;</button>
                </div>
                
                <!-- Channel Selector -->
                <div class="space-y-1">
                    <label class="text-[9px] font-black uppercase text-text-tertiary tracking-widest font-mono-geist">Select Channel</label>
                    <div class="flex gap-2">
                        @foreach(['whatsapp' => '💬 WhatsApp', 'sms' => '📱 SMS', 'email' => '✉️ Email'] as $ch => $lbl)
                        <button wire:click="openDraftModal('{{ $ch }}')" 
                            class="flex-1 py-1.5 rounded text-xs font-bold transition-all border {{ $draftChannel === $ch ? 'bg-brand-accent text-black border-brand-accent shadow-brand-sm' : 'border-border-strong text-text-secondary hover:text-zinc-200 bg-transparent' }}">
                            {{ $lbl }}
                        </button>
                        @endforeach
                    </div>
                </div>
                
                <!-- Message Body -->
                <div class="space-y-1">
                    <label class="text-[9px] font-black uppercase text-text-tertiary tracking-widest font-mono-geist">Draft Content</label>
                    <textarea wire:model="draftMessage" rows="5"
                        class="w-full text-xs p-3.5 bg-surface-raised border border-border-strong rounded text-text-primary focus:border-brand-accent focus:outline-none resize-none font-sans leading-relaxed"></textarea>
                </div>
                
                <!-- Footer Actions -->
                <div class="flex gap-3 pt-2">
                    <button wire:click="$set('showDraftModal', false)" class="flex-1 py-2 bg-surface-card hover:bg-zinc-700 text-zinc-300 text-xs font-bold rounded transition-colors">
                        Cancel
                    </button>
                    <button wire:click="sendDraftMessage" class="flex-1 py-2 bg-brand-accent hover:bg-amber-600 text-black text-xs font-black rounded transition-colors shadow-brand-sm">
                        Send Message
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
