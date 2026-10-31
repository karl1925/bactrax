<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreModeRequest;
use App\Models\ProcurementMode\ProcurementMode;
use App\Models\Role;
use App\Models\ProcurementMode\Form;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcurementModeController extends Controller
{
    public function index(Request $request)
    {
        $modes = ProcurementMode::withCount('phases')->where('is_active', true)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%' . trim($request->search) . '%')->orWhere('code', 'like', '%' . trim($request->search) . '%')))
            ->orderBy('id', 'asc')->paginate(15)->withQueryString();
        return view('admin.modes.index', compact('modes'));
    }

    public function archivedIndex(Request $request)
    {
        $modes = ProcurementMode::withCount('phases')->where('is_active', false)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%' . trim($request->search) . '%')->orWhere('code', 'like', '%' . trim($request->search) . '%')))
            ->orderBy('id', 'asc')->paginate(15)->withQueryString();
        return view('admin.modes.archive', compact('modes'));
    }

    public function toggleArchive(ProcurementMode $mode)
    {
        $mode->update(['is_active' => ! $mode->is_active]);
        return back()->with('success', "Procurement mode '{$mode->name}' " . ($mode->is_active ? 'restored' : 'archived') . ' successfully.');
    }

    public function create()
    {
        return view('admin.modes.create');
    }

    public function store(StoreModeRequest $request)
    {
        $mode = ProcurementMode::create($request->validated());

        return redirect()->route('admin.modes.builder', $mode->id)
            ->with('success', "Procurement Mode '{$mode->name}' created. Design your workflow phases below.");
    }

    public function edit(ProcurementMode $mode)
    {
        return view('admin.modes.edit', compact('mode'));
    }

    public function update(StoreModeRequest $request, ProcurementMode $mode)
    {
        $mode->update($request->validated());

        return redirect()->route('admin.modes.index')
            ->with('success', "Procurement Mode '{$mode->name}' updated successfully.");
    }

    public function builder(ProcurementMode $mode)
    {
        $mode->load([
            'phases' => function ($q) {
                $q->orderBy('phase_sequence', 'asc')
                    ->with([
                        'roles',
                        'attachments.signatories.role', // Updated relationship
                        'attachments.form',
                    ]);
            },
        ]);

        $forms = Form::where('status', 'published')->where('id', '!=', (int)Setting::get('pr_form', 0))->get();
        $roles = Role::all();

        return view('admin.modes.builder', compact('mode', 'roles', 'forms'));
    }

    public function getForms(Request $request, ProcurementMode $mode): JsonResponse
    {
        // Optional: current_phase passed via request query string, e.g. /api/procurement-modes/1/forms?current_phase=3
        $currentPhase = (int) $request->query('current_phase', 0);

        // Fetch forms with pivot data
        $forms = $mode->forms()
            ->where('forms.status', 'published')
            ->get()
            ->map(function ($form) use ($currentPhase) {
                $phaseStart = $form->pivot->phase_start;
                $phaseEnd   = $form->pivot->phase_end;

                // Determine if form is in its end phase
                // (e.g. current phase equals phase_end or phase_end is reached)
                $isEndPhase = $currentPhase > 0 && $phaseEnd !== null && $currentPhase >= $phaseEnd;
                return [
                    'id'           => $form->id,
                    'code'         => $form->code,
                    'name'         => $form->name,
                    'description'  => $form->description,
                    'status'       => $form->status,
                    'configuration'=> $form->configuration,
                    'phase_start'  => $phaseStart,
                    'phase_end'    => $phaseEnd,
                    'is_end_phase' => $isEndPhase,
                ];
            });

        return response()->json([
            'success'   => true,
            'mode_id'   => $mode->id,
            'mode_name' => $mode->name,
            'forms'     => $forms,
        ]);
    }
}
