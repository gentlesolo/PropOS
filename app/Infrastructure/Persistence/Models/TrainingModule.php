<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingModule extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_published' => 'boolean',
        'order' => 'integer',
    ];

    public function progress(): HasMany
    {
        return $this->hasMany(TrainingProgress::class, 'module_id');
    }

    public function progressFor(int $userId): ?TrainingProgress
    {
        return $this->progress()->where('user_id', $userId)->first();
    }
}
