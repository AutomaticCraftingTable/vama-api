<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = ['content', 'causer', 'article_id'];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'causer', 'nickname');
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
