<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\User;

class Report extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'causer',
        'target_type',
        'target_id',
        'content',
    ];

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer');
    }

    public function target(): MorphTo
    {
        return $this->morphTo(null, 'target_type', 'target_id');
    }
}
