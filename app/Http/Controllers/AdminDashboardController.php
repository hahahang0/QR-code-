<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Person;
use App\Models\QrCode as QrCodeRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate(['range' => ['nullable', 'integer', 'in:7,30,90']]);
        $range = (int) ($filters['range'] ?? 7);
        $statistics = [
            'people' => Person::query()->count(),
            'qr_codes' => QrCodeRecord::query()->count(),
            'active_qr_codes' => QrCodeRecord::query()->where('is_current', true)->whereHas('person')->count(),
            'inactive_people' => Person::withTrashed()->where(function ($query): void {
                $query->where('status', 'archived')->orWhereNotNull('deleted_at');
            })->count(),
        ];
        $recentPeople = Person::query()->with('currentQrCode')->latest('id')->limit(5)->get();
        $recentActivity = ActivityLog::query()->with(['user', 'person'])->latest('id')->limit(8)->get();
        $start = now()->subDays($range - 1)->startOfDay();
        $totals = Person::query()->whereBetween('created_at', [$start, now()])
            ->selectRaw('DATE(created_at) as registration_day, COUNT(*) as total')
            ->groupBy('registration_day')
            ->pluck('total', 'registration_day');
        $registrationDays = collect(range($range - 1, 0))->map(function (int $daysAgo) use ($totals): array {
            $date = now()->subDays($daysAgo)->toDateString();

            return ['label' => now()->subDays($daysAgo)->format($daysAgo === 0 || $daysAgo % 7 === 0 ? 'M j' : 'D'), 'total' => (int) ($totals[$date] ?? 0)];
        });
        $maxRegistrations = max(1, $registrationDays->max('total'));

        return view('admin.dashboard', compact('statistics', 'recentPeople', 'recentActivity', 'range', 'registrationDays', 'maxRegistrations'));
    }
}
