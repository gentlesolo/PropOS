<div class="max-w-xl mx-auto">

    @php $property = $viewing->listing?->property; @endphp

    @if($submitted)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-10 text-center space-y-4">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Thank you for your feedback!</h1>
        <p class="text-gray-500">Your response has been recorded and shared with the agent.</p>
    </div>

    @else
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
        <h1 class="text-xl font-bold text-gray-900 mb-1">How was your viewing?</h1>
        @if($property)
        <p class="text-gray-500 text-sm">{{ $property->address_line_1 }}, {{ $property->city }}</p>
        @endif
        <p class="text-gray-400 text-xs mt-1">Viewed {{ $viewing->scheduled_at->format('D, M j \a\t g:ia') }}</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6">

        {{-- Overall rating --}}
        <div>
            <label class="block text-sm font-semibold text-gray-800 mb-3">Overall impression</label>
            <div class="flex gap-3">
                @foreach([1 => '😞', 2 => '😕', 3 => '😐', 4 => '🙂', 5 => '😍'] as $val => $emoji)
                <button wire:click="$set('overall_rating', {{ $val }})"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative flex-1 py-3 rounded-xl text-2xl border-2 transition
                               {{ $overall_rating == $val ? 'border-blue-500 bg-blue-50 scale-110' : 'border-gray-200 hover:border-gray-300' }}" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">{{ $emoji }}</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                @endforeach
            </div>
            <div class="flex justify-between text-xs text-gray-400 mt-1 px-1">
                <span>Not for me</span><span>Love it</span>
            </div>
        </div>

        {{-- Price perception --}}
        <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">Price feels…</label>
            <div class="flex gap-2">
                @foreach([1 => 'Very High', 2 => 'High', 3 => 'Fair', 4 => 'Good', 5 => 'Great Value'] as $val => $label)
                <button wire:click="$set('price_perception', {{ $val }})"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative flex-1 py-2 rounded-lg text-xs font-medium border-2 transition
                               {{ $price_perception == $val ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600 hover:border-gray-300' }}" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">{{ $label }}</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                @endforeach
            </div>
        </div>

        {{-- Interest level --}}
        <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">Your interest level</label>
            <div class="grid grid-cols-2 gap-2">
                @foreach(['very_interested' => '🔥 Very Interested', 'interested' => '👍 Interested', 'maybe' => '🤔 Maybe', 'not_interested' => '❌ Not for me'] as $val => $label)
                <button wire:click="$set('interest_level', '{{ $val }}')"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative py-2.5 rounded-xl text-sm font-medium border-2 transition
                               {{ $interest_level === $val ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-600 hover:border-gray-300' }}" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">{{ $label }}</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                @endforeach
            </div>
        </div>

        {{-- What you liked --}}
        <div>
            <label class="block text-sm font-semibold text-gray-800 mb-1">What did you like most?</label>
            <textarea wire:model="positive_notes" rows="2"
                      placeholder="e.g. The kitchen, the light, the location…"
                      class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
        </div>

        {{-- Concerns --}}
        <div>
            <label class="block text-sm font-semibold text-gray-800 mb-1">Any concerns or deal-breakers?</label>
            <textarea wire:model="concerns" rows="2"
                      placeholder="e.g. Needs renovation, too far from schools…"
                      class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
        </div>

        {{-- Would make offer --}}
        <div class="flex items-center gap-3">
            <input type="checkbox" wire:model="would_make_offer" id="offer"
                   class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <label for="offer" class="text-sm font-medium text-gray-800">
                I'm interested in making an offer
            </label>
        </div>

        <button wire:click="submit" wire:loading.attr="disabled"
                class="w-full py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition disabled:opacity-60">
            <span wire:loading.remove wire:target="submit">Submit Feedback</span>
            <span wire:loading wire:target="submit">Submitting…</span>
        </button>
    </div>
    @endif

</div>
