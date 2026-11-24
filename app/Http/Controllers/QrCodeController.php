<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class QrCodeController extends Controller
{
    public function scanner(): View
    {
        return view('qr.scanner');
    }
}
