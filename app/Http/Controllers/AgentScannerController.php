<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AgentScannerController extends Controller
{
    public function show(): View
    {
        return view('agent.scanner', [
            'verificationPattern' => '/agent/rdvs/{hashid}/verification',
        ]);
    }
}
