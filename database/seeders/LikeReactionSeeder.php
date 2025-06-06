<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LikeReaction;
use App\Models\User;
use App\Models\Article;

class LikeReactionSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $articles = Article::all();

        if ($users->isEmpty() || $articles->isEmpty()) {
            $this->command->warn('Seed users and articles first.');
            return;
        }

        $usedPairs = [];

        while (count($usedPairs) < 35) {
            $userId = $users->random()->id;
            $articleId = $articles->random()->id;
            $key = "$userId-$articleId";

            if (!isset($usedPairs[$key])) {
                LikeReaction::create([
                    'causer' => $userId,
                    'article_id' => $articleId,
                ]);

                $usedPairs[$key] = true;
            }
        }
    }
}
