<?php

namespace App\Infrastructure\Services;

use App\Infrastructure\Persistence\Models\Viewing;
use App\Infrastructure\Persistence\Models\OpenHouse;
use App\Infrastructure\Persistence\Models\Task;

class CalendarSyncService
{
    public function syncViewingToGoogle(Viewing $viewing): ?string
    {
        $token = $this->getGoogleToken($viewing->agent_id ?? auth()->id());
        if (!$token) return null;

        $client = $this->buildGoogleClient($token);
        $service = new \Google\Service\Calendar($client);

        $event = new \Google\Service\Calendar\Event([
            'summary' => "Viewing: {$viewing->listing?->property?->address}",
            'description' => $viewing->notes ?? '',
            'start' => ['dateTime' => $viewing->scheduled_at->toRfc3339String(), 'timeZone' => config('app.timezone')],
            'end' => ['dateTime' => $viewing->scheduled_at->addHour()->toRfc3339String(), 'timeZone' => config('app.timezone')],
            'attendees' => array_filter([
                $viewing->contact?->email ? ['email' => $viewing->contact->email] : null,
            ]),
        ]);

        $created = $service->events->insert('primary', $event);
        return $created->getId();
    }

    public function syncTaskToGoogle(Task $task): ?string
    {
        $token = $this->getGoogleToken($task->assigned_to ?? auth()->id());
        if (!$token) return null;

        $client = $this->buildGoogleClient($token);
        $service = new \Google\Service\Calendar($client);

        $event = new \Google\Service\Calendar\Event([
            'summary' => $task->title,
            'description' => $task->description ?? '',
            'start' => ['dateTime' => $task->due_at->toRfc3339String(), 'timeZone' => config('app.timezone')],
            'end' => ['dateTime' => $task->due_at->addMinutes(30)->toRfc3339String(), 'timeZone' => config('app.timezone')],
        ]);

        $created = $service->events->insert('primary', $event);
        return $created->getId();
    }

    public function buildIcsContent(array $events): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//VillaCRM//Real Estate CRM//EN',
        ];

        foreach ($events as $event) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . ($event['uid'] ?? uniqid());
            $lines[] = 'DTSTAMP:' . now()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART:' . $event['start']->format('Ymd\THis\Z');
            $lines[] = 'DTEND:' . $event['end']->format('Ymd\THis\Z');
            $lines[] = 'SUMMARY:' . $this->escapeIcs($event['summary']);
            if (!empty($event['description'])) {
                $lines[] = 'DESCRIPTION:' . $this->escapeIcs($event['description']);
            }
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';
        return implode("\r\n", $lines);
    }

    private function getGoogleToken(?int $userId = null): ?array
    {
        $cred = \App\Infrastructure\Persistence\Models\IntegrationCredential::where('service', 'google_calendar')
            ->first();

        return $cred ? $cred->credentials : null;
    }

    private function buildGoogleClient(array $token): \Google\Client
    {
        $client = new \Google\Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired() && isset($token['refresh_token'])) {
            $client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
        }

        return $client;
    }

    private function escapeIcs(string $text): string
    {
        return str_replace([',', ';', '\\', "\n"], ['\\,', '\\;', '\\\\', '\\n'], $text);
    }
}
