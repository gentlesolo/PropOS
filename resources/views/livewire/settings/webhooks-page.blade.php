<div class="space-y-8 max-w-5xl">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Webhook Endpoints</h1>
            <p class="text-sm text-gray-500 mt-1">Receive real-time event payloads on your server whenever listings or viewings change.</p>
        </div>
        @can('agency.manage')
        <button wire:click="$set('showForm', true)"
                class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">+ Add Endpoint</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
        @endcan
    </div>

    {{-- Secret reveal banner --}}
    @if($revealedSecret)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 space-y-2">
        <p class="text-sm font-semibold text-amber-800">Webhook secret — copy it now. It will not be shown again.</p>
        <p class="text-xs text-amber-700">Use this to verify the <code>X-VillaCRM-Signature-256</code> header on incoming payloads.</p>
        <div class="flex items-center gap-3">
            <code class="flex-1 bg-white border border-amber-200 rounded-lg px-3 py-2 text-sm font-mono text-gray-800 break-all select-all">{{ $revealedSecret }}</code>
            <button onclick="navigator.clipboard.writeText('{{ $revealedSecret }}').then(() => alert('Copied!'))"
                    class="px-3 py-2 bg-amber-600 text-white text-xs font-medium rounded-lg hover:bg-amber-700 shrink-0">Copy</button>
        </div>
        <button wire:click="dismissSecret" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-amber-700 underline" wire:loading.attr="disabled" wire:target="dismissSecret">
                <span wire:loading.remove wire:target="dismissSecret">Dismiss</span>
                <span wire:loading wire:target="dismissSecret" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
    </div>
    @endif

    {{-- Add endpoint modal --}}
    @if($showForm)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Register Webhook Endpoint</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Endpoint URL *</label>
                <input type="url" wire:model="url" placeholder="https://yoursite.com/webhooks/villacrm"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                @error('url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Events to Subscribe *</label>
                <div class="space-y-2">
                    @foreach($availableEvents as $value => $label)
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox" wire:model="events" value="{{ $value }}"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        <span class="text-gray-700">{{ $label }}</span>
                        <code class="text-xs text-gray-400">{{ $value }}</code>
                    </label>
                    @endforeach
                </div>
                @error('events') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="text-xs text-gray-500 bg-gray-50 rounded-lg p-3">
                VillaCRM will POST a JSON payload to your URL with an <code>X-VillaCRM-Signature-256</code> HMAC-SHA256 header for verification.
            </div>

            <div class="flex gap-3 justify-end pt-2">
                <button wire:click="$set('showForm', false)" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-2 text-sm text-gray-600 hover:text-gray-800" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button wire:click="addSubscription" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="addSubscription">Register Endpoint</span>
                    <span wire:loading wire:target="addSubscription">Registering…</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Subscriptions list --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        @if($subscriptions->isEmpty())
        <div class="py-16 text-center text-gray-400">
            <p class="text-sm">No webhook endpoints yet.</p>
        </div>
        @else
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">URL</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Events</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Last Triggered</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($subscriptions as $sub)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-4 text-sm text-gray-800 font-mono max-w-xs truncate">{{ $sub->url }}</td>
                    <td class="px-5 py-4">
                        <div class="flex flex-wrap gap-1">
                            @foreach($sub->events as $event)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-600">{{ $event }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        @if($sub->is_active)
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span> Paused
                                @if($sub->failure_count >= 10)
                                <span class="text-red-500">(auto-disabled)</span>
                                @endif
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-500">
                        {{ $sub->last_triggered_at ? $sub->last_triggered_at->diffForHumans() : 'Never' }}
                    </td>
                    <td class="px-5 py-4 text-right space-x-3">
                        @can('agency.manage')
                        <button wire:click="toggleActive({{ $sub->id }})"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-blue-500 hover:text-blue-700" wire:loading.attr="disabled" wire:target="toggleActive">
                <span wire:loading.remove wire:target="toggleActive">{{ $sub->is_active ? 'Pause' : 'Enable' }}</span>
                <span wire:loading wire:target="toggleActive" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>

                        @if($deleteId === $sub->id)
                            <button wire:click="deleteSubscription({{ $sub->id }})"
                                    class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-red-600 hover:text-red-800 font-medium" wire:loading.attr="disabled" wire:target="deleteSubscription">
                <span wire:loading.remove wire:target="deleteSubscription">Confirm Delete</span>
                <span wire:loading wire:target="deleteSubscription" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                            <button wire:click="$set('deleteId', null)"
                                    class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-gray-500 hover:text-gray-700" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        @else
                            <button wire:click="$set('deleteId', {{ $sub->id }})"
                                    class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-red-500 hover:text-red-700" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Delete</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        @endif
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Signature verification guide --}}
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="text-sm font-semibold text-gray-800">Verifying Webhook Signatures</h3>
        <p class="text-xs text-gray-500">Every request includes an <code class="bg-gray-200 px-1 rounded">X-VillaCRM-Signature-256</code> header. Verify it server-side:</p>
        <pre class="text-xs bg-gray-900 text-green-300 rounded-lg p-4 overflow-x-auto leading-relaxed"><code>// PHP example
$secret    = 'your-webhook-secret';
$body      = file_get_contents('php://input');
$expected  = 'sha256=' . hash_hmac('sha256', $body, $secret);
$received  = $_SERVER['HTTP_X_VILLACRM_SIGNATURE_256'] ?? '';

if (!hash_equals($expected, $received)) {
    http_response_code(401);
    exit;
}</code></pre>
    </div>

</div>
