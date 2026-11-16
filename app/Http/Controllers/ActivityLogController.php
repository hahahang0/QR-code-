<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:80'],
            'search' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $logs = ActivityLog::query()
            ->with(['user', 'person'])
            ->when(isset($filters['action']), fn ($query) => $query->where('action', $filters['action']))
            ->when(isset($filters['search']) && trim($filters['search']) !== '', function ($query) use ($filters): void {
                $search = trim($filters['search']);
                $query->where(fn ($query) => $query->where('action', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('person', fn ($person) => $person->where('full_name', 'like', "%{$search}%")));
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
        $actions = ActivityLog::query()->distinct()->orderBy('action')->pluck('action');

        return view('admin.activity-logs.index', compact('logs', 'actions'));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate(['action' => ['nullable', 'string', 'max:80'], 'search' => ['nullable', 'string', 'max:120']]);
        $logs = ActivityLog::query()->with(['user', 'person'])
            ->when(isset($filters['action']), fn ($query) => $query->where('action', $filters['action']))
            ->when(isset($filters['search']) && trim($filters['search']) !== '', function ($query) use ($filters): void {
                $search = trim($filters['search']);
                $query->where(fn ($query) => $query->where('action', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('person', fn ($person) => $person->where('full_name', 'like', "%{$search}%")));
            })->latest('id')->get();

        return response()->streamDownload(function () use ($logs): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Administrator', 'Action', 'Person', 'Date and time', 'Result']);
            foreach ($logs as $log) {
                fputcsv($stream, [$log->user?->name ?? 'System', $log->action, $log->person?->full_name, $log->created_at->toIso8601String(), $log->metadata['outcome'] ?? 'Recorded']);
            }
            fclose($stream);
        }, 'activity-logs.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
