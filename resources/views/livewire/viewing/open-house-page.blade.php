<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Open Houses</h1>
            <p class="text-sm text-gray-500 mt-1">Schedule, manage, and check in attendees for open house events.</p>
        </div>
        <button wire:click="$set('showCreateForm', true)"
                class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">+ Schedule Open House</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
    </div>

    {{-- Create Form Modal --}}
    @if($showCreateForm)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Schedule Open House</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Listing</label>
                <select wire:model="listing_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Select a listing…</option>
                    @foreach($listings as $listing)
                        <option value="{{ $listing->id }}">
                            {{ $listing->property->address_line_1 }}, {{ $listing->property->city }}
                            — {{ number_format($listing->listing_price) }}
                        </option>
                    @endforeach
                </select>
                @error('listing_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start</label>
                    <input type="datetime-local" wire:model="starts_at"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    @error('starts_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End</label>
                    <input type="datetime-local" wire:model="ends_at"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    @error('ends_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                <textarea wire:model="notes" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                          placeholder="Any special instructions for the open house…"></textarea>
            </div>

            <div class="flex gap-3 justify-end pt-2">
                <button wire:click="$set('showCreateForm', false)"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 text-sm text-gray-600 hover:text-gray-800" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button wire:click="createOpenHouse"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700" wire:loading.attr="disabled" wire:target="createOpenHouse">
                <span wire:loading.remove wire:target="createOpenHouse">Schedule</span>
                <span wire:loading wire:target="createOpenHouse" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Check-in Modal --}}
    @if($checkingInId)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Check In Guest</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input type="text" wire:model="checkin_name" placeholder="Guest full name"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                @error('checkin_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" wire:model="checkin_email" placeholder="guest@example.com"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" wire:model="checkin_phone" placeholder="+234…"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>

            <div class="flex gap-3 justify-end pt-2">
                <button wire:click="$set('checkingInId', null)"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 text-sm text-gray-600 hover:text-gray-800" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button wire:click="checkIn"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700" wire:loading.attr="disabled" wire:target="checkIn">
                <span wire:loading.remove wire:target="checkIn">Check In</span>
                <span wire:loading wire:target="checkIn" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Upcoming Open Houses --}}
    <div class="space-y-4">
        <h2 class="text-base font-semibold text-gray-800">Upcoming & Live</h2>

        @forelse($upcomingOpenHouses as $oh)
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-semibold text-gray-900">
                            {{ $oh->listing->property->address_line_1 }}, {{ $oh->listing->property->city }}
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $oh->status === 'live' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($oh->status) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500">
                        {{ $oh->starts_at->format('D, M j, Y') }}
                        &middot; {{ $oh->starts_at->format('g:ia') }} – {{ $oh->ends_at->format('g:ia') }}
                        &middot; Agent: {{ $oh->agent->name }}
                    </p>
                    @if($oh->notes)
                    <p class="text-xs text-gray-400 mt-1">{{ $oh->notes }}</p>
                    @endif
                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-600">
                        <span>{{ $oh->rsvp_count }} RSVPs</span>
                        <span>{{ $oh->attendance_count }} checked in</span>
                        <button type="button" class="flex items-center gap-1.5 px-2 py-0.5 border border-gray-200 rounded-md bg-gray-50 text-xs font-semibold text-gray-700 hover:bg-gray-100 transition-colors" onclick="navigator.clipboard.writeText('{{ route('openhouses.rsvp', $oh->rsvp_slug) }}'); alert('Open House RSVP link copied!');">
                            <svg class="w-3 h-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 10.742l4.816-2.408m0 0l-4.816-2.408m4.816 2.408v6.824"/></svg>
                            Copy RSVP Link
                        </button>
                    </div>
                </div>

                <div class="flex flex-col gap-2 items-end shrink-0">
                    @if($oh->status === 'scheduled')
                        <button wire:click="markLive({{ $oh->id }})"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700" wire:loading.attr="disabled" wire:target="markLive">
                <span wire:loading.remove wire:target="markLive">Go Live</span>
                <span wire:loading wire:target="markLive" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="cancelOpenHouse({{ $oh->id }})"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative px-3 py-1.5 text-red-600 text-xs hover:underline" wire:loading.attr="disabled" wire:target="cancelOpenHouse">
                <span wire:loading.remove wire:target="cancelOpenHouse">Cancel</span>
                <span wire:loading wire:target="cancelOpenHouse" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    @elseif($oh->status === 'live')
                        <button wire:click="startCheckin({{ $oh->id }})"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700" wire:loading.attr="disabled" wire:target="startCheckin">
                <span wire:loading.remove wire:target="startCheckin">Check In Guest</span>
                <span wire:loading wire:target="startCheckin" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="markCompleted({{ $oh->id }})"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative px-3 py-1.5 text-gray-600 text-xs hover:underline" wire:loading.attr="disabled" wire:target="markCompleted">
                <span wire:loading.remove wire:target="markCompleted">Mark Complete</span>
                <span wire:loading wire:target="markCompleted" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    @endif
                </div>
            </div>

            {{-- RSVP list --}}
            @if($oh->rsvps->isNotEmpty())
            <div class="mt-4 border-t border-gray-100 pt-3">
                <p class="text-xs font-medium text-gray-500 mb-2">Attendees</p>
                <div class="grid grid-cols-2 gap-1">
                    @foreach($oh->rsvps as $rsvp)
                    <div class="flex items-center gap-2 text-sm">
                        <span class="{{ $rsvp->checked_in ? 'text-green-500' : 'text-gray-300' }}">&#10003;</span>
                        <span class="text-gray-700">{{ $rsvp->guest_name }}</span>
                        @if($rsvp->guest_phone)
                        <span class="text-gray-400 text-xs">{{ $rsvp->guest_phone }}</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @empty
        <div class="bg-gray-50 border border-dashed border-gray-200 rounded-xl p-10 text-center">
            <p class="text-gray-400 text-sm">No upcoming open houses. Schedule one to get started.</p>
        </div>
        @endforelse
    </div>

    {{-- Past Open Houses --}}
    @if($pastOpenHouses->isNotEmpty())
    <div class="space-y-3">
        <h2 class="text-base font-semibold text-gray-800">Past Open Houses</h2>
        @foreach($pastOpenHouses as $oh)
        <div class="bg-white border border-gray-200 rounded-lg px-4 py-3 flex items-center justify-between">
            <div>
                <span class="text-sm font-medium text-gray-800">
                    {{ $oh->listing->property->address_line_1 }}, {{ $oh->listing->property->city }}
                </span>
                <span class="ml-2 text-xs text-gray-400">{{ $oh->starts_at->format('M j, Y') }}</span>
            </div>
            <div class="flex items-center gap-4 text-sm text-gray-500">
                <span>{{ $oh->attendance_count }} attended</span>
                <span class="px-2 py-0.5 rounded-full text-xs
                    {{ $oh->status === 'completed' ? 'bg-gray-100 text-gray-600' : 'bg-red-50 text-red-500' }}">
                    {{ ucfirst($oh->status) }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
