<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">Contacts CRM</h1>
            <p class="mt-2 text-slate-600 dark:text-slate-400">Track agency customer relationships, buyer/seller leads, and communication history.</p>
        </div>
        <div class="flex space-x-3">
            <button wire:click="$set('showCreateModal', true)" class="px-4 py-2 bg-brand-primary text-white rounded-lg hover:bg-brand-secondary font-medium text-sm transition-colors hover-spring">
                + New Contact
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass-panel p-6 rounded-2xl border border-border-default/60">
            <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Active Contacts</h3>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ $totalActive }}</p>
        </div>
        <div class="glass-panel p-6 rounded-2xl border border-border-default/60">
            <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400">New Leads (This Week)</h3>
            <p class="mt-2 text-3xl font-bold text-success-600 dark:text-success-400">{{ $newThisWeek }}</p>
        </div>
        <div class="glass-panel p-6 rounded-2xl border border-border-default/60">
            <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400">Hot Buyers</h3>
            <p class="mt-2 text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $hotBuyers }}</p>
        </div>
        <div class="glass-panel p-6 rounded-2xl border border-border-default/60">
            <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400">Sellers (Pending)</h3>
            <p class="mt-2 text-3xl font-bold text-info-600 dark:text-info-400">{{ $pendingSellers }}</p>
        </div>
    </div>

    <!-- Contacts Table -->
    <div class="glass-panel rounded-2xl overflow-hidden border border-border-default/60 shadow-sm">
        <div class="px-6 py-4 border-b border-border-default/60 flex items-center justify-between bg-surface-sunken/30 gap-4">
            <div class="w-1/3">
                <input wire:model.debounce.300ms="search" type="text" placeholder="Search by name, email, or phone..."
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white/50 focus:ring-2 focus:ring-brand-primary focus:border-brand-primary text-sm">
            </div>
            <div class="flex space-x-2">
                <select wire:model="filterType" class="px-3 py-2 border border-slate-300 rounded-lg bg-white text-slate-700 text-sm">
                    <option value="">All Types</option>
                    <option value="buyer">Buyers</option>
                    <option value="seller">Sellers</option>
                    <option value="landlord">Landlords</option>
                    <option value="tenant">Tenants</option>
                    <option value="investor">Investors</option>
                </select>
                <select wire:model="filterStatus" class="px-3 py-2 border border-slate-300 rounded-lg bg-white text-slate-700 text-sm">
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
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Name & Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned To</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Intent</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default/60 bg-white/10">
                    @forelse($contacts as $contact)
                    <tr class="hover:bg-surface-sunken/20 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-brand-primary/10 flex items-center justify-center text-brand-primary font-bold text-sm">
                                    {{ strtoupper(substr($contact->first_name,0,1).substr($contact->last_name,0,1)) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $contact->first_name }} {{ $contact->last_name }}</div>
                                    <div class="text-sm text-slate-500">{{ $contact->email ?? $contact->phone ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-800 uppercase tracking-wider">
                                {{ str_replace('_', ' ', $contact->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($contact->status === 'qualified') bg-success-100 text-success-800
                                @elseif($contact->status === 'active') bg-info-100 text-info-800
                                @elseif($contact->status === 'nurturing') bg-warning-100 text-warning-800
                                @else bg-slate-100 text-slate-800 @endif uppercase tracking-wider">
                                {{ $contact->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            {{ $contact->agent ? $contact->agent->first_name : 'Unassigned' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-slate-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full
                                        @if($contact->intent_score >= 80) bg-success-500
                                        @elseif($contact->intent_score >= 50) bg-warning-500
                                        @else bg-slate-400 @endif"
                                        style="width: {{ $contact->intent_score }}%"></div>
                                </div>
                                <span class="text-xs text-slate-500">{{ $contact->intent_score }}%</span>
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
                                <h3 class="text-sm font-medium text-slate-900 dark:text-white">No contacts found</h3>
                                <p class="mt-1 text-sm text-slate-500">
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
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
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
</div>
