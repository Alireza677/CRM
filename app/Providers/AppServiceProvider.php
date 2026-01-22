<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use App\Models\Proforma;
use App\Observers\ProformaObserver;
use App\Services\Sms\FarazEdgeService;
use App\Models\AppSetting;


// Spatie middlewares
use Spatie\Permission\Middlewares\RoleMiddleware;
use Spatie\Permission\Middlewares\PermissionMiddleware;
use Spatie\Permission\Middlewares\RoleOrPermissionMiddleware;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FarazEdgeService::class, fn() => new FarazEdgeService());

    }

    

    /**
     * Bootstrap any application services.
     */
    public function boot(Router $router): void
    {
        // ثبت Observerهای شما
        Proforma::observe(ProformaObserver::class);

        // ثبت صریح alias میدلورها برای Router
        $router->aliasMiddleware('role', \Spatie\Permission\Middleware\RoleMiddleware::class);
        $router->aliasMiddleware('permission', \Spatie\Permission\Middleware\PermissionMiddleware::class);
        $router->aliasMiddleware('role_or_permission', \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class);

        try {
            $assetsEmergency = AppSetting::getBool('assets_emergency', config('app.assets_emergency'));
            config(['app.assets_emergency' => $assetsEmergency]);
        } catch (\Throwable $e) {
            // ignore settings lookup failures
        }
        }
}
