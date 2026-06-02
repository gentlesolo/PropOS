<div>
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-text-primary dark:text-white">Settings</h1>
        <p class="mt-2 text-text-secondary dark:text-text-tertiary">Manage your profile, agency branding, and team members.</p>
    </div>

    <!-- Tab Navigation -->
    <div class="flex gap-1 mb-6 border-b border-border-default">
        @foreach(['profile' => 'My Profile', 'agency' => 'Agency', 'team' => 'Team', 'security' => 'Security'] as $tab => $label)
        <button wire:click="$set('activeTab', '{{ $tab }}')"
            class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px
            {{ $activeTab === $tab ? 'border-brand-primary text-brand-primary' : 'border-transparent text-text-secondary hover:text-text-primary' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>

    <!-- Flash Messages -->
    @foreach(['profile_saved' => 'Profile updated.', 'password_saved' => 'Password changed.', 'agency_saved' => 'Agency settings saved.', 'invite_sent' => null, 'mls_sync_triggered' => 'Background MLS sync triggered.'] as $key => $default)
    @if(session($key))
    <div class="mb-4 p-3 bg-success-50 border border-success-200 rounded-xl text-sm text-success-800">
        {{ session($key) ?? $default }}
    </div>
    @endif
    @endforeach

    <!-- Profile Tab -->
    @if($activeTab === 'profile')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-surface-card rounded-2xl border border-border-default p-6">
                <h2 class="text-base font-semibold text-text-primary mb-5">Personal Information</h2>
                <form wire:submit.prevent="saveProfile" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">First Name *</label>
                            <input wire:model.defer="first_name" type="text" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                            @error('first_name') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1">Last Name *</label>
                            <input wire:model.defer="last_name" type="text" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                            @error('last_name') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Email Address *</label>
                        <input wire:model.defer="email" type="email" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        @error('email') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Phone</label>
                        <input wire:model.defer="phone" type="text" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Job Title</label>
                        <input wire:model.defer="job_title" type="text" placeholder="e.g. Senior Agent, Branch Manager" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Bio</label>
                        <textarea wire:model.defer="bio" rows="3" placeholder="A brief bio shown on listings and reports..." class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Profile Photo</label>
                        <input wire:model="avatar" type="file" accept="image/*" class="w-full text-sm text-text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-primary/10 file:text-brand-primary hover:file:bg-brand-primary/20">
                        <div wire:loading wire:target="avatar" class="text-xs text-brand-primary mt-1">Uploading preview...</div>
                    </div>
                    <div class="pt-4 border-t border-border-default flex justify-end">
                        <button type="submit" class="px-5 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
                            <span wire:loading.remove wire:target="saveProfile">Save Profile</span>
                            <span wire:loading wire:target="saveProfile">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="lg:col-span-1">
            <div class="bg-surface-card rounded-2xl border border-border-default p-6 text-center">
                <div class="h-24 w-24 rounded-full bg-brand-primary/10 flex items-center justify-center mx-auto text-brand-primary text-3xl font-bold mb-3">
                    {{ strtoupper(substr(auth()->user()->first_name,0,1).substr(auth()->user()->last_name,0,1)) }}
                </div>
                <p class="font-semibold text-text-primary">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                <p class="text-sm text-text-secondary">{{ auth()->user()->job_title ?? 'Agent' }}</p>
                <p class="text-xs text-text-secondary mt-1">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Agency Tab -->
    @if($activeTab === 'agency')
    <div class="bg-surface-card rounded-2xl border border-border-default p-6 max-w-2xl">
        <h2 class="text-base font-semibold text-text-primary mb-5">Agency Details</h2>
        <form wire:submit.prevent="saveAgency" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">Agency Name *</label>
                <input wire:model.defer="agency_name" type="text" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                @error('agency_name') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Contact Email *</label>
                    <input wire:model.defer="agency_email" type="email" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    @error('agency_email') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Phone</label>
                    <input wire:model.defer="agency_phone" type="text" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">Website</label>
                <input wire:model.defer="agency_website" type="url" placeholder="https://..." class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
            </div>
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">Address</label>
                <textarea wire:model.defer="agency_address" rows="2" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page resize-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Timezone</label>
                    <select wire:model.defer="timezone" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        <option value="UTC">UTC</option>
                        <option value="Africa/Lagos">Africa/Lagos (WAT)</option>
                        <option value="Africa/Johannesburg">Africa/Johannesburg (SAST)</option>
                        <option value="Africa/Nairobi">Africa/Nairobi (EAT)</option>
                        <option value="Africa/Accra">Africa/Accra (GMT)</option>
                        <option value="Europe/London">Europe/London</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Currency</label>
                    <select wire:model.defer="currency" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        <option value="NGN">NGN (₦ Naira)</option>
                        <option value="ZAR">ZAR (R Rand)</option>
                        <option value="GHS">GHS (₵ Cedi)</option>
                        <option value="KES">KES (KSh Shilling)</option>
                        <option value="USD">USD ($ Dollar)</option>
                        <option value="GBP">GBP (£ Pound)</option>
                    </select>
                </div>
            </div>
            {{-- ── Branding ── --}}
            <div class="pt-2 pb-1">
                <h3 class="text-sm font-semibold text-text-primary">Branding</h3>
                <p class="text-xs text-text-secondary mt-0.5">Customise how this agency looks to every user.</p>
            </div>

            {{-- Logo & Favicon --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Agency Logo</label>
                    @if(auth()->user()->agency?->logo_path)
                        <img src="{{ asset('storage/'.auth()->user()->agency->logo_path) }}" class="h-10 mb-2 rounded object-contain" alt="Logo">
                    @endif
                    <input wire:model="agency_logo" type="file" accept="image/*" class="w-full text-sm text-text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-primary/10 file:text-brand-primary hover:file:bg-brand-primary/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Favicon <span class="text-text-tertiary font-normal">(32×32 recommended)</span></label>
                    @if(auth()->user()->agency?->favicon_path)
                        <img src="{{ asset('storage/'.auth()->user()->agency->favicon_path) }}" class="h-8 mb-2 rounded object-contain" alt="Favicon">
                    @endif
                    <input wire:model="favicon" type="file" accept="image/*" class="w-full text-sm text-text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-primary/10 file:text-brand-primary hover:file:bg-brand-primary/20">
                    @error('favicon') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Colors --}}
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Primary Color</label>
                    <div class="flex items-center gap-3">
                        <input wire:model.defer="primary_color" type="color" class="h-10 w-20 rounded-lg border border-border-default cursor-pointer">
                        <input wire:model.defer="primary_color" type="text" placeholder="#1E40AF" class="flex-1 rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary text-sm font-mono">
                    </div>
                    @error('primary_color') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Secondary Color</label>
                        <div class="flex items-center gap-2">
                            <input wire:model.defer="secondary_color" type="color" class="h-10 w-14 rounded-lg border border-border-default cursor-pointer shrink-0">
                            <input wire:model.defer="secondary_color" type="text" placeholder="#18181B" class="flex-1 rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary text-sm font-mono min-w-0">
                        </div>
                        @error('secondary_color') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Accent Color</label>
                        <div class="flex items-center gap-2">
                            <input wire:model.defer="accent_color" type="color" class="h-10 w-14 rounded-lg border border-border-default cursor-pointer shrink-0">
                            <input wire:model.defer="accent_color" type="text" placeholder="#F59E0B" class="flex-1 rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary text-sm font-mono min-w-0">
                        </div>
                        @error('accent_color') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Typography, Radius, Sidebar --}}
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Font Family</label>
                    <select wire:model.defer="font_family" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        <option value="">Geist (default)</option>
                        <option value="Inter">Inter</option>
                        <option value="Poppins">Poppins</option>
                        <option value="Lato">Lato</option>
                        <option value="Roboto">Roboto</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Corner Style</label>
                    <select wire:model.defer="border_radius" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        <option value="sharp">Sharp</option>
                        <option value="default">Default</option>
                        <option value="rounded">Rounded</option>
                        <option value="pill">Pill</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Sidebar Style</label>
                    <select wire:model.defer="sidebar_style" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        <option value="dark">Dark</option>
                        <option value="light">Light</option>
                        <option value="brand">Brand Color</option>
                    </select>
                </div>
            </div>

            {{-- Custom CSS --}}
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">
                    Custom CSS
                    <span class="text-text-tertiary font-normal ml-1">— Advanced: injected into every page head</span>
                </label>
                <textarea wire:model.defer="custom_css" rows="5" placeholder=".my-class { color: red; }" spellcheck="false" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-y font-mono text-xs"></textarea>
                @error('custom_css') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div class="pt-4 border-t border-border-default flex justify-end">
                <button type="submit" class="px-5 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
                    <span wire:loading.remove wire:target="saveAgency">Save Agency Settings</span>
                    <span wire:loading wire:target="saveAgency">Saving...</span>
                </button>
            </div>
        </form>
    </div>
    @endif

    <!-- Team Tab -->
    @if($activeTab === 'team')
    <div class="space-y-5">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold text-text-primary">Team Members</h2>
            <button wire:click="$toggle('showInviteForm')" class="px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
                + Invite Member
            </button>
        </div>

        @if($showInviteForm)
        <div class="bg-surface-card rounded-2xl border border-border-default p-5 max-w-lg">
            <h3 class="text-sm font-semibold text-text-primary mb-4">Send Team Invitation</h3>
            <form wire:submit.prevent="sendInvitation" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Email Address *</label>
                    <input wire:model.defer="invite_email" type="email" placeholder="colleague@example.com" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    @error('invite_email') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Role *</label>
                    <select wire:model.defer="invite_role" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        <option value="agent">Agent</option>
                        <option value="admin">Admin</option>
                        <option value="viewer">Viewer (Read-only)</option>
                        <option value="principal">Principal</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
                        <span wire:loading.remove wire:target="sendInvitation">Send Invitation</span>
                        <span wire:loading wire:target="sendInvitation">Sending...</span>
                    </button>
                    <button type="button" wire:click="$set('showInviteForm', false)" class="px-4 py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-sunken transition-colors">Cancel</button>
                </div>
            </form>
        </div>
        @endif

        <!-- Current Members -->
        <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
            <div class="px-6 py-3 bg-surface-sunken/30 border-b border-border-default">
                <h3 class="text-sm font-semibold text-text-primary">Active Members ({{ $teamMembers->count() }})</h3>
            </div>
            @forelse($teamMembers as $member)
            <div class="flex items-center justify-between px-6 py-4 border-b border-border-default/40 last:border-0">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-full bg-brand-primary/10 flex items-center justify-center text-brand-primary text-sm font-bold">
                        {{ strtoupper(substr($member->first_name,0,1).substr($member->last_name,0,1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-text-primary">{{ $member->first_name }} {{ $member->last_name }}
                            @if($member->id === auth()->id()) <span class="text-xs text-text-secondary ml-1">(You)</span> @endif
                        </p>
                        <p class="text-xs text-text-secondary">{{ $member->email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @if($member->roles->isNotEmpty())
                    <span class="px-2 py-0.5 bg-brand-primary/10 text-brand-primary rounded-full text-xs font-medium capitalize">
                        {{ $member->roles->first()->name }}
                    </span>
                    @endif
                    <span class="h-2 w-2 rounded-full {{ $member->status === 'active' ? 'bg-success-500' : 'bg-border-strong' }}"></span>
                </div>
            </div>
            @empty
            <div class="px-6 py-8 text-center text-sm text-text-secondary">No team members yet.</div>
            @endforelse
        </div>

        <!-- Pending Invitations -->
        @if($pendingInvitations->isNotEmpty())
        <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
            <div class="px-6 py-3 bg-surface-sunken/30 border-b border-border-default">
                <h3 class="text-sm font-semibold text-text-primary">Pending Invitations</h3>
            </div>
            @foreach($pendingInvitations as $invite)
            <div class="flex items-center justify-between px-6 py-3 border-b border-border-default/40 last:border-0">
                <div>
                    <p class="text-sm text-text-primary">{{ $invite->email }}</p>
                    <p class="text-xs text-text-secondary capitalize">{{ $invite->role }} · Invited {{ $invite->created_at->diffForHumans() }}</p>
                </div>
                <button wire:click="revokeInvitation({{ $invite->id }})" wire:confirm="Revoke this invitation?" class="text-xs text-danger-600 hover:text-danger-700 font-medium">Revoke</button>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    <!-- Security Tab -->
    @if($activeTab === 'security')
    <div class="space-y-5 max-w-lg">
        <div class="bg-surface-card rounded-2xl border border-border-default p-6">
            <h2 class="text-base font-semibold text-text-primary mb-5">Change Password</h2>
            <form wire:submit.prevent="savePassword" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Current Password</label>
                    <input wire:model.defer="current_password" type="password" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    @error('current_password') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">New Password</label>
                    <input wire:model.defer="new_password" type="password" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    @error('new_password') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Confirm New Password</label>
                    <input wire:model.defer="new_password_confirmation" type="password" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                </div>
                <div class="pt-2">
                    <button type="submit" class="px-5 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
                        <span wire:loading.remove wire:target="savePassword">Update Password</span>
                        <span wire:loading wire:target="savePassword">Updating...</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-surface-card rounded-2xl border border-border-default p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-base font-semibold text-text-primary">Two-Factor Authentication</h2>
                    <p class="text-sm text-text-secondary mt-1">Add an extra layer of security to your account using a TOTP authenticator app.</p>
                </div>
                @if(auth()->user()->two_factor_enabled)
                <span class="px-2 py-0.5 bg-success-100 text-success-700 rounded-full text-xs font-medium">Enabled</span>
                @else
                <span class="px-2 py-0.5 bg-surface-sunken text-text-secondary rounded-full text-xs font-medium">Disabled</span>
                @endif
            </div>
            <div class="mt-4">
                <a href="{{ route('two-factor.setup') }}" class="px-4 py-2 border border-brand-primary text-brand-primary rounded-xl text-sm font-medium hover:bg-brand-primary/5 transition-colors inline-block">
                    {{ auth()->user()->two_factor_enabled ? 'Manage 2FA' : 'Enable 2FA' }}
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Integrations ──────────────────────────────────────────────────────── --}}
    <div class="bg-surface-card rounded-2xl border border-border-default p-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">Integrations</h2>

        {{-- Google Calendar --}}
        @php
            $googleCred = \App\Infrastructure\Persistence\Models\IntegrationCredential::where('user_id', auth()->id())
                ->where('provider', 'google_calendar')
                ->first();
        @endphp
        <div class="flex items-center justify-between py-4 border-b border-border-default last:border-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-surface-sunken flex items-center justify-center">
                    <svg class="w-5 h-5 text-text-secondary" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.5 3h-3V1.5h-1.5V3h-6V1.5H7.5V3h-3A1.5 1.5 0 003 4.5v15A1.5 1.5 0 004.5 21h15a1.5 1.5 0 001.5-1.5v-15A1.5 1.5 0 0019.5 3zm0 16.5h-15V9h15v10.5zM7.5 4.5V6H9V4.5h6V6h1.5V4.5H19.5V7.5h-15V4.5H7.5z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-text-primary">Google Calendar</p>
                    <p class="text-xs text-text-secondary">Sync viewings and tasks to your Google Calendar.</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @if($googleCred)
                    <span class="px-2 py-0.5 bg-success-100 text-success-700 rounded-full text-xs font-medium">Connected</span>
                    <a href="{{ route('google-calendar.disconnect') }}"
                       onclick="return confirm('Disconnect Google Calendar?')"
                       class="px-3 py-1.5 border border-danger-300 text-danger-600 rounded-lg text-xs font-medium hover:bg-danger-50 transition-colors">
                        Disconnect
                    </a>
                @else
                    <span class="px-2 py-0.5 bg-surface-sunken text-text-secondary rounded-full text-xs font-medium">Not connected</span>
                    <a href="{{ route('google-calendar.redirect') }}"
                       class="px-3 py-1.5 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-lg text-xs font-medium hover:bg-brand-secondary transition-colors">
                        Connect
                    </a>
                @endif
            </div>
        </div>

        {{-- MLS Sync Integration --}}
        <div class="flex items-center justify-between py-4 border-b border-border-default last:border-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-surface-sunken flex items-center justify-center">
                    <svg class="w-5 h-5 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-text-primary">MLS / IDX Two-Way Sync</p>
                    <p class="text-xs text-text-secondary">Simulate updating database listings from remote MLS/IDX servers.</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button wire:click="runMlsSyncJob" class="px-3 py-1.5 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-lg text-xs font-medium hover:bg-brand-secondary transition-colors">
                    <span wire:loading.remove wire:target="runMlsSyncJob">Run Sync Simulation</span>
                    <span wire:loading wire:target="runMlsSyncJob">Triggering...</span>
                </button>
            </div>
        </div>
    </div>
</div>



