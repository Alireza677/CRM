<?php

namespace App\Providers;

use App\Models\Proforma;
use App\Policies\ProformaPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Task;
use App\Policies\TaskPolicy;
use App\Models\Document;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Proforma::class => ProformaPolicy::class,
        \App\Models\Project::class => \App\Policies\ProjectPolicy::class,
        Task::class => TaskPolicy::class,


    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        Gate::define('documents.view', function (User $user, Document $doc) {
            return ($user->is_admin ?? false) || $doc->user_id === $user->id;
        });
    
        Gate::define('documents.download', function (User $user, Document $doc) {
            return ($user->is_admin ?? false) || $doc->user_id === $user->id;
        });
    }
}
