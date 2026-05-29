<?php

namespace App\Http\Livewire\Marketing;

use App\Infrastructure\Persistence\Models\CampaignContent;
use Carbon\Carbon;
use Livewire\Component;

class ContentCalendarPage extends Component
{
    public int $year;
    public int $month;
    public ?int $selectedContentId = null;

    public function mount()
    {
        $this->year = now()->year;
        $this->month = now()->month;
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->year, $this->month)->subMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month)->addMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function selectContent(int $id): void
    {
        $this->selectedContentId = $this->selectedContentId === $id ? null : $id;
    }

    public function render()
    {
        $startOfMonth = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $contents = CampaignContent::with('campaign.listing.property')
            ->whereHas('campaign', fn($q) => $q->where('agency_id', auth()->user()->agency_id))
            ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn($c) => Carbon::parse($c->scheduled_at)->day);

        $calendarDays = collect();
        $startPadding = $startOfMonth->dayOfWeek; // 0=Sun
        for ($i = 0; $i < $startPadding; $i++) {
            $calendarDays->push(null);
        }
        for ($day = 1; $day <= $endOfMonth->day; $day++) {
            $calendarDays->push([
                'day' => $day,
                'date' => Carbon::create($this->year, $this->month, $day),
                'contents' => $contents->get($day, collect()),
            ]);
        }

        $selectedContent = $this->selectedContentId
            ? CampaignContent::with('campaign.listing.property')->find($this->selectedContentId)
            : null;

        return view('livewire.marketing.content-calendar-page', compact('calendarDays', 'selectedContent'))
            ->layout('layouts.app');
    }
}
