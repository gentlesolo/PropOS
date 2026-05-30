<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmaReport extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $casts = [
        'estimated_value_low' => 'decimal:2',
        'estimated_value_high' => 'decimal:2',
        'recommended_list_price' => 'decimal:2',
        'comparable_sales' => 'array',
        'market_stats' => 'array',
    ];

    public function listing(): BelongsTo { return $this->belongsTo(Listing::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function getValueRangeAttribute(): string
    {
        if (!$this->estimated_value_low && !$this->estimated_value_high) return 'N/A';
        $low = '₦' . number_format($this->estimated_value_low);
        $high = '₦' . number_format($this->estimated_value_high);
        return "{$low} – {$high}";
    }
}
