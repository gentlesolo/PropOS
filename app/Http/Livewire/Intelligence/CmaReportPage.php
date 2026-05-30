<?php

namespace App\Http\Livewire\Intelligence;

use App\Infrastructure\Persistence\Models\CmaReport;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Services\CmaReportService;
use Livewire\Component;

class CmaReportPage extends Component
{
    public bool $showCreateForm = false;

    public string $title = '';
    public string $subject_address = '';
    public string $listing_id = '';
    public string $contact_id = '';
    public string $estimated_value_low = '';
    public string $estimated_value_high = '';
    public string $recommended_list_price = '';
    public string $summary = '';
    public array $comparable_sales = [];

    // New comparable form
    public string $comp_address = '';
    public string $comp_sale_price = '';
    public string $comp_sale_date = '';
    public string $comp_bedrooms = '';
    public string $comp_sqm = '';

    public function addComparable(): void
    {
        $this->validate([
            'comp_address' => 'required|string',
            'comp_sale_price' => 'required|numeric|min:1',
        ]);

        $this->comparable_sales[] = [
            'address' => $this->comp_address,
            'sale_price' => $this->comp_sale_price,
            'sale_date' => $this->comp_sale_date ?: null,
            'bedrooms' => $this->comp_bedrooms ?: null,
            'sqm' => $this->comp_sqm ?: null,
        ];

        $this->reset(['comp_address', 'comp_sale_price', 'comp_sale_date', 'comp_bedrooms', 'comp_sqm']);
    }

    public function removeComparable(int $index): void
    {
        array_splice($this->comparable_sales, $index, 1);
    }

    public function generate(CmaReportService $service): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'subject_address' => 'required|string|max:500',
            'estimated_value_low' => 'nullable|numeric|min:0',
            'estimated_value_high' => 'nullable|numeric|min:0',
            'recommended_list_price' => 'nullable|numeric|min:0',
        ]);

        $listing = $this->listing_id ? Listing::find($this->listing_id) : null;
        $contact = $this->contact_id ? Contact::find($this->contact_id) : null;

        $service->generate([
            'title' => $this->title,
            'subject_address' => $this->subject_address,
            'estimated_value_low' => $this->estimated_value_low ?: null,
            'estimated_value_high' => $this->estimated_value_high ?: null,
            'recommended_list_price' => $this->recommended_list_price ?: null,
            'comparable_sales' => $this->comparable_sales,
            'summary' => $this->summary ?: null,
        ], $listing, $contact);

        $this->reset(['showCreateForm', 'title', 'subject_address', 'listing_id', 'contact_id',
            'estimated_value_low', 'estimated_value_high', 'recommended_list_price', 'summary', 'comparable_sales']);
        $this->dispatch('notify', message: 'CMA report generated.', type: 'success');
    }

    public function render()
    {
        $reports = CmaReport::with('listing.property', 'contact', 'createdBy')
            ->orderByDesc('created_at')
            ->paginate(12);

        $listings = Listing::with('property:id,address')->latest()->get(['id', 'property_id']);
        $contacts = Contact::orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('livewire.intelligence.cma-report-page', compact('reports', 'listings', 'contacts'))
            ->layout('layouts.app');
    }
}
