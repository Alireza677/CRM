<?php

namespace App\Http\Controllers\Settings\Automation;

use App\Http\Controllers\Controller;
use App\Models\LeadRoundRobinSetting;
use App\Models\LeadRoundRobinUser;
use App\Models\User;
use Illuminate\Http\Request;

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
        ]);

        $settings = LeadRoundRobinSetting::query()->firstOrCreate([], [
            'sla_duration_value' => 24,
            'sla_duration_unit'  => 'hours',
            'max_reassign_count' => 2,
        ]);

        $settings->fill($data)->save();

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
