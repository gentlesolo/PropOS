<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadRoutingRule extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $casts = [
        'conditions' => 'array',
        'agent_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('priority', 'desc');
    }

    public function getAgentPool(): array
    {
        return User::whereIn('id', $this->agent_ids ?? [])->get()->toArray();
    }

    public function advanceRoundRobinIndex(): void
    {
        $agents = $this->agent_ids ?? [];
        $next = ($this->current_index + 1) % max(count($agents), 1);
        $this->update(['current_index' => $next]);
    }
}
