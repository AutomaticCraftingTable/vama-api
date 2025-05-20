<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model
{
    use HasFactory;
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
