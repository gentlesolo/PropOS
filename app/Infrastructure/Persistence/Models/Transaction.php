<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'agent_split' => 'decimal:2',
        'offer_date' => 'date',
        'deadline' => 'date',
        'estimated_close_date' => 'date',
        'closed_at' => 'date',
        'timeline' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->reference ??= 'TXN-' . strtoupper(Str::random(8));
        });
    }

    public function deal(): BelongsTo { return $this->belongsTo(Deal::class); }
    public function listing(): BelongsTo { return $this->belongsTo(Listing::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'assigned_agent_id'); }
    public function attorney(): BelongsTo { return $this->belongsTo(User::class, 'attorney_id'); }
    public function documents(): HasMany { return $this->hasMany(ComplianceDocument::class); }
    public function commission(): HasOne { return $this->hasOne(Commission::class); }

    public function contract(): HasOne { return $this->hasOne(Contract::class); }
    public function inspections(): HasMany { return $this->hasMany(Inspection::class)->orderByDesc('scheduled_at'); }
    public function tasks(): HasMany { return $this->hasMany(Task::class)->orderBy('due_at'); }

    public function ficaDocuments(): HasMany
    {
        return $this->documents()->where('is_fica_required', true);
    }

    public function getFicaProgressAttribute(): int
    {
        $total = $this->ficaDocuments()->count();
        if ($total === 0) return 0;
        $approved = $this->ficaDocuments()->where('status', 'approved')->count();
        return (int) round(($approved / $total) * 100);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline && $this->deadline->isPast() && !in_array($this->status, ['completed', 'cancelled']);
    }
}
