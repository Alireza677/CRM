<?php

namespace App\Traits;

use App\Notifications\FormAssignedNotification;

trait NotifiesAssignee
{
    /**
     * ارسال اعلان در صورت تغییر ارجاع به
     *
     * @param mixed|null $oldAssignedTo
     */
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
        }
    }
}
