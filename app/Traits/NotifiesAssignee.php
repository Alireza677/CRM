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
            $this->assignedTo->notify(
                new FormAssignedNotification($this, $currentUser)
            );
        }
    }
}
