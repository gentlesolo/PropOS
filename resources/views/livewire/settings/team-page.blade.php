<div class="space-y-8 max-w-5xl">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Team Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage team members, their roles, and pending invitations.</p>
        </div>
        @can('agency.manage')
        <button wire:click="$set('showInviteForm', true)"
                class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">+ Invite Member</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
        @endcan
    </div>

    {{-- Invite Modal --}}
    @if($showInviteForm)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Invite a Team Member</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                <input type="email" wire:model="invite_email" placeholder="colleague@example.com"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                @error('invite_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                <select wire:model="invite_role"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    @foreach($availableRoles as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Role description --}}
            <div class="text-xs text-gray-500 bg-gray-50 rounded-lg p-3 leading-relaxed">
                @if($invite_role === 'principal') Full access to all modules, settings, financial data, and team management.
                @elseif($invite_role === 'branch_manager') Manage team listings, contacts, deals, and view branch analytics.
                @elseif($invite_role === 'marketing_manager') Create and manage all marketing campaigns. Read access to listings.
                @elseif($invite_role === 'senior_agent') Same as Agent with team-wide visibility of contacts and deals.
                @elseif($invite_role === 'admin') Broad access for admin/PA tasks — contacts, listings, transactions. No financials.
                @else Agent access — own contacts, deals, listings, and training content only.
                @endif
            </div>

            <div class="flex gap-3 justify-end pt-2">
                <button wire:click="$set('showInviteForm', false)"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 text-sm text-gray-600 hover:text-gray-800" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button wire:click="sendInvitation" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="sendInvitation">Send Invitation</span>
                    <span wire:loading wire:target="sendInvitation">Sending…</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Confirm Deactivation --}}
    @if($confirmDeactivateId)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 text-center space-y-4">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.293 4.293a1 1 0 011.414 0L21 13.586V19a2 2 0 01-2 2H5a2 2 0 01-2-2v-5.414L10.293 4.293z"/>
                </svg>
            </div>
            <h2 class="text-base font-semibold text-gray-900">Deactivate team member?</h2>
            <p class="text-sm text-gray-500">They will no longer be able to log in. You can reactivate them at any time.</p>
            <div class="flex gap-3 justify-center">
                <button wire:click="$set('confirmDeactivateId', null)"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 text-sm text-gray-600 hover:text-gray-800" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button wire:click="deactivateMember({{ $confirmDeactivateId }})"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700" wire:loading.attr="disabled" wire:target="deactivateMember">
                <span wire:loading.remove wire:target="deactivateMember">Deactivate</span>
                <span wire:loading wire:target="deactivateMember" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Team Members Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                Team Members ({{ $members->count() }})
            </h2>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($members as $member)
            <div class="px-6 py-4 flex items-center gap-4">

                {{-- Avatar --}}
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm shrink-0
                    {{ $member->id === auth()->id() ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                    @if($member->avatar_path)
                        <img src="{{ asset('storage/'.$member->avatar_path) }}"
                             class="w-10 h-10 rounded-full object-cover" alt="{{ $member->name }}" />
                    @else
                        {{ strtoupper(substr($member->first_name,0,1).substr($member->last_name,0,1)) }}
                    @endif
                </div>

                {{-- Name & email --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            {{ $member->name }}
                            @if($member->id === auth()->id())
                                <span class="text-xs text-gray-400">(you)</span>
                            @endif
                        </p>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $member->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-50 text-red-600' }}">
                            {{ ucfirst($member->status) }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 truncate">{{ $member->email }}</p>
                    @if($member->job_title)
                    <p class="text-xs text-gray-400">{{ $member->job_title }}</p>
                    @endif
                </div>

                {{-- Role selector --}}
                @can('agency.manage')
                <div class="shrink-0 w-48">
                    <select wire:model="memberRoles.{{ $member->id }}"
                            wire:change="changeRole({{ $member->id }})"
                            class="w-full text-sm border border-gray-300 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                   {{ $member->status === 'suspended' ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ $member->status === 'suspended' ? 'disabled' : '' }}>
                        @foreach($availableRoles as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div class="shrink-0 w-48">
                    <span class="text-sm text-gray-600">
                        {{ $availableRoles[$memberRoles[$member->id] ?? 'agent'] ?? 'Agent' }}
                    </span>
                </div>
                @endcan

                {{-- Last active --}}
                <div class="shrink-0 text-xs text-gray-400 w-28 text-right hidden md:block">
                    @if($member->last_active_at)
                        Active {{ $member->last_active_at->diffForHumans() }}
                    @else
                        Never logged in
                    @endif
                </div>

                {{-- Actions --}}
                @can('agency.manage')
                @if($member->id !== auth()->id())
                <div class="shrink-0">
                    @if($member->status === 'active')
                    <button wire:click="$set('confirmDeactivateId', {{ $member->id }})"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-red-500 hover:text-red-700 hover:underline" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Deactivate</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    @else
                    <button wire:click="reactivateMember({{ $member->id }})"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-green-600 hover:text-green-800 hover:underline" wire:loading.attr="disabled" wire:target="reactivateMember">
                <span wire:loading.remove wire:target="reactivateMember">Reactivate</span>
                <span wire:loading wire:target="reactivateMember" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    @endif
                </div>
                @endif
                @endcan
            </div>
            @empty
            <div class="px-6 py-10 text-center text-gray-400 text-sm">
                No team members yet.
            </div>
            @endforelse
        </div>
    </div>

    {{-- Pending Invitations --}}
    @if($pendingInvitations->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                Pending Invitations ({{ $pendingInvitations->count() }})
            </h2>
        </div>

        <div class="divide-y divide-gray-100">
            @foreach($pendingInvitations as $invitation)
            <div class="px-6 py-4 flex items-center gap-4">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">{{ $invitation->email }}</p>
                    <p class="text-xs text-gray-400">
                        Invited as <span class="font-medium">{{ $availableRoles[$invitation->role] ?? ucfirst($invitation->role) }}</span>
                        @if($invitation->inviter) by {{ $invitation->inviter->name }}@endif
                        &middot; {{ $invitation->created_at->diffForHumans() }}
                    </p>
                </div>
                <span class="px-2 py-0.5 bg-yellow-50 text-yellow-700 text-xs rounded-full font-medium">Pending</span>
                @can('agency.manage')
                <div class="flex items-center gap-3 shrink-0">
                    <button wire:click="resendInvitation({{ $invitation->id }})"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-blue-600 hover:underline" wire:loading.attr="disabled" wire:target="resendInvitation">
                <span wire:loading.remove wire:target="resendInvitation">Resend</span>
                <span wire:loading wire:target="resendInvitation" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    <button wire:click="revokeInvitation({{ $invitation->id }})"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-red-500 hover:underline" wire:loading.attr="disabled" wire:target="revokeInvitation">
                <span wire:loading.remove wire:target="revokeInvitation">Revoke</span>
                <span wire:loading wire:target="revokeInvitation" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </div>
                @endcan
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Role Permission Guide --}}
    <div class="bg-gray-50 rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Role Permissions Guide</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead>
                    <tr class="text-gray-500 border-b border-gray-200">
                        <th class="pb-2 pr-4 font-medium w-44">Permission</th>
                        @foreach($availableRoles as $label)
                        <th class="pb-2 px-2 font-medium text-center">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php
                    $matrix = [
                        'Own contacts & deals'    => [1,1,1,0,1,1],
                        'Team contacts & deals'   => [0,1,1,0,1,1],
                        'All contacts & deals'    => [0,0,0,0,1,1],
                        'Create & edit listings'  => [1,1,1,0,1,1],
                        'Manage campaigns'        => [0,0,0,1,0,1],
                        'View analytics'          => [1,1,1,1,1,1],
                        'Team analytics'          => [0,0,1,0,1,1],
                        'View commissions'        => [0,0,0,0,1,1],
                        'Manage transactions'     => [0,0,0,0,1,1],
                        'Agency settings'         => [0,0,0,0,0,1],
                        'Invite & manage team'    => [0,0,0,0,0,1],
                    ];
                    @endphp
                    @foreach($matrix as $label => $access)
                    <tr>
                        <td class="py-2 pr-4 text-gray-600 font-medium">{{ $label }}</td>
                        @foreach($access as $has)
                        <td class="py-2 px-2 text-center">
                            @if($has)
                                <span class="text-green-500 font-bold">&#10003;</span>
                            @else
                                <span class="text-gray-300">&mdash;</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
