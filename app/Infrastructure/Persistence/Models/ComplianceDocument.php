<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceDocument extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_fica_required' => 'boolean',
        'expiry_date' => 'date',
        'reviewed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    public function transaction(): BelongsTo { return $this->belongsTo(Transaction::class); }
    public function uploadedBy(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'under_review' => 'warning',
            'uploaded' => 'info',
            default => 'slate',
        };
    }
}
