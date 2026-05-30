<?php

namespace App\Http\Livewire\Crm;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Listing;
use Livewire\Component;
use Livewire\WithFileUploads;

class BulkImportPage extends Component
{
    use WithFileUploads;

    public string $importType = 'contacts';
    public $csvFile = null;
    public array $preview = [];
    public array $importResults = [];
    public bool $importing = false;
    public string $exportType = 'contacts';

    public function updatedCsvFile(): void
    {
        $this->preview = [];
        $this->importResults = [];

        if (!$this->csvFile) return;

        $path = $this->csvFile->getRealPath();
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);
        $rows = [];
        $count = 0;
        while (($row = fgetcsv($handle)) !== false && $count < 5) {
            $rows[] = array_combine($headers, $row);
            $count++;
        }
        fclose($handle);
        $this->preview = ['headers' => $headers, 'rows' => $rows];
    }

    public function import(): void
    {
        $this->validate(['csvFile' => 'required|file|mimes:csv,txt|max:5120']);

        $path = $this->csvFile->getRealPath();
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);
        $agencyId = auth()->user()->agency_id;
        $imported = 0;
        $failed = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            try {
                if ($this->importType === 'contacts') {
                    $this->importContact($data, $agencyId);
                } elseif ($this->importType === 'listings') {
                    $this->importListing($data, $agencyId);
                }
                $imported++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = $e->getMessage();
            }
        }
        fclose($handle);

        $this->importResults = compact('imported', 'failed', 'errors');
        $this->csvFile = null;
        $this->preview = [];
        $this->dispatch('notify', message: "Imported {$imported} records. {$failed} failed.", type: $failed ? 'warning' : 'success');
    }

    private function importContact(array $data, int $agencyId): void
    {
        Contact::updateOrCreate(
            ['agency_id' => $agencyId, 'email' => $data['email'] ?? null],
            [
                'agency_id' => $agencyId,
                'first_name' => $data['first_name'] ?? '',
                'last_name' => $data['last_name'] ?? '',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'type' => $data['type'] ?? 'buyer',
                'source' => $data['source'] ?? 'import',
                'assigned_agent_id' => auth()->id(),
            ]
        );
    }

    private function importListing(array $data, int $agencyId): void
    {
        Listing::create([
            'agency_id' => $agencyId,
            'agent_id' => auth()->id(),
            'listing_price' => $data['listing_price'] ?? 0,
            'status' => $data['status'] ?? 'active',
            'mandate_type' => $data['mandate_type'] ?? 'sole',
            'type' => $data['type'] ?? 'sale',
        ]);
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $agencyId = auth()->user()->agency_id;

        return response()->streamDownload(function () use ($agencyId) {
            $handle = fopen('php://output', 'w');

            if ($this->exportType === 'contacts') {
                fputcsv($handle, ['id', 'first_name', 'last_name', 'email', 'phone', 'type', 'source', 'intent_score', 'created_at']);
                Contact::where('agency_id', $agencyId)->chunk(200, function ($contacts) use ($handle) {
                    foreach ($contacts as $c) {
                        fputcsv($handle, [$c->id, $c->first_name, $c->last_name, $c->email, $c->phone, $c->type, $c->source, $c->intent_score, $c->created_at]);
                    }
                });
            } elseif ($this->exportType === 'listings') {
                fputcsv($handle, ['id', 'status', 'listing_price', 'type', 'mandate_type', 'days_on_market', 'health_score', 'created_at']);
                Listing::where('agency_id', $agencyId)->chunk(200, function ($listings) use ($handle) {
                    foreach ($listings as $l) {
                        fputcsv($handle, [$l->id, $l->status, $l->listing_price, $l->type, $l->mandate_type, $l->days_on_market, $l->health_score, $l->created_at]);
                    }
                });
            }

            fclose($handle);
        }, "export-{$this->exportType}-" . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render()
    {
        return view('livewire.crm.bulk-import-page')->layout('layouts.app');
    }
}
