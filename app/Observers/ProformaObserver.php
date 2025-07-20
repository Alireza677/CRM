<?php

namespace App\Observers;

use App\Models\Proforma;
use App\Models\AutomationRule;
use App\Notifications\FormAssignedNotification;

class ProformaObserver
{
    public function updated(Proforma $proforma)
    {
        if ($proforma->isDirty('stage')) {
            $newStage = $proforma->stage;

            // بررسی قانون
            $rule = AutomationRule::where('field', 'stage')
                ->where('operator', '=', '=')
                ->where('value', $newStage)
                ->with('approvers')
                ->first();

            if ($rule) {
                foreach ($rule->approvers as $approver) {
                    $user = $approver->user;
                    if ($user) {
                        $user->notify(new FormAssignedNotification(
                            model: $proforma,
                            type: 'proforma',
                            title: 'پیش‌فاکتور برای تایید شما ارسال شده است'
                        ));
                    }
                }
            }
        }
    }
}
