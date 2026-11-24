<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeneratePersonQrCodeRequest;
use App\Models\Person;
use App\Models\QrCode as QrCodeRecord;
use App\Services\ActivityLogService;
use App\Services\QrRecordService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class QrManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', 'string', 'in:current,previous']]);
        $query = QrCodeRecord::query()->with(['person', 'generator'])
            ->when(isset($filters['search']) && trim($filters['search']) !== '', function ($query) use ($filters): void {
                $search = trim($filters['search']);
                $query->whereHas('person', fn ($person) => $person->where('full_name', 'like', "%{$search}%")->orWhere('reference_number', 'like', "%{$search}%"));
            })
            ->when(($filters['status'] ?? null) === 'current', fn ($query) => $query->where('is_current', true))
            ->when(($filters['status'] ?? null) === 'previous', fn ($query) => $query->where('is_current', false));
        $qrCodes = $query->latest('id')->paginate(15)->withQueryString();
        $counts = ['all' => QrCodeRecord::query()->count(), 'current' => QrCodeRecord::query()->where('is_current', true)->count(), 'previous' => QrCodeRecord::query()->where('is_current', false)->count()];

        return view('admin.qr-codes.index', compact('qrCodes', 'counts'));
    }

    public function store(GeneratePersonQrCodeRequest $request, Person $person, QrRecordService $qrRecords, ActivityLogService $activityLogs): RedirectResponse
    {
        if ($person->status !== 'active') {
            return back()->withErrors(['person' => 'Activate this profile before generating a public QR code.']);
        }

        $values = $request->validated();

        try {
            $qrCode = $qrRecords->generate($person, $request->user(), $values['error_correction']);
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['include' => $exception->getMessage()]);
        }

        $activityLogs->record('qr_code.generated', $request, $person, ['qr_code_id' => $qrCode->id, 'error_correction' => $qrCode->error_correction]);

        return redirect()->route('admin.people.show', $person)->with('status', 'QR code generated successfully.');
    }

    public function download(QrCodeRecord $qrCode, string $format, QrRecordService $qrRecords): Response
    {
        abort_unless(in_array($format, ['png', 'svg'], true), 404);
        $images = $qrRecords->render($qrCode);
        $dataUri = $images[$format];
        $bytes = base64_decode(substr($dataUri, strpos($dataUri, ',') + 1), true);

        abort_if($bytes === false, 500, 'QR image generation failed.');

        return response($bytes, 200, [
            'Content-Type' => $format === 'svg' ? 'image/svg+xml; charset=utf-8' : 'image/png',
            'Content-Disposition' => 'attachment; filename="person-'.$qrCode->person_id.'-qr.'.$format.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function preview(QrCodeRecord $qrCode, string $format, QrRecordService $qrRecords): Response
    {
        abort_unless(in_array($format, ['png', 'svg'], true), 404);
        $images = $qrRecords->render($qrCode);
        $bytes = base64_decode(substr($images[$format], strpos($images[$format], ',') + 1), true);

        abort_if($bytes === false, 500, 'QR image generation failed.');

        return response($bytes, 200, [
            'Content-Type' => $format === 'svg' ? 'image/svg+xml; charset=utf-8' : 'image/png',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function deactivate(Request $request, QrCodeRecord $qrCode, ActivityLogService $activityLogs): RedirectResponse
    {
        if (! $qrCode->is_current) {
            return back()->with('status', 'This QR code version is already inactive.');
        }

        $qrCode->update(['is_current' => false]);
        $activityLogs->record('qr_code.deactivated', $request, $qrCode->person, ['qr_code_id' => $qrCode->id]);

        return back()->with('status', 'QR code deactivated. Printed or downloaded copies cannot be revoked.');
    }
}
