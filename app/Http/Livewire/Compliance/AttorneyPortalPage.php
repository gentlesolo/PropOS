<?php

namespace App\Http\Livewire\Compliance;

use App\Infrastructure\Persistence\Models\Transaction;
use App\Infrastructure\Persistence\Models\User;
use Livewire\Component;

class AttorneyPortalPage extends Component
{
    public string $search = '';
    public ?int $assigningTransactionId = null;
    public string $attorney_name = '';
    public string $attorney_email = '';
    public string $attorney_firm = '';
    public string $attorney_phone = '';

    public function assignAttorney(int $transactionId)
    {
        $this->validate([
            'attorney_name' => 'required|string|max:255',
            'attorney_email' => 'required|email|max:255',
            'attorney_firm' => 'nullable|string|max:255',
            'attorney_phone' => 'nullable|string|max:50',
        ]);

        // Find or create attorney user record
        $attorney = User::firstOrCreate(
            ['email' => $this->attorney_email],
            [
                'agency_id' => auth()->user()->agency_id,
                'first_name' => explode(' ', $this->attorney_name)[0],
                'last_name' => implode(' ', array_slice(explode(' ', $this->attorney_name), 1)) ?: 'Attorney',
                'phone' => $this->attorney_phone ?: null,
                'job_title' => $this->attorney_firm ? "Attorney — {$this->attorney_firm}" : 'Conveyancing Attorney',
                'status' => 'invited',
                'password' => bcrypt(\Str::random(16)),
            ]
        );

        Transaction::find($transactionId)?->update(['attorney_id' => $attorney->id]);

        $this->reset(['assigningTransactionId', 'attorney_name', 'attorney_email', 'attorney_firm', 'attorney_phone']);
        $this->dispatch('notify', message: 'Attorney assigned to transaction.', type: 'success');
    }

    public function removeAttorney(int $transactionId)
    {
        Transaction::find($transactionId)?->update(['attorney_id' => null]);
        $this->dispatch('notify', message: 'Attorney removed.', type: 'success');
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $transactions = Transaction::with(['deal', 'contact', 'listing.property', 'attorney', 'documents'])
            ->where('agency_id', $agencyId)
            ->whereNotIn('status', ['cancelled'])
            ->when($this->search, fn($q) => $q->whereHas('deal', fn($d) => $d->where('title', 'like', "%{$this->search}%")))
            ->latest()
            ->get();

        $stats = [
            'with_attorney' => $transactions->whereNotNull('attorney_id')->count(),
            'without_attorney' => $transactions->whereNull('attorney_id')->count(),
            'in_conveyancing' => $transactions->whereIn('status', ['conveyancing', 'registration'])->count(),
        ];

        return view('livewire.compliance.attorney-portal-page', compact('transactions', 'stats'))
            ->layout('layouts.app');
    }
}
