<?php

namespace App\Http\Livewire\Settings;

use App\Infrastructure\Persistence\Models\EmailAccount;
use Illuminate\Support\Facades\Crypt;
use Livewire\Component;

class EmailAccountsPage extends Component
{
    // Form state
    public bool   $showForm    = false;
    public ?int   $editingId   = null;

    public string $name              = '';
    public string $email_address     = '';
    public bool   $is_shared         = false;
    public string $imap_host         = '';
    public int    $imap_port         = 993;
    public string $imap_encryption   = 'ssl';
    public string $smtp_host         = '';
    public int    $smtp_port         = 587;
    public string $smtp_encryption   = 'tls';
    public string $username          = '';
    public string $password          = '';
    public string $email_signature_html = '';
    public bool   $is_default        = false;

    public ?string $testResult = null;
    public bool    $testPassed = false;

    protected function rules(): array
    {
        return [
            'name'            => 'required|string|max:100',
            'email_address'   => 'required|email|max:255',
            'imap_host'       => 'required|string|max:255',
            'imap_port'       => 'required|integer|min:1|max:65535',
            'imap_encryption' => 'required|in:ssl,tls,none',
            'smtp_host'       => 'nullable|string|max:255',
            'smtp_port'       => 'nullable|integer|min:1|max:65535',
            'smtp_encryption' => 'required|in:tls,ssl,none',
            'username'        => 'required|string|max:255',
            'password'        => $this->editingId ? 'nullable|string' : 'required|string',
        ];
    }

    public function openCreate(): void
    {
        $this->reset([
            'editingId', 'name', 'email_address', 'is_shared',
            'imap_host', 'imap_port', 'imap_encryption',
            'smtp_host', 'smtp_port', 'smtp_encryption',
            'username', 'password', 'email_signature_html',
            'is_default', 'testResult', 'testPassed',
        ]);
        $this->imap_port       = 993;
        $this->smtp_port       = 587;
        $this->imap_encryption = 'ssl';
        $this->smtp_encryption = 'tls';
        $this->showForm        = true;
    }

    public function openEdit(int $id): void
    {
        $account = $this->resolveOwned($id);
        $this->editingId           = $id;
        $this->name                = $account->name;
        $this->email_address       = $account->email_address;
        $this->is_shared           = $account->is_shared;
        $this->imap_host           = $account->imap_host ?? '';
        $this->imap_port           = $account->imap_port;
        $this->imap_encryption     = $account->imap_encryption;
        $this->smtp_host           = $account->smtp_host ?? '';
        $this->smtp_port           = $account->smtp_port;
        $this->smtp_encryption     = $account->smtp_encryption;
        $this->username            = $account->username ?? '';
        $this->password            = '';
        $this->email_signature_html = $account->email_signature_html ?? '';
        $this->is_default          = $account->is_default;
        $this->testResult          = null;
        $this->showForm            = true;
    }

    public function save(): void
    {
        $this->validate();

        $user = auth()->user();

        $isAdmin = $user->hasPermissionTo('agency.manage');

        $data = [
            'agency_id'           => $user->agency_id,
            'user_id'             => ($isAdmin && $this->is_shared) ? null : $user->id,
            'name'                => $this->name,
            'email_address'       => $this->email_address,
            'is_shared'           => $isAdmin && $this->is_shared,
            'imap_host'           => $this->imap_host,
            'imap_port'           => $this->imap_port,
            'imap_encryption'     => $this->imap_encryption,
            'smtp_host'           => $this->smtp_host ?: null,
            'smtp_port'           => $this->smtp_port,
            'smtp_encryption'     => $this->smtp_encryption,
            'username'            => $this->username,
            'email_signature_html'=> $this->email_signature_html ?: null,
            'is_default'          => $this->is_default,
        ];

        if ($this->password) {
            $data['password'] = $this->password;
        }

        if ($this->is_default) {
            // Un-default other accounts for this user
            EmailAccount::where('agency_id', $user->agency_id)
                ->when(! $this->is_shared, fn ($q) => $q->where('user_id', $user->id))
                ->update(['is_default' => false]);
        }

        if ($this->editingId) {
            $this->resolveOwned($this->editingId)->update($data);
            $this->dispatch('notify', message: 'Account updated.', type: 'success');
        } else {
            EmailAccount::create($data);
            $this->dispatch('notify', message: 'Email account connected.', type: 'success');
        }

        $this->showForm = false;
    }

    public function testConnection(): void
    {
        $this->testResult = null;
        $this->testPassed = false;

        if (! extension_loaded('imap')) {
            $this->testResult = 'php-imap extension is not enabled on this server.';
            return;
        }

        if (! $this->imap_host || ! $this->username || ! $this->password) {
            $this->testResult = 'Fill in IMAP host, username, and password first.';
            return;
        }

        $enc        = match ($this->imap_encryption) {
            'ssl'  => '/ssl',
            'none' => '/notls',
            default => '',
        };
        $mailbox    = "{{$this->imap_host}:{$this->imap_port}/imap{$enc}}INBOX";
        $connection = @imap_open($mailbox, $this->username, $this->password, 0, 1);

        if ($connection) {
            imap_close($connection);
            $this->testResult = 'Connection successful!';
            $this->testPassed = true;
        } else {
            $this->testResult = 'Connection failed: ' . (imap_last_error() ?: 'Unknown error');
        }
    }

    public function syncNow(int $id): void
    {
        $this->resolveOwned($id);
        \App\Infrastructure\Queue\Jobs\SyncEmailAccountJob::dispatch($id);
        $this->dispatch('notify', message: 'Sync queued.', type: 'info');
    }

    public function toggleActive(int $id): void
    {
        $account = $this->resolveOwned($id);
        $account->update(['is_active' => ! $account->is_active]);
    }

    public function delete(int $id): void
    {
        $this->resolveOwned($id)->delete();
        $this->dispatch('notify', message: 'Account removed.', type: 'info');
    }

    private function resolveOwned(int $id): EmailAccount
    {
        $user    = auth()->user();
        $isAdmin = $user->hasPermissionTo('agency.manage');

        return EmailAccount::where('agency_id', $user->agency_id)
            ->when(! $isAdmin, fn ($q) => $q->where('user_id', $user->id))
            ->findOrFail($id);
    }

    public function render()
    {
        $user     = auth()->user();
        $isAdmin  = $user->hasPermissionTo('agency.manage');

        $accounts = EmailAccount::where('agency_id', $user->agency_id)
            ->when(! $isAdmin, fn ($q) => $q->where(fn ($q2) =>
                $q2->where('user_id', $user->id)->orWhere('is_shared', true)
            ))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('livewire.settings.email-accounts-page', compact('accounts'))
            ->layout('layouts.app');
    }
}
