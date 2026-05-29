<?php

namespace App\Application\CRM\Actions;

use App\Infrastructure\Persistence\Models\Contact;
use Illuminate\Database\Eloquent\Collection;

class DetectDuplicateContactsAction
{
    public function execute(string $email = null, string $phone = null, int $excludeId = null): Collection
    {
        $query = Contact::query();

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $query->where(function ($q) use ($email, $phone) {
            if ($email) {
                $q->orWhere('email', $email);
            }
            if ($phone) {
                $normalized = preg_replace('/\D/', '', $phone);
                $q->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') LIKE ?", ["%{$normalized}%"]);
            }
        });

        return $query->limit(5)->get();
    }
}
