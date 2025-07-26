<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        $this->routes(function () {
            // بارگذاری مسیرهای API
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            // بارگذاری مسیرهای وب
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
