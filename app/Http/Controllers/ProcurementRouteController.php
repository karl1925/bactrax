<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Procurement;
use App\Services\ProcurementWorkflowEngine;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProcurementRouteController extends Controller
{
    use AuthorizesRequests;
    protected ProcurementWorkflowEngine $workflowEngine;

    public function __construct(ProcurementWorkflowEngine $workflowEngine)
    {
        $this->workflowEngine = $workflowEngine;
    }

    public function route(Request $request, Procurement $procurement)
    {

        // Enforce ProcurementPolicy@route (verifies active phase role)
        $this->authorize('route', $procurement);

        $request->validate([
            'to_office_id' => ['required', 'exists:offices,id'],
            'to_user_id' => ['nullable', 'exists:users,id',],
            'remarks' => ['nullable', 'string'],
        ]);


        try {
            $this->workflowEngine->routeToNextPhase(
                $procurement,
                $request->user(),
                (int) $request->input('to_office_id'),
                $request->input('remarks'),
                isset($validated['to_user_id'])
                    ? (int) $validated['to_user_id']
                    : null
            );

            return back()->with('success', "Procurement {$procurement->tracking_number} routed successfully.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function return(Request $request, Procurement $procurement)
    {
        // Enforce ProcurementPolicy@return (verifies active phase role)
        $this->authorize('return', $procurement);

        $request->validate([
            'target_phase_id' => ['required', 'exists:procurement_mode_phases,id'],
            'remarks' => ['required', 'string', 'min:5'],
        ]);

        try {
            $this->workflowEngine->returnProcurement(
                $procurement,
                $request->user(),
                (int) $request->input('target_phase_id'),
                $request->input('remarks')
            );

            return back()->with('success', "Procurement {$procurement->tracking_number} returned for revision.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function complete(Request $request, Procurement $procurement)
    {
        $this->authorize('complete', $procurement);

        $validated = $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        try {
            $this->workflowEngine->completeProcurement(
                $procurement,
                $request->user(),
                $validated['remarks'] ?? null
            );

            return back()->with('success', "Procurement {$procurement->tracking_number} completed successfully.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, Procurement $procurement)
    {
        $this->authorize('cancel', $procurement);

        $validated = $request->validate([
            'remarks' => ['required', 'string', 'min:5'],
        ]);

        try {
            $this->workflowEngine->cancelProcurement(
                $procurement,
                $request->user(),
                $validated['remarks']
            );

            return back()->with('success', "Procurement {$procurement->tracking_number} cancelled.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, Procurement $procurement)
    {
        $this->authorize('reject', $procurement);

        $validated = $request->validate([
            'remarks' => ['required', 'string', 'min:5'],
        ]);

        try {
            $this->workflowEngine->rejectProcurement(
                $procurement,
                $request->user(),
                $validated['remarks']
            );

            return back()->with('success', "Procurement {$procurement->tracking_number} rejected.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
