<?php

namespace App\Traits;

use App\Notifications\FormAssignedNotification;

trait NotifiesAssignee
{
    
    public function notifyIfAssigneeChanged($oldAssignedTo = null)
    {
        $currentUser = auth()->user();

        if (
            $this->assigned_to &&
            $this->assigned_to != $oldAssignedTo &&
            $this->assignedTo &&
            $currentUser
        ) {
            try {
                $this->assignedTo->notify(
                    new FormAssignedNotification($this, $currentUser)
                );
            } catch (\Throwable $e) {
                \Log::error('Failed to send assignment notification email', [
                    'model'  => static::class,
                    'model_id' => method_exists($this, 'getKey') ? $this->getKey() : null,
                    'user_id'  => $this->assigned_to,
                    'error'    => $e->getMessage(),
                ]);
            }

            // Route via NotificationRouter in parallel
            try {
                $module = match (true) {
                    $this instanceof \App\Models\SalesLead => 'leads',
                    $this instanceof \App\Models\Opportunity => 'opportunities',
                    $this instanceof \App\Models\Proforma => 'proformas',
                    default => null,
                };
                if ($module) {
                    $router = app(\App\Services\Notifications\NotificationRouter::class);
                    $title = null;
                    if (method_exists($this, 'getNotificationTitle')) {
                        $title = $this->getNotificationTitle();
                    } else {
                        foreach (['subject', 'name', 'title'] as $prop) {
                            if (!empty($this->{$prop})) {
                                $title = $this->{$prop};
                                break;
                            }
                        }
                    }
                    $oldAssigneeName = null;
                    if ($oldAssignedTo) {
                        $oldAssigneeName = \App\Models\User::query()->find($oldAssignedTo)?->name ?? $oldAssignedTo;
                    }
                    $newAssigneeName = optional($this->assignedTo)->name;
                    $context = [
                        'model' => $this,
                        'form_title' => (string) ($title ?? ''),
                        'old_assignee' => $oldAssigneeName,
                        'new_assignee' => $newAssigneeName,
                        'old_user'      => $oldAssigneeName,
                        'new_user'      => $newAssigneeName,
                        'actor' => $currentUser,
                        'sender_name' => optional($currentUser)->name,
                        'url' => method_exists($this, 'getKey') ? request()->fullUrl() : null,
                    ];
                    $router->route($module, 'assigned.changed', $context, [$this->assigned_to]);
                }
            } catch (\Throwable $e) {
                \Log::warning('NotifiesAssignee: NotificationRouter failed', ['error' => $e->getMessage()]);
            }
        }
    }
}
