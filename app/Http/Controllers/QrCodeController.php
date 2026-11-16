<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateQrCodeRequest;
use App\Services\QrCodeService;
use App\Services\QrPayloadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class QrCodeController extends Controller
{
    public function index(): View
    {
        return view('qr.index');
    }

    public function scanner(): View
    {
        return view('qr.index', ['scannerOnly' => true]);
    }

    public function generate(
        GenerateQrCodeRequest $request,
        QrPayloadService $payloadService,
        QrCodeService $qrCodeService,
    ): View|JsonResponse {
        $validated = $request->validated();
        $payload = $payloadService->create($validated['person'], $validated['include'] ?? []);

        try {
            $payloadService->assertWithinCapacity($payload, $validated['error_correction']);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['person' => $exception->getMessage()]);
        }

        $images = $qrCodeService->generate($payload, $validated['error_correction'], 1024);
        $decoded = $payloadService->validate($payload);
        $result = [
            'payload' => $payload,
            'bytes' => strlen($payload),
            'errorCorrection' => $validated['error_correction'],
            'person' => $decoded['person'],
            'svg' => $images['svg'],
            'png' => $images['png'],
        ];

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return view('qr.index', [
            'result' => $result,
        ]);
    }
}
