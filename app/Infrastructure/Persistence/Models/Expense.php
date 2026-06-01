<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Expense extends Model
{
    use HasFactory, BelongsToAgency, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'amount'           => 'decimal:2',
        'tax_amount'       => 'decimal:2',
        'is_tax_deductible'=> 'boolean',
        'expense_date'     => 'date',
        'approved_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->reference ??= 'EXP-' . strtoupper(Str::random(8));
            $model->period_month ??= (int) now()->format('m');
            $model->period_year  ??= (int) now()->format('Y');
        });
    }

    public function property(): BelongsTo  { return $this->belongsTo(Property::class); }
    public function listing(): BelongsTo   { return $this->belongsTo(Listing::class); }
    public function approver(): BelongsTo  { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeDeductible($query) { return $query->where('is_tax_deductible', true); }

    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('period_month', $month)->where('period_year', $year);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'paid'     => 'success',
            'rejected' => 'danger',
            default    => 'warning',
        };
    }
}
