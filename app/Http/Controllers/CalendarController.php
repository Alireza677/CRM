<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\Holiday;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\Route;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $todayJalali = Jalalian::now();
        $jy = (int) $request->query('jy', $todayJalali->getYear());
        $jm = (int) $request->query('jm', $todayJalali->getMonth());

        if ($jm < 1 || $jm > 12) {
            $jm = $todayJalali->getMonth();
        }
        if ($jy < 1) {
            $jy = $todayJalali->getYear();
        }

        $grid = $this->buildJalaliMonthGrid($jy, $jm);
        $grid = $this->hydrateGridDates($grid, $jy, $jm);
        $rangeStart = Carbon::parse($grid['range']['start'])->startOfDay();
        $rangeEnd = Carbon::parse($grid['range']['end'])->endOfDay();
        $useShowRoute = Route::has('activities.show');
        $useEditRoute = Route::has('activities.edit');

        $activitiesByDate = Activity::query()
            ->visibleTo($request->user())
            ->whereBetween('start_at', [$rangeStart, $rangeEnd])
            ->orderBy('start_at')
            ->get(['id', 'subject', 'start_at'])
            ->map(function (Activity $activity) use ($useShowRoute, $useEditRoute) {
                $url = null;
                if ($useShowRoute) {
                    $url = route('activities.show', $activity->id);
                } elseif ($useEditRoute) {
                    $url = route('activities.edit', $activity->id);
                }

                return [
                    'id' => $activity->id,
                    'subject' => $activity->subject,
                    'start_at' => $activity->start_at,
                    'url' => $url,
                ];
            })
            ->groupBy(fn (array $activity) => $activity['start_at']?->toDateString())
            ->map(fn ($items) => $items->values());
        $holidaysByDate = Holiday::query()
            ->whereBetween('date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->orderBy('date')
            ->get()
            ->groupBy(fn (Holiday $holiday) => $holiday->date?->toDateString())
            ->map(fn ($items) => $items->values());
        $title = (new Jalalian($jy, $jm, 1))->format('F Y');

        return view('calendar.index', [
            'grid' => $grid,
            'activitiesByDate' => $activitiesByDate,
            'holidaysByDate' => $holidaysByDate,
            'current' => [
                'jy' => $jy,
                'jm' => $jm,
                'title' => $title,
            ],
            'today' => [
                'jy' => $todayJalali->getYear(),
                'jm' => $todayJalali->getMonth(),
                'jd' => $todayJalali->getDay(),
            ],
        ]);
    }

    // فید ایونت برای تقویم
    public function events(Request $request)
    {
        $user   = $request->user();
        $start  = $request->query('start'); // ISO8601
        $end    = $request->query('end');
        $scope  = $request->query('scope'); // personal|shared (optional)

        $q = Activity::query();

        if ($scope === 'shared') {
            // Only public activities
            $q->where('is_private', false);
        } elseif ($scope === 'personal') {
            // Only current user's activities (created by or assigned to)
            $q->where(function ($qq) use ($user) {
                $qq->where('created_by_id', $user->id)
                   ->orWhere('assigned_to_id', $user->id);
            });
        } else {
            // Fallback to visibility rules
            $q->visibleTo($user);
        }

        if ($start) $q->where('start_at', '>=', $start);
        if ($end) {
            $q->where(function ($qq) use ($end) {
                $qq->whereNull('due_at')->orWhere('due_at', '<=', $end);
            });
        }

        $activities = $q->get()->map->toCalendarEvent()->values()->all();

        // Holidays are global and always visible
        $startDate = $start ? Carbon::parse($start)->toDateString() : null;
        $endDate   = $end   ? Carbon::parse($end)->toDateString()   : null;

        $hq = Holiday::query();
        if ($startDate) {
            $hq->whereRaw('COALESCE(date_end, date) >= ?', [$startDate]);
        }
        if ($endDate) {
            $hq->where('date', '<=', $endDate);
        }

        $holidays = $hq->get()->map->toCalendarEvent()->values()->all();

        return response()->json(array_values(array_merge($activities, $holidays)));
    }

    private function buildJalaliMonthGrid(int $jy, int $jm): array
    {
        $firstJalali = new Jalalian($jy, $jm, 1);
        $firstCarbon = $firstJalali->toCarbon()->startOfDay();
        $offset = ($firstCarbon->dayOfWeek + 1) % 7; // Saturday=0 .. Friday=6
        $daysInMonth = $firstJalali->getMonthDays();
        $today = Carbon::today();

        $cells = [];
        for ($i = 0; $i < 42; $i++) {
            $isFriday = ($i % 7) === 6;
            if ($i < $offset || $i >= ($offset + $daysInMonth)) {
                $cells[] = [
                    'type' => 'empty',
                    'is_friday' => $isFriday,
                ];
                continue;
            }

            $day = $i - $offset + 1;
            $jalaliDate = new Jalalian($jy, $jm, $day);
            $carbonDate = $jalaliDate->toCarbon()->startOfDay();

            $cells[] = [
                'type' => 'day',
                'day' => $day,
                'jalali' => $jalaliDate->format('Y/m/d'),
                'gregorian' => $carbonDate->toDateString(),
                'is_today' => $carbonDate->isSameDay($today),
                'is_friday' => $isFriday,
            ];
        }

        return [
            'offset' => $offset,
            'daysInMonth' => $daysInMonth,
            'cells' => $cells,
            'prev' => $this->shiftJalaliMonth($jy, $jm, -1),
            'next' => $this->shiftJalaliMonth($jy, $jm, 1),
        ];
    }

    private function hydrateGridDates(array $grid, int $jy, int $jm): array
    {
        $offset = $grid['offset'];
        $firstJalali = new Jalalian($jy, $jm, 1);
        $startJalali = $firstJalali;

        if ($offset > 0) {
            $prev = $this->shiftJalaliMonth($jy, $jm, -1);
            $prevDays = (new Jalalian($prev['jy'], $prev['jm'], 1))->getMonthDays();
            $startDay = $prevDays - $offset + 1;
            $startJalali = new Jalalian($prev['jy'], $prev['jm'], $startDay);
        }

        $startCarbon = $startJalali->toCarbon()->startOfDay();

        foreach ($grid['cells'] as $i => $cell) {
            $cellCarbon = $startCarbon->copy()->addDays($i);
            $cellJalali = Jalalian::fromCarbon($cellCarbon);
            $cell['gregorian'] = $cellJalali->toCarbon()->toDateString();
            if (!isset($cell['jalali'])) {
                $cell['jalali'] = $cellJalali->format('Y/m/d');
            }
            $grid['cells'][$i] = $cell;
        }

        $grid['range'] = [
            'start' => $startCarbon->toDateString(),
            'end' => $startCarbon->copy()->addDays(count($grid['cells']) - 1)->toDateString(),
        ];

        return $grid;
    }

    private function shiftJalaliMonth(int $jy, int $jm, int $delta): array
    {
        $total = ($jy * 12) + ($jm - 1) + $delta;
        $newJy = (int) floor($total / 12);
        $newJm = $total % 12;
        if ($newJm < 0) {
            $newJm += 12;
        }

        return ['jy' => $newJy, 'jm' => $newJm + 1];
    }
}
