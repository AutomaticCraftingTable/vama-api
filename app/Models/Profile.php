<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'nickname',
        'description',
        'logo',
        'user_id',
    ];

    public $incrementing = false;
    protected $primaryKey = 'user_id';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'author', 'nickname');
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'causer', 'nickname');
    }
}
