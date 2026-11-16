<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Person;
use Illuminate\Http\Request;

class ActivityLogService
{
    /** @param array<string, scalar|null> $metadata */
    public function record(string $action, Request $request, ?Person $person = null, array $metadata = []): ActivityLog
    {
        return ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'person_id' => $person?->id,
            'action' => $action,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);
    }
}
