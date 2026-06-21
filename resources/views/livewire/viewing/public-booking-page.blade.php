<div class="max-w-2xl mx-auto">

    @if($booked)
    {{-- Confirmation --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-10 text-center space-y-4">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Viewing Confirmed!</h1>
        <p class="text-gray-500">
            Your viewing has been booked for
            <strong>{{ \Carbon\Carbon::parse($selectedDate . ' ' . $selectedSlot)->format('l, F j \a\t g:ia') }}</strong>.
        </p>
        <p class="text-gray-500">
            {{ $listing->property->address_line_1 }}, {{ $listing->property->city }}
        </p>
        <p class="text-sm text-gray-400">
            You will receive a confirmation shortly. The agent will be in touch to confirm your appointment.
        </p>
    </div>

    @else
    {{-- Property Header --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        @if($listing->coverPhoto)
        <img src="{{ asset('storage/' . $listing->coverPhoto->file_path) }}"
             alt="{{ $listing->headline }}"
             class="w-full h-48 object-cover" />
        @else
        <div class="w-full h-32 bg-gradient-to-r from-blue-100 to-blue-200"></div>
        @endif

        <div class="p-6">
            <h1 class="text-xl font-bold text-gray-900">
                {{ $listing->property->address_line_1 }}, {{ $listing->property->city }}
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                {{ $listing->property->bedrooms }} bed &middot;
                {{ $listing->property->bathrooms }} bath &middot;
                {{ $listing->property->property_type }}
                &mdash;
                <span class="font-semibold text-gray-800">{{ number_format($listing->listing_price) }}</span>
            </p>
            @if($listing->agent)
            <p class="text-sm text-gray-400 mt-1">Agent: {{ $listing->agent->name }}</p>
            @endif
        </div>
    </div>

    {{-- Date Navigation --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <button wire:click="previousDay" class="disabled:opacity-70 disabled:cursor-not-allowed relative p-2 rounded-lg hover:bg-gray-100 text-gray-600" wire:loading.attr="disabled" wire:target="previousDay">
                <span wire:loading.remove wire:target="previousDay">&#8592;</span>
                <span wire:loading wire:target="previousDay" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            <span class="font-semibold text-gray-800">
                {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}
            </span>
            <button wire:click="nextDay" class="disabled:opacity-70 disabled:cursor-not-allowed relative p-2 rounded-lg hover:bg-gray-100 text-gray-600" wire:loading.attr="disabled" wire:target="nextDay">
                <span wire:loading.remove wire:target="nextDay">&#8594;</span>
                <span wire:loading wire:target="nextDay" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
        </div>

        {{-- Slots --}}
        @if(count($availableSlots) === 0)
        <p class="text-center text-gray-400 py-4 text-sm">No available slots on this day. Try another date.</p>
        @else
        <div class="grid grid-cols-3 gap-2">
            @foreach($availableSlots as $slot)
            <button wire:click="selectSlot('{{ $slot }}')"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative py-2 rounded-lg text-sm font-medium border transition
                        {{ $selectedSlot === $slot
                            ? 'bg-blue-600 text-white border-blue-600'
                            : 'bg-white text-gray-700 border-gray-300 hover:border-blue-400' }}" wire:loading.attr="disabled" wire:target="selectSlot">
                <span wire:loading.remove wire:target="selectSlot">{{ \Carbon\Carbon::parse($selectedDate . ' ' . $slot)->format('g:ia') }}</span>
                <span wire:loading wire:target="selectSlot" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Guest Details Form --}}
    @if($selectedSlot)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
        <h2 class="text-base font-semibold text-gray-800">Your Details</h2>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
            <input type="text" wire:model="guest_name" placeholder="Your full name"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            @error('guest_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
            <input type="tel" wire:model="guest_phone" placeholder="+234 800 000 0000"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            @error('guest_phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
            <input type="email" wire:model="guest_email" placeholder="you@example.com"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            @error('guest_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Message (optional)</label>
            <textarea wire:model="guest_message" rows="2" placeholder="Any requirements or questions…"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
        </div>

        <div class="pt-2">
            <p class="text-sm text-gray-500 mb-3">
                Booking for
                <strong>{{ \Carbon\Carbon::parse($selectedDate . ' ' . $selectedSlot)->format('D, M j \a\t g:ia') }}</strong>
                at {{ $listing->property->address_line_1 }}.
            </p>
            <button wire:click="confirmBooking" wire:loading.attr="disabled"
                    class="w-full py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition disabled:opacity-60">
                <span wire:loading.remove wire:target="confirmBooking">Confirm Viewing</span>
                <span wire:loading wire:target="confirmBooking">Booking…</span>
            </button>
        </div>
    </div>
    @endif
    @endif

</div>
