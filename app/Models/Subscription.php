<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = ['causer', 'author'];

    public function causerProfile()
    {
        return $this->belongsTo(Profile::class, 'causer', 'nickname');
    }

    public function authorProfile()
    {
        return $this->belongsTo(Profile::class, 'author', 'nickname');
    }
}
