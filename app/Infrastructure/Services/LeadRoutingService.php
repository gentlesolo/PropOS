<?php

namespace App\Infrastructure\Services;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\LeadRoutingRule;
use App\Infrastructure\Persistence\Models\User;

class LeadRoutingService
{
    public function assignAgent(Contact $contact): ?User
    {
        $rule = LeadRoutingRule::active()
            ->where('agency_id', $contact->agency_id)
            ->get()
            ->first(fn ($r) => $this->matchesConditions($r, $contact));

        if (! $rule) {
            return null;
        }

        return match ($rule->strategy) {
            'round_robin'    => $this->roundRobin($rule),
            'load_balanced'  => $this->loadBalanced($rule),
            'specific_agent' => $this->specificAgent($rule),
            'territory'      => $this->territory($rule, $contact),
            default          => $this->roundRobin($rule),
        };
    }

    private function matchesConditions(LeadRoutingRule $rule, Contact $contact): bool
    {
        $conditions = $rule->conditions ?? [];
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $op    = $condition['operator'] ?? 'equals';
            $val   = $condition['value'] ?? null;

            // Territory conditions are evaluated inside territory() — skip here
            if ($op === 'territory') {
                continue;
            }

            $contactVal = $contact->{$field} ?? null;

            $matches = match ($op) {
                'equals'   => $contactVal == $val,
                'contains' => str_contains((string) $contactVal, (string) $val),
                'in'       => \in_array($contactVal, (array) $val),
                default    => true,
            };

            if (! $matches) {
                return false;
            }
        }

        return true;
    }

    private function roundRobin(LeadRoutingRule $rule): ?User
    {
        $agentIds = $rule->agent_ids ?? [];
        if (empty($agentIds)) {
            return null;
        }

        $idx     = $rule->current_index % count($agentIds);
        $agentId = $agentIds[$idx];
        $rule->advanceRoundRobinIndex();

        return User::find($agentId);
    }

    private function loadBalanced(LeadRoutingRule $rule): ?User
    {
        $agentIds = $rule->agent_ids ?? [];
        if (empty($agentIds)) {
            return null;
        }

        return User::whereIn('id', $agentIds)
            ->withCount(['contacts as open_count' => fn ($q) => $q->whereNull('deleted_at')])
            ->orderBy('open_count')
            ->first();
    }

    private function specificAgent(LeadRoutingRule $rule): ?User
    {
        $agentIds = $rule->agent_ids ?? [];
        return $agentIds ? User::find($agentIds[0]) : null;
    }

    /**
     * Territory routing: each condition with operator "territory" maps
     * a location value (city or state) to a specific agent_id via "agent_id".
     *
     * Condition shape:
     *   { "field": "city", "operator": "territory", "value": "Lagos", "agent_id": 42 }
     *
     * The contact's city and state_province are checked against all territory
     * conditions (case-insensitive). The first match wins. Falls back to
     * round-robin over the rule's agent pool when no territory matches.
     */
    private function territory(LeadRoutingRule $rule, Contact $contact): ?User
    {
        $conditions = $rule->conditions ?? [];
        $property   = $contact->deals()->latest()->first()?->listing?->property
            ?? $contact->tenant?->listing?->property;

        // Location sources: prefer linked property, fall back to contact preferences
        $city    = $property?->city
            ?? ($contact->preferences['preferred_city'] ?? null);
        $state   = $property?->state_province
            ?? ($contact->preferences['preferred_state'] ?? null);

        foreach ($conditions as $condition) {
            if (($condition['operator'] ?? '') !== 'territory') {
                continue;
            }

            $field     = $condition['field'] ?? 'city';
            $territory = strtolower(trim($condition['value'] ?? ''));
            $agentId   = $condition['agent_id'] ?? null;

            if (! $territory || ! $agentId) {
                continue;
            }

            $contactLocation = strtolower(trim(
                $field === 'state_province' ? ($state ?? '') : ($city ?? '')
            ));

            if ($contactLocation && str_contains($contactLocation, $territory)) {
                return User::find($agentId);
            }
        }

        // No territory matched — fall back to round-robin over the rule's agent pool
        return $this->roundRobin($rule);
    }
}
