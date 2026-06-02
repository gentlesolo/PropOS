<div class="space-y-8 max-w-5xl">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">API Keys</h1>
            <p class="text-sm text-gray-500 mt-1">Generate keys to power your website widgets, WordPress plugin, or custom integration.</p>
        </div>
        @can('agency.manage')
        <button wire:click="$set('showForm', true)"
                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            + New API Key
        </button>
        @endcan
    </div>

    {{-- New token reveal banner --}}
    @if($newToken)
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 space-y-2">
        <p class="text-sm font-semibold text-green-800">Key created — copy it now. It will not be shown again.</p>
        <div class="flex items-center gap-3">
            <code class="flex-1 bg-white border border-green-200 rounded-lg px-3 py-2 text-sm font-mono text-gray-800 break-all select-all">{{ $newToken }}</code>
            <button onclick="navigator.clipboard.writeText('{{ $newToken }}').then(() => alert('Copied!'))"
                    class="px-3 py-2 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 shrink-0">Copy</button>
        </div>
        <button wire:click="dismissToken" class="text-xs text-green-700 underline">Dismiss</button>
    </div>
    @endif

    {{-- Create form modal --}}
    @if($showForm)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Create API Key</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Key Name *</label>
                <input type="text" wire:model="name" placeholder="e.g. WordPress Sync Key"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Access Type *</label>
                <select wire:model="type"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="public_read">Public Read — listings, availability (website widgets, plugins)</option>
                    <option value="full_access">Full Access — read + lead submission (headless developer API)</option>
                </select>
            </div>

            <div class="flex gap-3 justify-end pt-2">
                <button wire:click="$set('showForm', false)" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                <button wire:click="createKey" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="createKey">Generate Key</span>
                    <span wire:loading wire:target="createKey">Generating…</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Keys table --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        @if($keys->isEmpty())
        <div class="py-16 text-center text-gray-400">
            <p class="text-sm">No API keys yet. Create one to connect your website.</p>
        </div>
        @else
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Last Used</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($keys as $key)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-4 text-sm font-medium text-gray-900">{{ $key->name }}</td>
                    <td class="px-5 py-4">
                        @if($key->type === 'public_read')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">Public Read</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-50 text-orange-700">Full Access</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-500">
                        {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-500">{{ $key->created_at->format('d M Y') }}</td>
                    <td class="px-5 py-4 text-right">
                        @can('agency.manage')
                        @if($revokeId === $key->id)
                            <span class="text-xs text-gray-600 mr-2">Revoke?</span>
                            <button wire:click="revokeKey({{ $key->id }})"
                                    class="text-xs text-red-600 hover:text-red-800 font-medium mr-2">Yes, Revoke</button>
                            <button wire:click="$set('revokeId', null)"
                                    class="text-xs text-gray-500 hover:text-gray-700">Cancel</button>
                        @else
                            <button wire:click="$set('revokeId', {{ $key->id }})"
                                    class="text-xs text-red-500 hover:text-red-700">Revoke</button>
                        @endif
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Quick-start snippet --}}
    <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 space-y-3">
        <h3 class="text-sm font-semibold text-gray-800">Quick Start — Embed Listings on Your Website</h3>
        <p class="text-xs text-gray-500">Replace <code class="bg-gray-200 px-1 rounded">YOUR_API_KEY</code> with a Public Read key above.</p>
        <pre class="text-xs bg-gray-900 text-green-300 rounded-lg p-4 overflow-x-auto leading-relaxed"><code>&lt;script src="https://cdn.propos.app/widgets.js" defer&gt;&lt;/script&gt;

&lt;propos-listings-grid
    agency-key="YOUR_API_KEY"
    primary-color="{{ auth()->user()->agency?->primary_color ?? '#1E40AF' }}"
    items-per-page="9"
    view-type="grid"&gt;
&lt;/propos-listings-grid&gt;</code></pre>
    </div>

</div>
