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

        if (!$rule) {
            return null;
        }

        return match ($rule->strategy) {
            'round_robin' => $this->roundRobin($rule),
            'load_balanced' => $this->loadBalanced($rule),
            'specific_agent' => $this->specificAgent($rule),
            default => $this->roundRobin($rule),
        };
    }

    private function matchesConditions(LeadRoutingRule $rule, Contact $contact): bool
    {
        $conditions = $rule->conditions ?? [];
        if (empty($conditions)) return true;

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $op = $condition['operator'] ?? 'equals';
            $val = $condition['value'] ?? null;

            $contactVal = $contact->{$field} ?? null;

            $matches = match ($op) {
                'equals' => $contactVal == $val,
                'contains' => str_contains((string) $contactVal, (string) $val),
                'in' => in_array($contactVal, (array) $val),
                default => true,
            };

            if (!$matches) return false;
        }

        return true;
    }

    private function roundRobin(LeadRoutingRule $rule): ?User
    {
        $agentIds = $rule->agent_ids ?? [];
        if (empty($agentIds)) return null;

        $idx = $rule->current_index % count($agentIds);
        $agentId = $agentIds[$idx];
        $rule->advanceRoundRobinIndex();

        return User::find($agentId);
    }

    private function loadBalanced(LeadRoutingRule $rule): ?User
    {
        $agentIds = $rule->agent_ids ?? [];
        if (empty($agentIds)) return null;

        // Assign to agent with fewest open contacts
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
}
