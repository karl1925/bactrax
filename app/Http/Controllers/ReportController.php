<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\GppbExportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected GppbExportService $exportService;

    public function __construct(GppbExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    public function gppbCompliance(Request $request)
    {
        $year = (int) $request->input('year', date('Y'));
        $reportData = $this->exportService->generateAnnualRegister($year);

        return view('reports.gppb-compliance', compact('reportData', 'year'));
    }
}
