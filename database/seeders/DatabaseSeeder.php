<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
        UserWithProfilesSeeder::class,
        ArticleSeeder::class,
        CommentSeeder::class,
        BanSeeder::class,
        LikeReactionSeeder::class,
        NoteSeeder::class,
        SubscriptionSeeder::class,
        ReportSeeder::class,
    ]);
    }
}
