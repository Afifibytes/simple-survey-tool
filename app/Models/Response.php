<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Response extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'session_id',
        'nps_score',
        'open_text',
        'ai_follow_up_question',
        'ai_follow_up_answer',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function isCompleted(): bool
    {
        return !is_null($this->completed_at);
    }

    public function hasAiFollowUp(): bool
    {
        return !is_null($this->ai_follow_up_question);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeWithNpsScore($query)
    {
        return $query->whereNotNull('nps_score');
    }
}
