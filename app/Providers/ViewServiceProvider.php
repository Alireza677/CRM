<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\BreadcrumbService;

class ViewServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        View::composer('*', function ($view) {
            $breadcrumbService = app(BreadcrumbService::class);
            $breadcrumbItems = $breadcrumbService->generate();
            
            $view->with('breadcrumbItems', $breadcrumbItems);
        });
    }
} 