<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $casts = [
        'monthly_income_targets'  => 'array',
        'monthly_expense_targets' => 'array',
        'vacancy_rate_assumption' => 'decimal:2',
        'escalation_assumption'   => 'decimal:2',
        'approved_at'             => 'datetime',
    ];

    public function property(): BelongsTo  { return $this->belongsTo(Property::class); }
    public function approver(): BelongsTo  { return $this->belongsTo(User::class, 'approved_by'); }

    public function getAnnualIncomeTargetAttribute(): float
    {
        return (float) array_sum($this->monthly_income_targets ?? []);
    }

    public function getAnnualExpenseTargetAttribute(): float
    {
        return (float) array_sum($this->monthly_expense_targets ?? []);
    }
}
