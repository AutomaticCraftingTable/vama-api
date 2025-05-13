<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Ban extends Model
{
    protected $fillable = [
    'causer',
    'target_type',
    'target_id',
    'content',
];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer');
    }

    public function target(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'target_type', 'target_id');
    }
}
