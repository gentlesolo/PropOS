<?php

namespace App\Http\Livewire\Compliance;

use App\Infrastructure\Persistence\Models\ComplianceDocument;
use App\Infrastructure\Persistence\Models\Transaction;
use Livewire\Component;
use Livewire\WithFileUploads;

class TransactionDetailPage extends Component
{
    use WithFileUploads;

    public Transaction $transaction;

    // Document upload
    public string $doc_type = 'proof_of_identity';
    public string $doc_title = '';
    public bool $doc_is_fica = true;
    public $doc_file;
    public bool $showDocForm = false;

    // Status update
    public string $newStatus = '';

    // Deadline edit
    public bool $showDeadlineForm = false;
    public string $deadline = '';
    public string $estimated_close_date = '';

    // Notes
    public string $notes = '';

    public function mount(Transaction $transaction)
    {
        $this->transaction = $transaction->load('documents.uploadedBy', 'deal', 'contact', 'agent', 'commission', 'listing.property');
        $this->newStatus = $transaction->status;
        $this->deadline = $transaction->deadline?->format('Y-m-d') ?? '';
        $this->estimated_close_date = $transaction->estimated_close_date?->format('Y-m-d') ?? '';
        $this->notes = $transaction->notes ?? '';
    }

    public function uploadDocument()
    {
        $this->validate([
            'doc_title' => 'required|string|max:255',
            'doc_type' => 'required|string',
            'doc_file' => 'required|file|max:10240',
        ]);

        $path = $this->doc_file->store("transactions/{$this->transaction->id}/documents", 'public');

        ComplianceDocument::create([
            'transaction_id' => $this->transaction->id,
            'agency_id' => $this->transaction->agency_id,
            'uploaded_by' => auth()->id(),
            'document_type' => $this->doc_type,
            'title' => $this->doc_title,
            'file_path' => $path,
            'file_name' => $this->doc_file->getClientOriginalName(),
            'mime_type' => $this->doc_file->getMimeType(),
            'file_size' => $this->doc_file->getSize(),
            'status' => 'uploaded',
            'is_fica_required' => $this->doc_is_fica,
        ]);

        $this->reset(['doc_title', 'doc_file', 'showDocForm']);
        $this->doc_type = 'proof_of_identity';
        $this->doc_is_fica = true;
        $this->transaction->refresh()->load('documents.uploadedBy');
    }

    public function approveDocument(int $docId)
    {
        ComplianceDocument::where('id', $docId)
            ->where('transaction_id', $this->transaction->id)
            ->update(['status' => 'approved', 'reviewed_at' => now()]);
        $this->transaction->refresh()->load('documents');
    }

    public function rejectDocument(int $docId)
    {
        ComplianceDocument::where('id', $docId)
            ->where('transaction_id', $this->transaction->id)
            ->update(['status' => 'rejected', 'reviewed_at' => now()]);
        $this->transaction->refresh()->load('documents');
    }

    public function updateStatus()
    {
        $this->validate(['newStatus' => 'required|in:initiated,fica_pending,fica_verified,offer_accepted,conveyancing,registration,completed,cancelled']);

        $timeline = $this->transaction->timeline ?? [];
        $timeline[] = ['status' => $this->newStatus, 'at' => now()->toISOString(), 'by' => auth()->user()->first_name];

        $this->transaction->update([
            'status' => $this->newStatus,
            'timeline' => $timeline,
            'closed_at' => $this->newStatus === 'completed' ? now()->toDateString() : $this->transaction->closed_at,
        ]);

        $this->transaction->refresh();
        $this->dispatch('notify', message: 'Transaction status updated.', type: 'success');
    }

    public function saveDeadlines()
    {
        $this->validate([
            'deadline' => 'nullable|date',
            'estimated_close_date' => 'nullable|date',
        ]);

        $this->transaction->update([
            'deadline' => $this->deadline ?: null,
            'estimated_close_date' => $this->estimated_close_date ?: null,
            'notes' => $this->notes ?: null,
        ]);

        $this->showDeadlineForm = false;
        $this->transaction->refresh();
        $this->dispatch('notify', message: 'Deadlines saved.', type: 'success');
    }

    public function render()
    {
        return view('livewire.compliance.transaction-detail-page')
            ->layout('layouts.app');
    }
}
