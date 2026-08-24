<?php

namespace App\Http\Controllers;

use App\Models\Procurement;

class PrintRoutingSlipController extends Controller
{
    public function __invoke(Procurement $procurement)
    {
        $procurement->load(['endUserOffice', 'mode', 'currentPhase', 'routes.fromOffice', 'routes.toOffice']);

        // Generate QR code data string containing tracking URL and details
        $qrData = route('procurements.show', $procurement->id);

        return view('procurements.print-routing-slip', compact('procurement', 'qrData'));
    }
}
