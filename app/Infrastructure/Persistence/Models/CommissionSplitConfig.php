<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionSplitConfig extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'agent_split' => 'decimal:2',
        'agency_split' => 'decimal:2',
        'referral_split' => 'decimal:2',
        'franchise_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeAgencyDefault($query) { return $query->where('applies_to', 'agency_default'); }

    public function calculateAgentPayout(float $salePrice): float
    {
        $gross = $salePrice * ($this->commission_rate / 100);
        $agentGross = $gross * ($this->agent_split / 100);
        $referral = $agentGross * ($this->referral_split / 100);
        $franchise = $agentGross * ($this->franchise_fee / 100);
        return round($agentGross - $referral - $franchise, 2);
    }
}
