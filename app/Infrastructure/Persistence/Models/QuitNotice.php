<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QuitNotice extends Model
{
    use HasFactory, BelongsToAgency, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'issue_date'       => 'date',
        'vacate_by_date'   => 'date',
        'sent_at'          => 'datetime',
        'acknowledged_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->reference ??= 'QN-' . strtoupper(Str::random(8));
            $model->issue_date ??= now()->toDateString();
        });
    }

    public function lease(): BelongsTo { return $this->belongsTo(Lease::class); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function issuedBy(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
    // agency() provided by BelongsToAgency trait

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'drafted'      => 'secondary',
            'sent'         => 'brand',
            'acknowledged' => 'warning',
            'disputed'     => 'danger',
            'withdrawn'    => 'secondary',
            'completed'    => 'success',
            default        => 'secondary',
        };
    }

    public function getNoticePeriodDaysAttribute(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->vacate_by_date, false);
    }
}
