<div>
    <div class="mb-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <span class="text-[10px] font-black uppercase tracking-widest text-brand-primary bg-brand-primary/10 px-2.5 py-1 rounded-full">CRM Directory</span>
            <h1 class="text-4xl font-black tracking-tight text-text-primary mt-2">
                Contacts <span class="bg-clip-text text-transparent bg-gradient-to-r from-brand-primary via-emerald-400 to-brand-accent">CRM</span>.
            </h1>
            <p class="text-xs font-semibold text-text-secondary mt-1">Track agency customer relationships, buyer/seller leads, and communication history.</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="$set('showCreateModal', true)" class="px-5 py-2.5 rounded-xl bg-brand-primary text-white text-xs font-bold hover:bg-brand-secondary transition-all shadow-brand-sm hover:shadow-brand-md hover-spring flex items-center space-x-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                <span>New Contact</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div class="p-6 rounded-[2rem] glass-panel border border-border-default/80 shadow-sm hover:shadow-brand-sm group hover:scale-[1.01] transition-all duration-300 relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-24 h-24 rounded-full bg-brand-primary/10 blur-xl group-hover:scale-115 transition-transform duration-500"></div>
            <div class="relative z-10">
                <h3 class="text-xs font-bold tracking-widest uppercase text-text-secondary">Total Active</h3>
                <p class="mt-2 text-3xl font-black text-text-primary tracking-tighter">{{ $totalActive }}</p>
            </div>
        </div>
        <div class="p-6 rounded-[2rem] glass-panel border border-border-default/80 shadow-sm hover:shadow-brand-sm group hover:scale-[1.01] transition-all duration-300 relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-24 h-24 rounded-full bg-success-500/10 blur-xl group-hover:scale-115 transition-transform duration-500"></div>
            <div class="relative z-10">
                <h3 class="text-xs font-bold tracking-widest uppercase text-text-secondary">New (This Week)</h3>
                <p class="mt-2 text-3xl font-black text-success-500 tracking-tighter">{{ $newThisWeek }}</p>
            </div>
        </div>
        <div class="p-6 rounded-[2rem] glass-panel border border-border-default/80 shadow-sm hover:shadow-brand-sm group hover:scale-[1.01] transition-all duration-300 relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-24 h-24 rounded-full bg-warning-500/10 blur-xl group-hover:scale-115 transition-transform duration-500"></div>
            <div class="relative z-10">
                <h3 class="text-xs font-bold tracking-widest uppercase text-text-secondary">Hot Buyers</h3>
                <p class="mt-2 text-3xl font-black text-warning-500 tracking-tighter">{{ $hotBuyers }}</p>
            </div>
        </div>
        <div class="p-6 rounded-[2rem] glass-panel border border-border-default/80 shadow-sm hover:shadow-brand-sm group hover:scale-[1.01] transition-all duration-300 relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-24 h-24 rounded-full bg-info-500/10 blur-xl group-hover:scale-115 transition-transform duration-500"></div>
            <div class="relative z-10">
                <h3 class="text-xs font-bold tracking-widest uppercase text-text-secondary">Sellers (Pending)</h3>
                <p class="mt-2 text-3xl font-black text-info-500 tracking-tighter">{{ $pendingSellers }}</p>
            </div>
        </div>
    </div>

    <!-- Contacts Table -->
    <div class="glass-panel rounded-2xl overflow-hidden border border-border-default/60 shadow-sm">
        <div class="px-6 py-4 border-b border-border-default/60 flex items-center justify-between bg-surface-sunken/30 gap-4">
            <div class="w-1/3">
                <input wire:model.debounce.300ms="search" type="text" placeholder="Search by name, email, or phone..."
                    class="w-full px-3 py-2 border border-border-strong rounded-lg bg-white/50 focus:ring-2 focus:ring-brand-primary focus:border-brand-primary text-sm">
            </div>
            <div class="flex space-x-2">
                <select wire:model="filterType" class="px-3 py-2 border border-border-strong rounded-lg bg-white text-text-secondary text-sm">
                    <option value="">All Types</option>
                    <option value="buyer">Buyers</option>
                    <option value="seller">Sellers</option>
                    <option value="landlord">Landlords</option>
                    <option value="tenant">Tenants</option>
                    <option value="investor">Investors</option>
                </select>
                <select wire:model="filterStatus" class="px-3 py-2 border border-border-strong rounded-lg bg-white text-text-secondary text-sm">
                    <option value="">All Statuses</option>
                    <option value="new">New</option>
                    <option value="active">Active</option>
                    <option value="qualified">Qualified</option>
                    <option value="nurturing">Nurturing</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border-default/60">
                <thead class="bg-surface-sunken/20">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Name & Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Assigned To</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">Intent</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-text-tertiary uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default/60 bg-white/10">
                    @forelse($contacts as $contact)
                    <tr class="hover:bg-surface-sunken/20 transition-colors cursor-pointer"
                        wire:key="contact-row-{{ $contact->id }}"
                        @click="if (!$event.target.closest('select') && !$event.target.closest('input') && !$event.target.closest('a') && !$event.target.closest('button')) $wire.selectContact({{ $contact->id }})">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-brand-primary/10 flex items-center justify-center text-brand-primary font-bold text-sm">
                                    {{ strtoupper(substr($contact->first_name,0,1).substr($contact->last_name,0,1)) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-text-primary dark:text-white">{{ $contact->first_name }} {{ $contact->last_name }}</div>
                                    <div class="text-sm text-text-tertiary">{{ $contact->email ?? $contact->phone ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-surface-sunken text-text-primary uppercase tracking-wider">
                                {{ str_replace('_', ' ', $contact->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"
                            x-data="{ 
                                editing: false, 
                                status: '{{ $contact->status }}',
                                save() {
                                    this.editing = false;
                                    $wire.updateStatus({{ $contact->id }}, this.status);
                                }
                            }"
                            @click.away="if(editing) save()">
                            <div x-show="!editing" @dblclick="editing = true; $nextTick(() => $refs.statusSelect.focus())" class="flex items-center justify-between group cursor-pointer gap-2">
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-lg
                                    @if($contact->status === 'qualified') bg-success-500/10 text-success-600
                                    @elseif($contact->status === 'active') bg-info-500/10 text-info-600
                                    @elseif($contact->status === 'nurturing') bg-warning-500/10 text-warning-600
                                    @elseif($contact->status === 'hot') bg-danger-500/10 text-danger-600
                                    @else bg-surface-sunken/80 text-text-primary @endif uppercase tracking-wider">
                                    {{ $contact->status }}
                                </span>
                                <svg class="w-3.5 h-3.5 text-text-tertiary opacity-0 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </div>
                            <div x-show="editing" style="display: none;">
                                <select x-model="status" 
                                        x-ref="statusSelect"
                                        @change="save()"
                                        @keydown.escape="editing = false"
                                        class="w-full text-xs bg-surface-input border border-brand-primary rounded-lg p-1.5 focus:ring-1 focus:ring-brand-primary focus:outline-none text-text-primary">
                                    <option value="new">New</option>
                                    <option value="active">Active</option>
                                    <option value="qualified">Qualified</option>
                                    <option value="nurturing">Nurturing</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-text-tertiary">
                            {{ $contact->agent ? $contact->agent->first_name : 'Unassigned' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"
                            x-data="{ 
                                editing: false, 
                                score: {{ $contact->intent_score ?? 0 }},
                                save() {
                                    this.editing = false;
                                    let val = parseInt(this.score);
                                    if(isNaN(val)) val = 0;
                                    val = Math.min(100, Math.max(0, val));
                                    this.score = val;
                                    $wire.updateIntentScore({{ $contact->id }}, val);
                                }
                            }"
                            @click.away="if(editing) save()">
                            <div x-show="!editing" @dblclick="editing = true; $nextTick(() => $refs.scoreInput.focus())" class="flex items-center justify-between group cursor-pointer gap-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 bg-surface-sunken rounded-full h-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full transition-all duration-500
                                            @if($contact->intent_score >= 80) bg-success-500
                                            @elseif($contact->intent_score >= 50) bg-warning-500
                                            @else bg-text-tertiary @endif"
                                            style="width: {{ $contact->intent_score }}%"></div>
                                    </div>
                                    <span class="text-xs text-text-secondary font-medium">{{ $contact->intent_score }}%</span>
                                </div>
                                <svg class="w-3.5 h-3.5 text-text-tertiary opacity-0 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </div>
                            <div x-show="editing" style="display: none;">
                                <input type="number" min="0" max="100"
                                       x-model="score"
                                       x-ref="scoreInput"
                                       @keydown.enter="save()"
                                       @keydown.escape="editing = false"
                                       class="w-20 text-xs bg-surface-input border border-brand-primary rounded-lg p-1.5 focus:ring-1 focus:ring-brand-primary focus:outline-none text-text-primary">
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('crm.contact.detail', $contact) }}" class="text-brand-primary hover:text-brand-secondary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="h-12 w-12 rounded-full bg-brand-primary/10 flex items-center justify-center mb-3 text-xl">👥</div>
                                <h3 class="text-sm font-medium text-text-primary dark:text-white">No contacts found</h3>
                                <p class="mt-1 text-sm text-text-tertiary">
                                    @if($search || $filterType || $filterStatus)
                                        Try adjusting your filters.
                                    @else
                                        Get started by creating a new contact.
                                    @endif
                                </p>
                                @if(!$search && !$filterType && !$filterStatus)
                                <button wire:click="$set('showCreateModal', true)" class="mt-4 px-4 py-2 bg-brand-primary text-white rounded-lg hover:bg-brand-secondary font-medium text-sm transition-colors">
                                    + Add First Contact
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-3 border-t border-border-default/60">
            {{ $contacts->links() }}
        </div>
    </div>

    <!-- Create Contact Slide-over -->
    @if($showCreateModal)
    <div class="relative z-50" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-surface-overlay backdrop-blur-sm"></div>
        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div class="pointer-events-auto w-screen max-w-md">
                        <div class="flex h-full flex-col overflow-y-scroll bg-surface-page shadow-xl border-l border-border-default/60">
                            <div class="bg-surface-card px-4 py-6 sm:px-6 border-b border-border-default/60 flex items-center justify-between">
                                <h2 class="text-xl font-bold text-text-primary">Create New Contact</h2>
                                <button wire:click="$set('showCreateModal', false)" type="button" class="rounded-md text-text-secondary hover:text-text-primary">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="relative flex-1 px-4 py-6 sm:px-6">

                                <!-- Duplicate Warning -->
                                @if(count($duplicates) > 0 && !$confirmDuplicate)
                                <div class="mb-5 p-4 bg-warning-50 border border-warning-300 rounded-xl">
                                    <p class="text-sm font-semibold text-warning-800 mb-2">⚠️ Possible duplicate contacts found:</p>
                                    @foreach($duplicates as $dup)
                                    <div class="text-xs text-warning-700 mb-1 flex items-center justify-between">
                                        <span>{{ $dup['name'] }} — {{ $dup['email'] ?? $dup['phone'] }}</span>
                                        <a href="{{ route('crm.contact.detail', $dup['id']) }}" class="underline text-warning-800">View</a>
                                    </div>
                                    @endforeach
                                    <div class="mt-3 flex gap-2">
                                        <button wire:click="dismissDuplicates" class="px-3 py-1.5 bg-warning-600 text-white rounded-lg text-xs font-medium hover:bg-warning-700">
                                            Create Anyway
                                        </button>
                                        <button wire:click="$set('duplicates', [])" class="px-3 py-1.5 border border-warning-400 text-warning-800 rounded-lg text-xs font-medium hover:bg-warning-100">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                                @endif

                                <form wire:submit.prevent="saveContact" class="space-y-5">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-text-primary mb-1">First Name *</label>
                                            <input wire:model.defer="first_name" type="text" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                            @error('first_name') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-text-primary mb-1">Last Name *</label>
                                            <input wire:model.defer="last_name" type="text" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                            @error('last_name') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-text-primary mb-1">Email Address</label>
                                        <input wire:model.lazy="email" type="email" wire:change="checkDuplicates" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                        @error('email') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-text-primary mb-1">Phone Number</label>
                                        <input wire:model.lazy="phone" type="text" wire:change="checkDuplicates" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                        @error('phone') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-text-primary mb-1">Contact Type *</label>
                                        <select wire:model.defer="type" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                            <option value="buyer">Buyer</option>
                                            <option value="seller">Seller</option>
                                            <option value="landlord">Landlord</option>
                                            <option value="tenant">Tenant</option>
                                            <option value="investor">Investor</option>
                                            <option value="referral_partner">Referral Partner</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-text-primary mb-1">Lead Source</label>
                                        <select wire:model.defer="source" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
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
                                    <div class="pt-6 border-t border-border-default/60">
                                        <button type="submit" class="w-full px-4 py-2 bg-brand-primary text-white rounded-lg hover:bg-brand-secondary font-medium transition-colors flex justify-center items-center">
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

    <!-- Contact Detail Slide-over Drawer -->
    <div class="relative z-50" 
         x-data="{ show: @entangle('showDrawer') }" 
         x-show="show" 
         role="dialog" 
         aria-modal="true" 
         style="display: none;">
        
        <!-- Backdrop with blur -->
        <div class="fixed inset-0 bg-surface-overlay/40 backdrop-blur-xs transition-opacity" 
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
            <div class="w-screen max-w-xl"
                 x-show="show"
                 x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-200 sm:duration-300"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full">
                 
                <div class="flex h-full flex-col bg-surface-card border-l border-border-default/60 shadow-2xl overflow-hidden">
                    <!-- Drawer Header -->
                    <div class="px-6 py-5 border-b border-border-default/60 bg-surface-sunken/20 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-brand-primary/10 flex items-center justify-center text-brand-primary font-bold text-sm">
                                {{ $selectedContact ? strtoupper(substr($selectedContact->first_name, 0, 1) . substr($selectedContact->last_name, 0, 1)) : '' }}
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-text-primary dark:text-white">
                                    {{ $selectedContact ? $selectedContact->first_name . ' ' . $selectedContact->last_name : 'Contact Details' }}
                                </h2>
                                <span class="inline-block mt-0.5 px-2 py-0.5 rounded bg-surface-raised border border-border-default text-[10px] font-bold text-text-secondary uppercase tracking-wider">
                                    {{ $selectedContact ? $selectedContact->type : '' }}
                                </span>
                            </div>
                        </div>
                        <button @click="$wire.closeDrawer()" type="button" class="p-1.5 rounded-lg text-text-secondary hover:text-text-primary hover:bg-surface-raised transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    @if($selectedContact)
                    <!-- Drawer Scrollable Content -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">
                        <!-- Quick Details -->
                        <div class="grid grid-cols-2 gap-4 p-4 rounded-2xl bg-surface-sunken/30 border border-border-default/40">
                            <div>
                                <span class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider block">Email</span>
                                <a href="mailto:{{ $selectedContact->email }}" class="text-sm font-semibold text-text-primary hover:text-brand-primary break-all">
                                    {{ $selectedContact->email ?? 'No email logged' }}
                                </a>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider block">Phone</span>
                                <span class="text-sm font-semibold text-text-primary">
                                    {{ $selectedContact->phone ?? 'No phone logged' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider block">Lead Status</span>
                                <span class="inline-flex mt-1 px-2.5 py-0.5 rounded-full text-2xs font-bold bg-surface-raised border border-border-default text-text-secondary uppercase tracking-wider">
                                    {{ $selectedContact->status }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-text-tertiary uppercase tracking-wider block">AI Intent Score</span>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs font-bold text-text-primary">{{ $selectedContact->intent_score }}%</span>
                                    <div class="w-12 bg-surface-raised rounded-full h-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full
                                            @if($selectedContact->intent_score >= 80) bg-success-500
                                            @elseif($selectedContact->intent_score >= 50) bg-warning-500
                                            @else bg-text-tertiary @endif"
                                            style="width: {{ $selectedContact->intent_score }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Action Log Form -->
                        <div class="p-5 rounded-2xl bg-surface-sunken/20 border border-border-default/40 space-y-4">
                            <h3 class="text-xs font-black uppercase tracking-widest text-text-primary">Log New Activity</h3>
                            
                            <div class="flex gap-1.5">
                                @foreach(['note' => '📝 Note', 'call' => '📞 Call', 'email' => '✉️ Email', 'meeting' => '📅 Meet', 'sms' => '💬 SMS'] as $val => $label)
                                <button type="button" 
                                        wire:click="$set('activityType', '{{ $val }}')"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200
                                        {{ $activityType === $val ? 'bg-brand-primary text-white shadow-brand-sm' : 'bg-surface-raised text-text-secondary border border-border-default hover:bg-surface-sunken' }}">
                                    {{ $label }}
                                </button>
                                @endforeach
                            </div>

                            <form wire:submit.prevent="saveDrawerActivity" class="space-y-3">
                                <input wire:model.defer="activitySubject" type="text" placeholder="Subject (optional)"
                                    class="w-full text-xs rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                <textarea wire:model.defer="activityBody" rows="3"
                                    placeholder="Summary of this interaction..."
                                    class="w-full text-xs rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"></textarea>
                                @error('activityBody') <span class="text-xs text-danger-600 block">{{ $message }}</span> @enderror
                                <div class="flex justify-between items-center pt-2">
                                    <a href="{{ route('crm.contact.detail', $selectedContact) }}" 
                                       class="text-xs font-bold text-brand-primary hover:text-brand-secondary flex items-center gap-1">
                                       Full Profile page &rarr;
                                    </a>
                                    <button type="submit" class="px-4 py-2 bg-brand-primary text-white rounded-lg text-xs font-bold hover:bg-brand-secondary transition-colors shadow-brand-sm hover:shadow-brand-md">
                                        Log {{ ucfirst($activityType) }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Activity Feed Timeline -->
                        <div class="space-y-4">
                            <h3 class="text-xs font-black uppercase tracking-widest text-text-primary">Timeline History</h3>
                            <div class="flow-root">
                                <ul role="list" class="-mb-8">
                                    @forelse($selectedContact->activities as $index => $activity)
                                    <li>
                                        <div class="relative pb-8">
                                            @if($index < count($selectedContact->activities) - 1)
                                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-border-default" aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full flex items-center justify-center text-xs ring-4 ring-surface-card bg-surface-sunken">
                                                        @switch($activity->type)
                                                            @case('call') 📞 @break
                                                            @case('email') ✉️ @break
                                                            @case('meeting') 📅 @break
                                                            @case('sms') 💬 @break
                                                            @case('status_change') 🔄 @break
                                                            @default 📝
                                                        @endswitch
                                                    </span>
                                                </div>
                                                <div class="flex-1 min-w-0 pt-1.5">
                                                    <div class="text-xs text-text-secondary flex justify-between gap-2">
                                                        <p class="font-bold text-text-primary">
                                                            {{ $activity->subject ?: ucfirst($activity->type) }}
                                                        </p>
                                                        <time class="shrink-0 text-text-tertiary">
                                                            {{ $activity->occurred_at->diffForHumans() }}
                                                        </time>
                                                    </div>
                                                    @if($activity->body)
                                                    <p class="mt-1 text-xs text-text-secondary">
                                                        {{ $activity->body }}
                                                    </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    @empty
                                    <p class="text-xs text-text-tertiary text-center py-4">No activities logged yet.</p>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>

