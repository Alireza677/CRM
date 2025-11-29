<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

use App\Models\User;
use App\Models\Task;
use App\Policies\TaskPolicy;
use App\Models\Report;
use App\Policies\ReportPolicy;

use App\Models\Proforma;
use App\Policies\ProformaPolicy;
use App\Models\Lead;
use App\Policies\LeadPolicy;
use App\Models\Opportunity;
use App\Policies\OpportunityPolicy;
use App\Models\Contact;
use App\Policies\ContactPolicy;
use App\Models\Organization;
use App\Policies\OrganizationPolicy;
use App\Models\Document;
use App\Policies\DocumentPolicy;
use App\Models\PurchaseOrder;
use App\Policies\PurchaseOrderPolicy;
use App\Models\OnlineChatGroup;
use App\Policies\OnlineChatGroupPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Proforma::class => ProformaPolicy::class,
        Lead::class => LeadPolicy::class,
        Opportunity::class => OpportunityPolicy::class,
        Contact::class => ContactPolicy::class,
        Organization::class => OrganizationPolicy::class,
        Document::class => DocumentPolicy::class,
        \App\Models\Project::class => \App\Policies\ProjectPolicy::class,
        Task::class => TaskPolicy::class,
        Report::class => ReportPolicy::class,
        PurchaseOrder::class => PurchaseOrderPolicy::class,
        OnlineChatGroup::class => OnlineChatGroupPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Example of a specific gate that might still be used elsewhere
        Gate::define('documents.download', function (User $user, Document $doc) {
            return ($user->is_admin ?? false) || $doc->user_id === $user->id;
        });
    }
}
