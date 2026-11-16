<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanEventController extends Controller
{
    public function store(Request $request, ActivityLogService $activityLogs): JsonResponse
    {
        $validated = $request->validate([
            'outcome' => ['required', 'string', 'in:valid,invalid'],
        ]);

        $activityLogs->record('qr_code.scanned', $request, metadata: ['outcome' => $validated['outcome']]);

        return response()->json(['recorded' => true], 201);
    }
}
