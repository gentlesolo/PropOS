<?php

namespace App\Http\Livewire\Governance;

use App\Infrastructure\Persistence\Models\ComplianceDocument;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Property;
use App\Infrastructure\Persistence\Models\Transaction;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class DocumentRepositoryPage extends Component
{
    use WithPagination, WithFileUploads;

    public string $search       = '';
    public string $categoryFilter = '';
    public string $statusFilter = '';
    public string $expiryFilter = '';
    public bool   $showUploadForm = false;

    // Upload form fields
    public string $doc_title        = '';
    public string $doc_type         = 'general';
    public string $doc_category     = 'other';
    public bool   $doc_is_fica      = false;
    public string $doc_expiry_date  = '';
    public string $doc_notes        = '';
    public string $linked_type      = '';
    public string $linked_id        = '';
    public $doc_file;

    protected $queryString = ['search', 'categoryFilter', 'statusFilter', 'expiryFilter'];

    public function updatingSearch(): void    { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }

    public function uploadDocument(): void
    {
        $this->validate([
            'doc_title'    => 'required|string|max:255',
            'doc_category' => 'required|in:lease_agreement,compliance_record,inspection_report,contract,identity,financial,other',
            'doc_file'     => 'required|file|max:20480',
            'doc_expiry_date' => 'nullable|date',
        ]);

        $path = $this->doc_file->store('documents/'.now()->format('Y/m'), 'public');

        $data = [
            'agency_id'     => auth()->user()->agency_id,
            'uploaded_by'   => auth()->id(),
            'document_type' => $this->doc_type,
            'category'      => $this->doc_category,
            'title'         => $this->doc_title,
            'file_path'     => $path,
            'file_name'     => $this->doc_file->getClientOriginalName(),
            'mime_type'     => $this->doc_file->getMimeType(),
            'file_size'     => $this->doc_file->getSize(),
            'status'        => 'uploaded',
            'is_fica_required' => $this->doc_is_fica,
            'expiry_date'   => $this->doc_expiry_date ?: null,
            'notes'         => $this->doc_notes ?: null,
        ];

        if ($this->linked_type === 'transaction' && $this->linked_id) {
            $data['transaction_id'] = $this->linked_id;
        } elseif ($this->linked_type === 'lease' && $this->linked_id) {
            $data['lease_id'] = $this->linked_id;
        } elseif ($this->linked_type === 'listing' && $this->linked_id) {
            $data['listing_id'] = $this->linked_id;
        } elseif ($this->linked_type === 'property' && $this->linked_id) {
            $data['property_id'] = $this->linked_id;
        }

        ComplianceDocument::create($data);

        $this->reset(['doc_title', 'doc_type', 'doc_category', 'doc_is_fica', 'doc_expiry_date',
            'doc_notes', 'linked_type', 'linked_id', 'doc_file', 'showUploadForm']);
        $this->dispatch('notify', message: 'Document uploaded successfully.', type: 'success');
    }

    public function deleteDocument(int $id): void
    {
        $doc = ComplianceDocument::where('agency_id', auth()->user()->agency_id)->findOrFail($id);
        if ($doc->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($doc->file_path);
        }
        $doc->delete();
        $this->dispatch('notify', message: 'Document deleted.', type: 'success');
    }

    public function approveDocument(int $id): void
    {
        ComplianceDocument::where('agency_id', auth()->user()->agency_id)
            ->findOrFail($id)
            ->update(['status' => 'approved', 'reviewed_at' => now()]);
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $documents = ComplianceDocument::with(['uploadedBy', 'transaction', 'lease.tenant.contact', 'listing.property', 'property'])
            ->where('agency_id', $agencyId)
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%")
                ->orWhere('file_name', 'like', "%{$this->search}%")
                ->orWhere('document_type', 'like', "%{$this->search}%"))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->expiryFilter === 'expired', fn ($q) => $q->expired())
            ->when($this->expiryFilter === 'expiring_soon', fn ($q) => $q->expiringSoon())
            ->when($this->expiryFilter === 'no_expiry', fn ($q) => $q->whereNull('expiry_date'))
            ->orderByDesc('created_at')
            ->paginate(20);

        $stats = [
            'total'          => ComplianceDocument::where('agency_id', $agencyId)->count(),
            'expired'        => ComplianceDocument::where('agency_id', $agencyId)->expired()->count(),
            'expiring_soon'  => ComplianceDocument::where('agency_id', $agencyId)->expiringSoon()->count(),
            'approved'       => ComplianceDocument::where('agency_id', $agencyId)->where('status', 'approved')->count(),
        ];

        $transactions = Transaction::where('agency_id', $agencyId)->latest()->get(['id', 'reference']);
        $leases       = Lease::where('agency_id', $agencyId)->with('tenant.contact:id,first_name,last_name')->latest()->get(['id', 'tenant_id']);
        $listings     = Listing::with('property:id,address_line_1')->where('agency_id', $agencyId)->latest()->get(['id', 'property_id']);
        $properties   = Property::where('agency_id', $agencyId)->get(['id', 'address_line_1', 'city']);

        return view('livewire.governance.document-repository-page', compact(
            'documents', 'stats', 'transactions', 'leases', 'listings', 'properties'
        ))->layout('layouts.app');
    }
}
