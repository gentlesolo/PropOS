<div class="space-y-10 max-w-5xl">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Website Integration</h1>
        <p class="text-sm text-gray-500 mt-1">Connect your public website to PropOS — widgets, analytics, and WordPress plugin.</p>
    </div>

    {{-- ── Tabs nav ─────────────────────────────────────────────────────────── --}}
    <div x-data="{ tab: 'widgets' }" class="space-y-6">
        <div class="flex gap-1 border-b border-gray-200">
            @foreach(['widgets' => 'Embed Widgets', 'wordpress' => 'WordPress Plugin', 'analytics' => 'Analytics & Scripts', 'api' => 'API Reference'] as $key => $label)
            <button @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors">
                {{ $label }}
            </button>
            @endforeach
        </div>

        {{-- ── Widgets tab ──────────────────────────────────────────────────── --}}
        <div x-show="tab === 'widgets'" class="space-y-6">

            @if($apiKeys->isEmpty())
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                You need a <strong>Public Read API key</strong> to use widgets.
                <a href="{{ route('settings.api-keys') }}" class="underline font-semibold ml-1">Create one →</a>
            </div>
            @else

            {{-- Snippet builder --}}
            <div class="bg-white border border-gray-200 rounded-xl p-6 space-y-5">
                <h2 class="text-base font-semibold text-gray-900">Snippet Builder</h2>
                <p class="text-sm text-gray-500">Configure options below and copy the generated snippet into your website's HTML.</p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">API Key</label>
                        <select wire:model="snippet_view_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            @foreach($apiKeys as $key)
                            <option value="{{ $key->name }}">{{ $key->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Manage keys in <a href="{{ route('settings.api-keys') }}" class="underline">API Keys</a>.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">View Type</label>
                        <select wire:model="snippet_view_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="grid">Grid</option>
                            <option value="list">List</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Items Per Page</label>
                        <input type="number" wire:model="snippet_per_page" min="3" max="24" step="3"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Filter by City (optional)</label>
                        <input type="text" wire:model="snippet_city" placeholder="e.g. Lagos"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Mandate Type (optional)</label>
                        <select wire:model="snippet_mandate_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">All</option>
                            <option value="sale">Sale</option>
                            <option value="rental">Rental</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-2">Generated Snippet</label>
                    <pre class="text-xs bg-gray-900 text-green-300 rounded-lg p-4 overflow-x-auto leading-relaxed select-all"><code>&lt;script src="https://cdn.propos.app/widgets.js" defer&gt;&lt;/script&gt;

&lt;propos-listings-grid
    agency-key="YOUR_PUBLIC_READ_KEY"
    primary-color="{{ $agency->primary_color ?? '#1E40AF' }}"
    items-per-page="{{ $snippet_per_page }}"
    view-type="{{ $snippet_view_type }}"{{ $snippet_city ? "\n    city=\"{$snippet_city}\"" : '' }}{{ $snippet_mandate_type ? "\n    mandate-type=\"{$snippet_mandate_type}\"" : '' }}&gt;
&lt;/propos-listings-grid&gt;</code></pre>
                </div>
            </div>

            {{-- Other widget snippets --}}
            <div class="grid grid-cols-1 gap-4">
                @foreach([
                    ['title' => 'Listing Detail', 'desc' => 'Embed a full listing detail view on any page.', 'code' => '<propos-listing-details agency-key="YOUR_KEY" listing-id="123" primary-color="' . ($agency->primary_color ?? '#1E40AF') . '"></propos-listing-details>'],
                    ['title' => 'Inquiry Form', 'desc' => 'Contact form that sends leads directly to your CRM.', 'code' => '<propos-inquiry-form agency-key="YOUR_KEY" listing-id="123" primary-color="' . ($agency->primary_color ?? '#1E40AF') . '"></propos-inquiry-form>'],
                    ['title' => 'Booking Scheduler', 'desc' => 'Let buyers book viewings from your website.', 'code' => '<propos-booking-scheduler agency-key="YOUR_KEY" agent-id="1" listing-id="123" primary-color="' . ($agency->primary_color ?? '#1E40AF') . '"></propos-booking-scheduler>'],
                ] as $widget)
                <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-2">
                    <h3 class="text-sm font-semibold text-gray-800">{{ $widget['title'] }}</h3>
                    <p class="text-xs text-gray-500">{{ $widget['desc'] }}</p>
                    <pre class="text-xs bg-gray-900 text-green-300 rounded-lg p-3 overflow-x-auto select-all"><code>{{ $widget['code'] }}</code></pre>
                </div>
                @endforeach
            </div>

            @endif
        </div>

        {{-- ── WordPress Plugin tab ─────────────────────────────────────────── --}}
        <div x-show="tab === 'wordpress'" class="space-y-6">
            <div class="bg-white border border-gray-200 rounded-xl p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">PropOS Sync — WordPress Plugin</h2>
                <p class="text-sm text-gray-600">Syncs listings from PropOS into WordPress as a native Custom Post Type with full SEO support.</p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="bg-gray-50 rounded-lg p-4 space-y-1">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Step 1</p>
                        <p class="text-sm font-medium text-gray-900">Install the Plugin</p>
                        <p class="text-xs text-gray-500">Upload the <code class="bg-gray-200 px-1 rounded">propos-sync</code> folder to <code>/wp-content/plugins/</code> and activate it.</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-1">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Step 2</p>
                        <p class="text-sm font-medium text-gray-900">Add Your API Key</p>
                        <p class="text-xs text-gray-500">In WordPress go to <strong>Listings (PropOS) → Settings</strong> and paste a Public Read key from <a href="{{ route('settings.api-keys') }}" class="text-blue-600 underline">API Keys</a>.</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-1">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Step 3 (optional)</p>
                        <p class="text-sm font-medium text-gray-900">Enable Instant Webhooks</p>
                        <p class="text-xs text-gray-500">Copy the webhook secret from the plugin settings into <a href="{{ route('settings.webhooks') }}" class="text-blue-600 underline">Webhooks</a> here, then register <code>https://yoursite.com/wp-json/propos-sync/v1/webhook</code> as an endpoint.</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-1">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Shortcodes</p>
                        <p class="text-sm font-medium text-gray-900">Drop listings anywhere</p>
                        <div class="text-xs text-gray-500 space-y-1 font-mono">
                            <p>[propos_listings limit="6" city="Lagos"]</p>
                            <p>[propos_listing id="42"]</p>
                            <p>[propos_inquiry listing_id="42"]</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Analytics tab ────────────────────────────────────────────────── --}}
        <div x-show="tab === 'analytics'" class="space-y-6">
            <div class="bg-white border border-gray-200 rounded-xl p-6 space-y-5">
                <h2 class="text-base font-semibold text-gray-900">Analytics &amp; Tracking Scripts</h2>
                <p class="text-sm text-gray-500">These settings apply to your PropOS-hosted pages and widgets. For third-party sites, add scripts directly to your site.</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Google Analytics 4 Measurement ID</label>
                        <input type="text" wire:model="google_analytics_id" placeholder="G-XXXXXXXXXX"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 font-mono">
                        @error('google_analytics_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Facebook Pixel ID</label>
                        <input type="text" wire:model="facebook_pixel_id" placeholder="1234567890123456"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 font-mono">
                        @error('facebook_pixel_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Custom Header Scripts</label>
                        <textarea wire:model="custom_header_scripts" rows="5"
                                  placeholder="<!-- e.g. TikTok Pixel, Hotjar, custom conversion code -->"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 font-mono"></textarea>
                        <p class="text-xs text-gray-400 mt-1">Injected into the <code>&lt;head&gt;</code> of PropOS-hosted pages. Max 5,000 characters.</p>
                        @error('custom_header_scripts') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                @can('agency.manage')
                <div class="pt-2">
                    <button wire:click="save" wire:loading.attr="disabled"
                            class="px-5 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">Save Settings</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
                @endcan
            </div>
        </div>

        {{-- ── API Reference tab ────────────────────────────────────────────── --}}
        <div x-show="tab === 'api'" class="space-y-4">
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-900">Public REST API — v1</h2>
                    <p class="text-xs text-gray-500 mt-1">Base URL: <code class="bg-gray-100 px-1 rounded">https://propos.app/api/v1/public</code> &nbsp;|&nbsp; Auth: <code class="bg-gray-100 px-1 rounded">Authorization: Bearer YOUR_KEY</code></p>
                </div>
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Method</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Endpoint</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Rate Limit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach([
                            ['GET',  '/listings',                    'Paginated active listings. Filters: city, mandate_type, min_price, max_price, bedrooms, per_page.', '60/min'],
                            ['GET',  '/listings/{id}',               'Full listing detail with media, agent, and description.', '60/min'],
                            ['POST', '/leads',                       'Submit a contact inquiry. Deduplicates and scores automatically.', '10/min'],
                            ['POST', '/bookings',                    'Book a viewing slot. Returns viewing_id and agent confirmation details.', '5/min'],
                            ['GET',  '/agents/{id}/availability',    'Timezone-aware 30-min free slots for the next 14 days.', '60/min'],
                        ] as $ep)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <span class="inline-block px-2 py-0.5 text-xs font-bold rounded font-mono
                                    {{ $ep[0] === 'GET' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $ep[0] }}
                                </span>
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-gray-700">{{ $ep[1] }}</td>
                            <td class="px-5 py-3 text-xs text-gray-600">{{ $ep[2] }}</td>
                            <td class="px-5 py-3 text-xs text-gray-500">{{ $ep[3] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 space-y-3">
                <h3 class="text-sm font-semibold text-gray-800">Webhook Events</h3>
                <p class="text-xs text-gray-500">Register endpoints in <a href="{{ route('settings.webhooks') }}" class="text-blue-600 underline">Webhooks</a>. All payloads are HMAC-SHA256 signed.</p>
                <div class="flex flex-wrap gap-2">
                    @foreach(['listing.published', 'listing.updated', 'listing.price_reduced', 'listing.deleted', 'viewing.scheduled'] as $event)
                    <span class="bg-white border border-gray-200 rounded-full px-3 py-1 text-xs font-mono text-gray-700">{{ $event }}</span>
                    @endforeach
                </div>
            </div>
        </div>

    </div>{{-- end x-data --}}

</div>
