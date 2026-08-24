<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePhaseRequest;
use App\Models\ProcurementMode;
use App\Models\ProcurementModePhase;
use App\Models\ProcurementModePhaseDocument;
use Illuminate\Http\Request;

class ProcurementPhaseController extends Controller
{
    public function store(StorePhaseRequest $request, ProcurementMode $mode)
    {
        $phase = $mode->phases()->create($request->validated());

        return back()->with('success', "Phase '{$phase->phase_name}' added to {$mode->code}.");
    }

    public function update(StorePhaseRequest $request, ProcurementMode $mode, ProcurementModePhase $phase)
    {
        $phase->update($request->validated());

        return back()->with('success', "Phase '{$phase->phase_name}' updated successfully.");
    }

    public function destroy(ProcurementMode $mode, ProcurementModePhase $phase)
    {
        $phase->delete();

        return back()->with('success', "Phase '{$phase->phase_name}' removed from workflow.");
    }

    public function syncRoles(Request $request, ProcurementMode $mode, ProcurementModePhase $phase)
    {
        $request->validate([
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['exists:roles,id'],
        ]);

        $phase->roles()->sync($request->input('role_ids', []));

        return back()->with('success', "Authorized roles updated for phase '{$phase->phase_name}'.");
    }

    public function storeDocumentRequirement(Request $request, ProcurementMode $mode, ProcurementModePhase $phase)
    {
        $request->validate([
            'document_type_name' => ['required', 'string', 'max:255'],
            'is_mandatory' => ['boolean'],
        ]);

        $phase->documents()->create([
            'document_type_name' => $request->input('document_type_name'),
            'is_mandatory' => $request->boolean('is_mandatory'),
        ]);

        return back()->with('success', "Document requirement added to phase '{$phase->phase_name}'.");
    }

    public function destroyDocumentRequirement(ProcurementMode $mode, ProcurementModePhase $phase, ProcurementModePhaseDocument $document)
    {
        $document->delete();

        return back()->with('success', "Document requirement removed.");
    }
}
