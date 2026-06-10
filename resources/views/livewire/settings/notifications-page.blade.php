<div>
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Notification Settings</h1>
        <p class="mt-2 text-text-secondary">Configure which automated email reminders are sent to tenants and agents.</p>
    </div>

    <!-- Tab bar -->
    <div class="flex gap-1 mb-6 border-b border-border-default">
        <a href="{{ route('settings.profile') }}"
           class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px border-transparent text-text-secondary hover:text-text-primary">
            Profile & Agency
        </a>
        <a href="{{ route('settings.email-accounts') }}"
           class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px border-transparent text-text-secondary hover:text-text-primary">
            Email Accounts
        </a>
        <a href="{{ route('settings.notifications') }}"
           class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px border-brand-primary text-brand-primary">
            Notifications
        </a>
    </div>

    <div class="max-w-2xl space-y-6">

        {{-- ── Lease Expiry Reminders ─────────────────────────────────────── --}}
        <div class="bg-surface-card rounded-2xl border border-border-default p-6">

            <div class="flex items-center justify-between mb-1">
                <h2 class="text-base font-semibold text-text-primary">Lease Expiry Reminders</h2>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model.live="leaseRemindersEnabled" class="sr-only peer">
                    <div class="w-10 h-5 bg-border-default peer-checked:bg-brand-primary rounded-full transition-colors"></div>
                    <div class="absolute left-0.5 top-0.5 bg-white w-4 h-4 rounded-full transition-transform peer-checked:translate-x-5 shadow pointer-events-none"></div>
                </label>
            </div>
            <p class="text-sm text-text-secondary mb-5">Automated emails sent to tenants and their assigned agents before (and on) the lease end date. You can add up to {{ $maxReminders }} reminders.</p>

            <div class="space-y-3 {{ $leaseRemindersEnabled ? '' : 'opacity-40 pointer-events-none' }}">

                @foreach($reminders as $i => $reminder)
                <div x-data="{ open: false }" class="rounded-xl border border-border-default bg-surface-hover/30 overflow-hidden">

                    <div class="flex items-center gap-3 p-3">
                        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                            <input type="checkbox" wire:model.live="reminders.{{ $i }}.enabled" class="sr-only peer">
                            <div class="w-9 h-5 bg-border-default peer-checked:bg-brand-primary rounded-full transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 bg-white w-4 h-4 rounded-full transition-transform peer-checked:translate-x-4 shadow pointer-events-none"></div>
                        </label>

                        <div class="flex items-center gap-2 flex-1 {{ ($reminder['enabled'] ?? true) ? '' : 'opacity-50' }}">
                            <input type="number"
                                   wire:model.live="reminders.{{ $i }}.days"
                                   min="0" max="365"
                                   class="w-20 rounded-lg border border-border-default bg-surface-input px-3 py-1.5 text-sm text-text-primary text-center focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                            <span class="text-sm text-text-secondary">
                                {{ ($reminder['days'] ?? 0) == 0 ? 'days before (sends on expiry day)' : 'days before expiry' }}
                            </span>
                        </div>

                        <button type="button" @click="open = !open"
                            class="flex items-center gap-1 text-xs text-text-secondary hover:text-brand-primary transition-colors flex-shrink-0 px-2 py-1 rounded-lg hover:bg-surface-hover">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span x-text="open ? 'Hide Templates' : 'Email Templates'"></span>
                            <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        @if(count($reminders) > 1)
                        <button wire:click="removeReminder({{ $i }})"
                            class="flex-shrink-0 text-text-tertiary hover:text-danger-600 transition-colors p-1 rounded-lg hover:bg-danger-50">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        @else
                        <div class="w-6 flex-shrink-0"></div>
                        @endif
                    </div>

                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="border-t border-border-default">
                        <div class="p-4 space-y-5">
                            <p class="text-xs text-text-tertiary leading-relaxed">
                                Available placeholders:
                                <span class="font-mono text-brand-primary">{first_name}</span>,
                                <span class="font-mono text-brand-primary">{full_name}</span>,
                                <span class="font-mono text-brand-primary">{address}</span>,
                                <span class="font-mono text-brand-primary">{reference}</span>,
                                <span class="font-mono text-brand-primary">{end_date}</span>,
                                <span class="font-mono text-brand-primary">{days}</span>,
                                <span class="font-mono text-brand-primary">{portal_url}</span>
                                <span class="text-text-tertiary">(tenant)</span> &nbsp;·&nbsp;
                                <span class="font-mono text-brand-primary">{agent_name}</span>
                                <span class="text-text-tertiary">(agent)</span>.
                                Leave blank to use the default template.
                            </p>

                            {{-- Tenant email --}}
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium text-text-primary">Tenant Email</h4>
                                    <button type="button"
                                        wire:click="generateEmailContent({{ $i }}, 'tenant')"
                                        wire:loading.attr="disabled"
                                        wire:target="generateEmailContent({{ $i }}, 'tenant')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-brand-primary/10 text-brand-primary hover:bg-brand-primary/20 transition-colors disabled:opacity-50">
                                        <span wire:loading.remove wire:target="generateEmailContent({{ $i }}, 'tenant')" class="inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.346A3.9 3.9 0 0114 18H10a3.9 3.9 0 01-2.796-1.172l-.347-.346z"/>
                                            </svg>
                                            AI Generate (2 credits)
                                        </span>
                                        <span wire:loading wire:target="generateEmailContent({{ $i }}, 'tenant')">Generating…</span>
                                    </button>
                                </div>
                                <input type="text"
                                    wire:model.live="reminders.{{ $i }}.tenant_subject"
                                    placeholder="Subject line (leave blank for default)"
                                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary placeholder-text-tertiary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                                <textarea
                                    wire:model.live="reminders.{{ $i }}.tenant_body"
                                    rows="4"
                                    placeholder="Email body (leave blank for default)"
                                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary placeholder-text-tertiary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page resize-y"></textarea>
                            </div>

                            <hr class="border-border-default">

                            {{-- Agent email --}}
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium text-text-primary">Agent Email</h4>
                                    <button type="button"
                                        wire:click="generateEmailContent({{ $i }}, 'agent')"
                                        wire:loading.attr="disabled"
                                        wire:target="generateEmailContent({{ $i }}, 'agent')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-brand-primary/10 text-brand-primary hover:bg-brand-primary/20 transition-colors disabled:opacity-50">
                                        <span wire:loading.remove wire:target="generateEmailContent({{ $i }}, 'agent')" class="inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.346A3.9 3.9 0 0114 18H10a3.9 3.9 0 01-2.796-1.172l-.347-.346z"/>
                                            </svg>
                                            AI Generate (2 credits)
                                        </span>
                                        <span wire:loading wire:target="generateEmailContent({{ $i }}, 'agent')">Generating…</span>
                                    </button>
                                </div>
                                <input type="text"
                                    wire:model.live="reminders.{{ $i }}.agent_subject"
                                    placeholder="Subject line (leave blank for default)"
                                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary placeholder-text-tertiary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                                <textarea
                                    wire:model.live="reminders.{{ $i }}.agent_body"
                                    rows="4"
                                    placeholder="Email body (leave blank for default)"
                                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary placeholder-text-tertiary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page resize-y"></textarea>
                            </div>
                        </div>
                    </div>

                </div>
                @endforeach

                @error('reminders.*.days')
                <p class="text-xs text-danger-600 mt-1">{{ $message }}</p>
                @enderror

                @if(count($reminders) < $maxReminders)
                <button wire:click="addReminder"
                    class="w-full py-2 border border-dashed border-border-default rounded-xl text-sm text-text-secondary hover:text-brand-primary hover:border-brand-primary transition-colors flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Reminder
                </button>
                @endif

            </div>
        </div>

        {{-- ── In-App Notification Templates ─────────────────────────────── --}}
        @foreach($groupedTypes as $group => $types)
        <div class="bg-surface-card rounded-2xl border border-border-default p-6">

            <h2 class="text-base font-semibold text-text-primary mb-1">{{ $group }} Notifications</h2>
            <p class="text-sm text-text-secondary mb-5">Customise in-app notification content for {{ strtolower($group) }} events. Leave fields blank to use system defaults.</p>

            <div class="space-y-2">
                @foreach($types as $type => $meta)
                <div x-data="{ open: false }" class="rounded-xl border border-border-default bg-surface-hover/30 overflow-hidden">

                    {{-- Row header --}}
                    <div class="flex items-center gap-3 p-3">

                        {{-- Enabled toggle --}}
                        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                            <input type="checkbox"
                                   wire:model.live="notificationTemplates.{{ $type }}.enabled"
                                   class="sr-only peer">
                            <div class="w-9 h-5 bg-border-default peer-checked:bg-brand-primary rounded-full transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 bg-white w-4 h-4 rounded-full transition-transform peer-checked:translate-x-4 shadow pointer-events-none"></div>
                        </label>

                        {{-- Label & description --}}
                        <div class="flex-1 min-w-0 {{ ($notificationTemplates[$type]['enabled'] ?? true) ? '' : 'opacity-50' }}">
                            <p class="text-sm font-medium text-text-primary leading-tight">{{ $meta['label'] }}</p>
                            <p class="text-xs text-text-tertiary leading-tight mt-0.5 truncate">{{ $meta['description'] }}</p>
                        </div>

                        {{-- Expand button --}}
                        <button type="button" @click="open = !open"
                            class="flex items-center gap-1 text-xs text-text-secondary hover:text-brand-primary transition-colors flex-shrink-0 px-2 py-1 rounded-lg hover:bg-surface-hover">
                            <span x-text="open ? 'Hide' : 'Edit'"></span>
                            <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                    </div>

                    {{-- Collapsible editor --}}
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="border-t border-border-default">
                        <div class="p-4 space-y-3">

                            {{-- Placeholder hint --}}
                            <p class="text-xs text-text-tertiary">
                                Placeholders:
                                @foreach($meta['placeholders'] as $ph)
                                <span class="font-mono text-brand-primary">{{ $ph }}</span>{{ ! $loop->last ? ',' : '' }}
                                @endforeach
                                — leave blank to use the system default.
                            </p>

                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-text-secondary uppercase tracking-wide">Notification Content</span>
                                <button type="button"
                                    wire:click="generateNotificationContent('{{ $type }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="generateNotificationContent('{{ $type }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-brand-primary/10 text-brand-primary hover:bg-brand-primary/20 transition-colors disabled:opacity-50">
                                    <span wire:loading.remove wire:target="generateNotificationContent('{{ $type }}')" class="inline-flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.346A3.9 3.9 0 0114 18H10a3.9 3.9 0 01-2.796-1.172l-.347-.346z"/>
                                        </svg>
                                        AI Generate (1 credit)
                                    </span>
                                    <span wire:loading wire:target="generateNotificationContent('{{ $type }}')">Generating…</span>
                                </button>
                            </div>

                            <input type="text"
                                wire:model.live="notificationTemplates.{{ $type }}.title"
                                placeholder="Title (leave blank for default)"
                                class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary placeholder-text-tertiary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">

                            <textarea
                                wire:model.live="notificationTemplates.{{ $type }}.body"
                                rows="3"
                                placeholder="Body (leave blank for default)"
                                class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary placeholder-text-tertiary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page resize-y"></textarea>

                        </div>
                    </div>

                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        {{-- Save --}}
        <div class="flex justify-end">
            <button wire:click="save" wire:loading.attr="disabled"
                class="px-6 py-2.5 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-hover transition-colors">
                <span wire:loading.remove wire:target="save">Save Settings</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>

    </div>
</div>
