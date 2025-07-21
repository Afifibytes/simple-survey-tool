<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    public function getResponseCountAttribute(): int
    {
        return $this->responses()->whereNotNull('completed_at')->count();
    }

    public function getAverageNpsScoreAttribute(): ?float
    {
        return $this->responses()
            ->whereNotNull('nps_score')
            ->avg('nps_score');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
