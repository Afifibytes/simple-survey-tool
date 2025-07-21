<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'type',
        'text',
        'order',
        'options',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function isNps(): bool
    {
        return $this->type === 'nps';
    }

    public function isText(): bool
    {
        return $this->type === 'text';
    }

    public function isAiFollowUp(): bool
    {
        return $this->type === 'ai_follow_up';
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
