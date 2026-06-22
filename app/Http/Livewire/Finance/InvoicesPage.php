<?php

namespace App\Http\Livewire\Finance;

use App\Application\Finance\Actions\GenerateRentInvoiceAction;
use App\Application\Finance\Actions\MarkInvoicePaidAction;
use App\Application\Finance\Actions\SendInvoiceAction;
use App\Infrastructure\Payment\Contracts\PaymentGatewayInterface;
use App\Infrastructure\Persistence\Models\Invoice;
use App\Infrastructure\Persistence\Models\InvoiceLineItem;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\Tenant;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class InvoicesPage extends Component
{
    use WithPagination;

    // ── Filters ───────────────────────────────────────────────────────────────
    public string $search       = '';
    public string $statusFilter = '';
    public string $typeFilter   = '';
    public string $periodMonth;
    public string $periodYear;

    protected $queryString = ['statusFilter', 'typeFilter', 'search'];

    // ── Detail panel ──────────────────────────────────────────────────────────
    public bool  $showDetail      = false;
    public ?int  $detailInvoiceId = null;

    // ── Create form ───────────────────────────────────────────────────────────
    public bool   $showCreateForm  = false;
    public string $form_lease_id   = '';
    public string $form_type       = 'maintenance';
    public string $form_due_date   = '';
    public string $form_notes      = '';
    public array  $form_line_items = [
        ['description' => '', 'category' => 'maintenance', 'quantity' => '1', 'unit_price' => ''],
    ];

    // ── Edit (draft only) ─────────────────────────────────────────────────────
    public bool   $showEditForm     = false;
    public ?int   $editInvoiceId    = null;
    public string $edit_due_date    = '';
    public string $edit_notes       = '';
    public array  $edit_line_items  = [];

    // ── Payment modal ─────────────────────────────────────────────────────────
    public bool   $showPaymentModal  = false;
    public ?int   $selectedInvoiceId = null;
    public string $paymentAmount     = '';
    public string $paymentMethod     = 'eft';
    public string $paymentReference  = '';
    public string $paymentDate       = '';

    // ── Payment link ──────────────────────────────────────────────────────────
    public ?string $paymentLinkUrl = null;

    // ── Note modal ────────────────────────────────────────────────────────────
    public bool   $showNoteModal  = false;
    public ?int   $noteInvoiceId  = null;
    public string $noteText       = '';

    public function mount(): void
    {
        $this->periodMonth  = now()->format('m');
        $this->periodYear   = now()->format('Y');
        $this->form_due_date = now()->addDays(5)->toDateString();
        $this->paymentDate  = now()->toDateString();
    }

    // ── Pagination reset ──────────────────────────────────────────────────────
    public function updatingSearch(): void    { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingTypeFilter(): void   { $this->resetPage(); }

    // ── Detail panel ──────────────────────────────────────────────────────────
    public function openDetail(int $id): void
    {
        $this->detailInvoiceId = $id;
        $this->showDetail      = true;
        $this->showCreateForm  = false;
        $this->showEditForm    = false;
    }

    public function closeDetail(): void
    {
        $this->showDetail      = false;
        $this->detailInvoiceId = null;
    }

    // ── Create ────────────────────────────────────────────────────────────────
    public function openCreateForm(): void
    {
        $this->reset(['form_lease_id', 'form_notes', 'form_line_items']);
        $this->form_type       = 'maintenance';
        $this->form_due_date   = now()->addDays(5)->toDateString();
        $this->form_line_items = [
            ['description' => '', 'category' => 'maintenance', 'quantity' => '1', 'unit_price' => ''],
        ];
        $this->showCreateForm = true;
        $this->showDetail     = false;
        $this->showEditForm   = false;
    }

    public function addLineItem(): void
    {
        $this->form_line_items[] = ['description' => '', 'category' => 'other', 'quantity' => '1', 'unit_price' => ''];
    }

    public function removeLineItem(int $index): void
    {
        if (count($this->form_line_items) > 1) {
            array_splice($this->form_line_items, $index, 1);
            $this->form_line_items = array_values($this->form_line_items);
        }
    }

    public function createInvoice(): void
    {
        $agencyId = auth()->user()->agency_id;
        $this->validate([
            'form_lease_id'              => ['required', Rule::exists('leases', 'id')->where('agency_id', $agencyId)],
            'form_type'                  => 'required|string',
            'form_due_date'              => 'required|date',
            'form_line_items'            => 'required|array|min:1',
            'form_line_items.*.description' => 'required|string|max:255',
            'form_line_items.*.quantity'    => 'required|numeric|min:0.01',
            'form_line_items.*.unit_price'  => 'required|numeric|min:0.01',
        ]);

        $agencyId = auth()->user()->agency_id;
        $lease    = Lease::where('id', $this->form_lease_id)
            ->where('agency_id', $agencyId)
            ->firstOrFail();

        $dueDate  = \Carbon\Carbon::parse($this->form_due_date);
        $subtotal = collect($this->form_line_items)->sum(fn ($i) => (float)$i['quantity'] * (float)$i['unit_price']);

        $invoice = Invoice::create([
            'agency_id'    => $agencyId,
            'lease_id'     => $lease->id,
            'tenant_id'    => $lease->tenant_id,
            'type'         => $this->form_type,
            'status'       => 'draft',
            'subtotal'     => $subtotal,
            'tax_amount'   => 0,
            'total'        => $subtotal,
            'amount_paid'  => 0,
            'due_date'     => $dueDate,
            'period_month' => (int) $dueDate->format('m'),
            'period_year'  => (int) $dueDate->format('Y'),
            'notes'        => $this->form_notes ?: null,
        ]);

        foreach ($this->form_line_items as $item) {
            $amount = (float)$item['quantity'] * (float)$item['unit_price'];
            InvoiceLineItem::create([
                'invoice_id'  => $invoice->id,
                'description' => $item['description'],
                'category'    => $item['category'],
                'quantity'    => (float) $item['quantity'],
                'unit_price'  => (float) $item['unit_price'],
                'amount'      => $amount,
                'is_taxable'  => false,
            ]);
        }

        $this->reset(['showCreateForm', 'form_lease_id', 'form_type', 'form_due_date', 'form_notes', 'form_line_items']);
        $this->form_line_items = [['description' => '', 'category' => 'maintenance', 'quantity' => '1', 'unit_price' => '']];
        $this->dispatch('notify', message: 'Invoice created as draft.', type: 'success');
    }

    // ── Edit draft ────────────────────────────────────────────────────────────
    public function openEditForm(int $id): void
    {
        $invoice = $this->scopedInvoice($id);

        if ($invoice->status !== 'draft') {
            $this->dispatch('notify', message: 'Only draft invoices can be edited.', type: 'error');
            return;
        }

        $this->editInvoiceId   = $invoice->id;
        $this->edit_due_date   = $invoice->due_date->toDateString();
        $this->edit_notes      = $invoice->notes ?? '';
        $this->edit_line_items = $invoice->lineItems->map(fn ($li) => [
            'id'          => $li->id,
            'description' => $li->description,
            'category'    => $li->category,
            'quantity'    => (string) $li->quantity,
            'unit_price'  => (string) $li->unit_price,
        ])->toArray();

        $this->showEditForm   = true;
        $this->showCreateForm = false;
        $this->showDetail     = false;
    }

    public function addEditLineItem(): void
    {
        $this->edit_line_items[] = ['id' => null, 'description' => '', 'category' => 'other', 'quantity' => '1', 'unit_price' => ''];
    }

    public function removeEditLineItem(int $index): void
    {
        if (count($this->edit_line_items) > 1) {
            array_splice($this->edit_line_items, $index, 1);
            $this->edit_line_items = array_values($this->edit_line_items);
        }
    }

    public function saveEdit(): void
    {
        $this->validate([
            'edit_due_date'                  => 'required|date',
            'edit_line_items'                => 'required|array|min:1',
            'edit_line_items.*.description'  => 'required|string|max:255',
            'edit_line_items.*.quantity'     => 'required|numeric|min:0.01',
            'edit_line_items.*.unit_price'   => 'required|numeric|min:0.01',
        ]);

        $invoice = $this->scopedInvoice($this->editInvoiceId);

        if ($invoice->status !== 'draft') {
            $this->dispatch('notify', message: 'Only draft invoices can be edited.', type: 'error');
            return;
        }

        // Rebuild line items: delete old ones, re-insert
        $invoice->lineItems()->delete();

        $subtotal = 0;
        foreach ($this->edit_line_items as $item) {
            $amount    = (float)$item['quantity'] * (float)$item['unit_price'];
            $subtotal += $amount;
            InvoiceLineItem::create([
                'invoice_id'  => $invoice->id,
                'description' => $item['description'],
                'category'    => $item['category'],
                'quantity'    => (float) $item['quantity'],
                'unit_price'  => (float) $item['unit_price'],
                'amount'      => $amount,
                'is_taxable'  => false,
            ]);
        }

        $invoice->update([
            'due_date'  => $this->edit_due_date,
            'notes'     => $this->edit_notes ?: null,
            'subtotal'  => $subtotal,
            'total'     => $subtotal,
        ]);

        $this->reset(['showEditForm', 'editInvoiceId', 'edit_due_date', 'edit_notes', 'edit_line_items']);
        $this->dispatch('notify', message: 'Invoice updated.', type: 'success');
    }

    // ── Record payment ────────────────────────────────────────────────────────
    public function openPaymentModal(int $id): void
    {
        $invoice = $this->scopedInvoice($id);
        $this->selectedInvoiceId = $invoice->id;
        $this->paymentAmount     = number_format($invoice->balance, 2, '.', '');
        $this->paymentDate       = now()->toDateString();
        $this->showPaymentModal  = true;
    }

    public function recordPayment(MarkInvoicePaidAction $action): void
    {
        $agencyId = auth()->user()->agency_id;
        $this->validate([
            'paymentAmount'    => 'required|numeric|min:0.01',
            'paymentMethod'    => 'required|string',
            'paymentDate'      => 'required|date',
            'selectedInvoiceId'=> ['required', Rule::exists('invoices', 'id')->where('agency_id', $agencyId)],
        ]);

        $invoice = $this->scopedInvoice($this->selectedInvoiceId);
        $action->execute($invoice, (float) $this->paymentAmount, $this->paymentMethod, $this->paymentReference ?: null);

        $this->reset(['showPaymentModal', 'selectedInvoiceId', 'paymentAmount', 'paymentMethod', 'paymentReference']);
        $this->dispatch('notify', message: 'Payment recorded.', type: 'success');
    }

    // ── Send ──────────────────────────────────────────────────────────────────
    public function sendInvoice(int $id, SendInvoiceAction $action): void
    {
        $invoice = $this->scopedInvoice($id);
        $action->execute($invoice);
        $this->dispatch('notify', message: 'Invoice sent to tenant.', type: 'success');
    }

    // ── Payment link ──────────────────────────────────────────────────────────
    public function generatePaymentLink(int $id, PaymentGatewayInterface $gateway): void
    {
        $invoice = $this->scopedInvoice($id);
        $result  = $gateway->createPaymentLink($invoice);
        $invoice->update([
            'payment_gateway'    => 'payfast',
            'gateway_payment_id' => $result['payment_id'],
            'gateway_payment_url'=> $result['url'],
        ]);
        $this->paymentLinkUrl = $result['url'];
        $this->dispatch('notify', message: 'Payment link generated.', type: 'success');
    }

    // ── Note ──────────────────────────────────────────────────────────────────
    public function openNoteModal(int $id): void
    {
        $invoice = $this->scopedInvoice($id);
        $this->noteInvoiceId = $invoice->id;
        $this->noteText      = $invoice->notes ?? '';
        $this->showNoteModal = true;
    }

    public function saveNote(): void
    {
        $this->validate(['noteText' => 'nullable|string|max:1000']);

        $this->scopedInvoice($this->noteInvoiceId)->update(['notes' => $this->noteText ?: null]);

        $this->reset(['showNoteModal', 'noteInvoiceId', 'noteText']);
        $this->dispatch('notify', message: 'Note saved.', type: 'success');
    }

    // ── Bulk generate rent invoices ───────────────────────────────────────────
    public function generateInvoices(GenerateRentInvoiceAction $generate, SendInvoiceAction $send): void
    {
        $month    = (int) $this->periodMonth;
        $year     = (int) $this->periodYear;
        $agencyId = auth()->user()->agency_id;
        $count    = 0;

        Lease::where('agency_id', $agencyId)
            ->whereIn('status', ['active', 'expiring_soon'])
            ->each(function (Lease $lease) use ($month, $year, $generate, $send, &$count) {
                $invoice = $generate->execute($lease, $month, $year);
                if ($invoice && $invoice->wasRecentlyCreated) {
                    $send->execute($invoice);
                    $count++;
                }
            });

        $this->dispatch('notify', message: "{$count} invoice(s) generated and sent.", type: 'success');
    }

    // ── Void ──────────────────────────────────────────────────────────────────
    public function voidInvoice(int $id): void
    {
        $this->scopedInvoice($id)->update(['status' => 'void']);
        $this->dispatch('notify', message: 'Invoice voided.', type: 'info');
    }

    // ── Delete draft ──────────────────────────────────────────────────────────
    public function deleteInvoice(int $id): void
    {
        $invoice = $this->scopedInvoice($id);

        if ($invoice->status !== 'draft') {
            $this->dispatch('notify', message: 'Only draft invoices can be deleted.', type: 'error');
            return;
        }

        $invoice->lineItems()->delete();
        $invoice->delete();

        if ($this->detailInvoiceId === $id) {
            $this->showDetail = false;
        }

        $this->dispatch('notify', message: 'Draft invoice deleted.', type: 'info');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function scopedInvoice(int $id): Invoice
    {
        return Invoice::where('id', $id)
            ->where('agency_id', auth()->user()->agency_id)
            ->with('lineItems')
            ->firstOrFail();
    }

    // ── Render ────────────────────────────────────────────────────────────────
    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $invoices = Invoice::with(['lease.tenant.contact', 'lease.listing.property', 'lineItems'])
            ->where('agency_id', $agencyId)
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('reference', 'like', "%{$this->search}%")
                       ->orWhereHas('lease.tenant.contact', fn ($q3) =>
                           $q3->where('first_name', 'like', "%{$this->search}%")
                              ->orWhere('last_name', 'like', "%{$this->search}%")
                       );
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter,   fn ($q) => $q->where('type', $this->typeFilter))
            ->orderByDesc('due_date')
            ->paginate(20);

        $month = (int) $this->periodMonth;
        $year  = (int) $this->periodYear;

        $totalInvoiced  = Invoice::where('agency_id', $agencyId)->where('period_month', $month)->where('period_year', $year)->sum('total');
        $totalCollected = Invoice::where('agency_id', $agencyId)->where('period_month', $month)->where('period_year', $year)->whereIn('status', ['paid', 'partially_paid'])->sum('amount_paid');
        $outstandingAr  = Invoice::where('agency_id', $agencyId)->whereNotIn('status', ['paid', 'void'])->selectRaw('SUM(total) - SUM(amount_paid) as bal')->value('bal') ?? 0;
        $overdueCount   = Invoice::where('agency_id', $agencyId)->where('status', 'overdue')->count();

        $stats = compact('totalInvoiced', 'totalCollected', 'outstandingAr', 'overdueCount');

        // Detail panel data
        $detailInvoice = null;
        if ($this->showDetail && $this->detailInvoiceId) {
            $detailInvoice = Invoice::with([
                'lease.tenant.contact',
                'lease.listing.property',
                'lineItems',
            ])->where('agency_id', $agencyId)->find($this->detailInvoiceId);
        }

        // Leases for create form
        $leases = Lease::with(['tenant.contact', 'listing.property'])
            ->where('agency_id', $agencyId)
            ->whereIn('status', ['active', 'expiring_soon'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.finance.invoices-page', compact('invoices', 'stats', 'detailInvoice', 'leases'))
            ->layout('layouts.app');
    }
}
