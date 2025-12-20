<?php

namespace App\Http\Controllers\Settings\Automation;

use App\Http\Controllers\Controller;
use App\Models\LeadRoundRobinSetting;
use App\Models\LeadRoundRobinUser;
use App\Models\SalesLead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LeadRoundRobinController extends Controller
{
    public function index()
    {
        $rows = LeadRoundRobinUser::with('user')
            ->orderBy('id')
            ->get();

        $availableUsers = User::query()
            ->whereNotIn('id', LeadRoundRobinUser::pluck('user_id'))
            ->orderBy('name')
            ->get();

        $settings = LeadRoundRobinSetting::query()->first();
        if (!$settings) {
            $settings = LeadRoundRobinSetting::create([
                'sla_duration_value' => 24,
                'sla_duration_unit' => 'hours',
                'max_reassign_count' => 2,
                'enable_rotation_warning' => false,
                'rotation_warning_time' => 6,
                'rotation_warning_unit' => 'hours',
            ]);
        }

        return view('settings.automation.leads_round_robin', [
            'rows'           => $rows,
            'availableUsers' => $availableUsers,
            'settings'       => $settings,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id', 'unique:lead_round_robin_users,user_id'],
        ]);

        LeadRoundRobinUser::create([
            'user_id'    => $data['user_id'],
            'is_active'  => true,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', __('Lead added to round robin.'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'sla_duration_value' => ['required', 'integer', 'min:1', 'max:100000'],
            'sla_duration_unit'  => ['required', 'in:minutes,hours'],
            'max_reassign_count' => ['required', 'integer', 'min:0', 'max:1000'],
            'enable_rotation_warning' => ['nullable', 'boolean'],
            'rotation_warning_time' => ['required', 'integer', 'min:1', 'max:100000'],
            'rotation_warning_unit' => ['required', 'in:hours,days'],
        ]);

        $settings = LeadRoundRobinSetting::query()->firstOrCreate([], [
            'sla_duration_value' => 24,
            'sla_duration_unit'  => 'hours',
            'max_reassign_count' => 2,
            'enable_rotation_warning' => false,
            'rotation_warning_time' => 6,
            'rotation_warning_unit' => 'hours',
        ]);

        $settings->fill([
            'sla_duration_value' => $data['sla_duration_value'],
            'sla_duration_unit' => $data['sla_duration_unit'],
            'max_reassign_count' => $data['max_reassign_count'],
            'enable_rotation_warning' => (bool) ($data['enable_rotation_warning'] ?? false),
            'rotation_warning_time' => $data['rotation_warning_time'],
            'rotation_warning_unit' => $data['rotation_warning_unit'],
        ])->save();

        $slaValue = (int) $data['sla_duration_value'];
        $slaUnit = $data['sla_duration_unit'];

        SalesLead::query()
            ->whereNotNull('rotation_due_at')
            ->whereNull('converted_at')
            ->where(function ($query) {
                $query->whereNull('lead_status')
                    ->orWhereNotIn('lead_status', [
                        SalesLead::STATUS_DISCARDED,
                        'lost',
                    ]);
            })
            ->orderBy('id')
            ->chunkById(200, function ($leads) use ($slaValue, $slaUnit) {
                foreach ($leads as $lead) {
                    if (!$lead->assigned_at) {
                        continue;
                    }

                    $base = Carbon::parse($lead->assigned_at);
                    $newDueAt = $slaUnit === 'minutes'
                        ? $base->copy()->addMinutes($slaValue)
                        : $base->copy()->addHours($slaValue);

                    $lead->forceFill([
                        'rotation_due_at' => $newDueAt,
                        'rotation_warning_sent_at' => null,
                    ])->save();
                }
            });

        return back()->with('success', __('Settings updated.'));
    }

    public function toggle(LeadRoundRobinUser $row)
    {
        $row->is_active = !$row->is_active;
        $row->save();

        return back()->with('success', __('Status updated.'));
    }

    public function destroy(LeadRoundRobinUser $row)
    {
        $row->delete();

        return back()->with('success', __('Lead removed from round robin.'));
    }
}
