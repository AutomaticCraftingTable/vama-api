<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\User;
use App\Models\Article;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $articles = Article::all();

        if ($users->isEmpty() || $articles->isEmpty()) {
            $this->command->error('Seed users and articles first.');
            return;
        }

        Comment::factory()
            ->count(50)
            ->make()
            ->each(function ($comment) use ($users, $articles) {
                $comment->causer = $users->random()->id;
                $comment->article_id = $articles->random()->id;
                $comment->save();
            });
    }
}
