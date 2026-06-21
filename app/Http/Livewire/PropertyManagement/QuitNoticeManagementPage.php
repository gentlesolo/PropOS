<?php

namespace App\Http\Livewire\PropertyManagement;

use App\Application\AI\Actions\GenerateQuitNoticeContentAction;
use App\Application\PropertyManagement\Actions\CreateQuitNoticeAction;
use App\Application\PropertyManagement\Actions\SendQuitNoticeAction;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\QuitNotice;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuitNoticeManagementPage extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $statusFilter = '';

    // Create / draft form
    public bool   $showCreateForm  = false;
    public bool   $generatingAi    = false;
    public string $lease_id        = '';
    public string $vacate_by_date  = '';
    public string $reason          = '';
    public string $notice_body     = '';
    public string $delivery_method = 'email';
    public string $internal_notes  = '';

    // Detail / view
    public ?int  $selectedNoticeId = null;
    public string $detailTab       = 'notice';

    // Tenant response capture
    public bool   $showResponseForm    = false;
    public string $response_notice_id  = '';
    public string $tenant_response     = '';
    public string $response_status     = 'acknowledged';

    protected $queryString = ['search', 'statusFilter'];

    public function updatingSearch(): void { $this->resetPage(); }

    // ── AI Draft ──────────────────────────────────────────────────────────────

    public function generateAiDraft(GenerateQuitNoticeContentAction $action): void
    {
        $this->validate([
            'lease_id'       => 'required|exists:leases,id',
            'vacate_by_date' => 'required|date|after:today',
            'reason'         => 'required|string|min:5',
        ]);

        $lease = Lease::with('tenant.contact', 'listing.property', 'agency')->findOrFail($this->lease_id);

        $this->generatingAi = true;

        $draft = $action->execute($lease, $this->reason, $this->vacate_by_date);

        $this->notice_body  = $draft;
        $this->generatingAi = false;

        $this->dispatch('notify', message: 'AI draft generated. Review and edit before saving.', type: 'info');
    }

    // ── Create Notice ─────────────────────────────────────────────────────────

    public function createNotice(CreateQuitNoticeAction $action): void
    {
        $this->validate([
            'lease_id'       => 'required|exists:leases,id',
            'vacate_by_date' => 'required|date|after:today',
            'reason'         => 'required|string|min:5',
            'notice_body'    => 'required|string|min:20',
            'delivery_method'=> 'required|in:email,hand_delivered,registered_post,email_and_post',
        ]);

        $notice = $action->execute([
            'lease_id'        => $this->lease_id,
            'vacate_by_date'  => $this->vacate_by_date,
            'reason'          => $this->reason,
            'notice_body'     => $this->notice_body,
            'delivery_method' => $this->delivery_method,
            'internal_notes'  => $this->internal_notes ?: null,
        ]);

        $this->resetCreateForm();
        $this->selectedNoticeId = $notice->id;
        $this->dispatch('notify', message: 'Quit notice drafted and saved.', type: 'success');
    }

    // ── Send Notice ───────────────────────────────────────────────────────────

    public function sendNotice(int $id, SendQuitNoticeAction $action): void
    {
        $notice = QuitNotice::with('lease.tenant.contact', 'lease.listing.property', 'issuedBy', 'agency')
            ->where('agency_id', auth()->user()->agency_id)
            ->findOrFail($id);

        if (!in_array($notice->status, ['drafted', 'disputed'])) {
            $this->dispatch('notify', message: 'This notice has already been sent.', type: 'warning');
            return;
        }

        $action->execute($notice);
        $this->dispatch('notify', message: 'Quit notice sent successfully.', type: 'success');
    }

    // ── Withdraw Notice ───────────────────────────────────────────────────────

    public function withdrawNotice(int $id): void
    {
        QuitNotice::where('agency_id', auth()->user()->agency_id)
            ->findOrFail($id)
            ->update(['status' => 'withdrawn']);

        $this->dispatch('notify', message: 'Quit notice withdrawn.', type: 'warning');
    }

    // ── Mark Completed ────────────────────────────────────────────────────────

    public function markCompleted(int $id): void
    {
        QuitNotice::where('agency_id', auth()->user()->agency_id)
            ->findOrFail($id)
            ->update(['status' => 'completed']);

        $this->dispatch('notify', message: 'Quit notice marked as completed.', type: 'success');
    }

    // ── Tenant Response ───────────────────────────────────────────────────────

    public function openResponseForm(int $id): void
    {
        $this->response_notice_id = (string) $id;
        $this->tenant_response    = '';
        $this->response_status    = 'acknowledged';
        $this->showResponseForm   = true;
    }

    public function recordTenantResponse(): void
    {
        $agencyId = auth()->user()->agency_id;

        $this->validate([
            'response_notice_id' => ['required', \Illuminate\Validation\Rule::exists('quit_notices', 'id')->where('agency_id', $agencyId)],
            'tenant_response'    => 'required|string|min:3',
            'response_status'    => 'required|in:acknowledged,disputed',
        ]);

        QuitNotice::where('agency_id', $agencyId)
            ->findOrFail($this->response_notice_id)
            ->update([
                'status'           => $this->response_status,
                'tenant_response'  => $this->tenant_response,
                'acknowledged_at'  => now(),
            ]);

        $this->reset(['showResponseForm', 'response_notice_id', 'tenant_response', 'response_status']);
        $this->dispatch('notify', message: 'Tenant response recorded.', type: 'info');
    }

    // ── Download PDF ──────────────────────────────────────────────────────────

    public function downloadPdf(int $id): StreamedResponse
    {
        $notice = QuitNotice::with('lease.tenant.contact', 'lease.listing.property', 'issuedBy', 'agency')
            ->where('agency_id', auth()->user()->agency_id)
            ->findOrFail($id);

        $pdf      = Pdf::loadView('pdfs.quit-notice', ['quitNotice' => $notice])->setPaper('a4', 'portrait');
        $content  = $pdf->output();
        $filename = "quit-notice-{$notice->reference}.pdf";

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    // ── UI helpers ────────────────────────────────────────────────────────────

    public function selectNotice(int $id): void
    {
        $this->selectedNoticeId = $id;
        $this->detailTab        = 'notice';
    }

    public function closeDetail(): void
    {
        $this->selectedNoticeId = null;
    }

    private function resetCreateForm(): void
    {
        $this->reset([
            'showCreateForm', 'generatingAi', 'lease_id', 'vacate_by_date',
            'reason', 'notice_body', 'delivery_method', 'internal_notes',
        ]);
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $notices = QuitNotice::with('lease.tenant.contact', 'lease.listing.property', 'issuedBy')
            ->where('agency_id', $agencyId)
            ->when($this->search, fn ($q) => $q->whereHas('lease.tenant.contact', fn ($sq) =>
                $sq->where('first_name', 'like', "%{$this->search}%")
                   ->orWhere('last_name', 'like', "%{$this->search}%"))
                ->orWhere('reference', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('created_at')
            ->paginate(15);

        $selectedNotice = $this->selectedNoticeId
            ? QuitNotice::with('lease.tenant.contact', 'lease.listing.property', 'issuedBy', 'agency')
                ->find($this->selectedNoticeId)
            : null;

        $leases = Lease::with('tenant.contact', 'listing.property')
            ->where('agency_id', $agencyId)
            ->whereIn('status', ['active', 'expiring_soon'])
            ->orderByDesc('start_date')
            ->get();

        $stats = [
            'total'        => QuitNotice::where('agency_id', $agencyId)->count(),
            'sent'         => QuitNotice::where('agency_id', $agencyId)->where('status', 'sent')->count(),
            'acknowledged' => QuitNotice::where('agency_id', $agencyId)->where('status', 'acknowledged')->count(),
            'disputed'     => QuitNotice::where('agency_id', $agencyId)->where('status', 'disputed')->count(),
        ];

        return view('livewire.property-management.quit-notice-management-page', compact(
            'notices', 'selectedNotice', 'leases', 'stats'
        ))->layout('layouts.app');
    }
}
