<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Listeners\LogVerifiedUser;
use Illuminate\Auth\Events\Verified;

class AppServiceProvider extends ServiceProvider
{
    protected $listen = [
    Verified::class => [
        LogVerifiedUser::class,
    ],
    ];

    public function register(): void
    {
    }

    public function boot(): void
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }
}
