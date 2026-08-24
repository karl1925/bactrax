<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreModeRequest;
use App\Models\ProcurementMode;
use App\Models\Role;
use Illuminate\Http\Request;

class ProcurementModeController extends Controller
{
    public function index()
    {
        $modes = ProcurementMode::withCount('phases')->orderBy('id', 'asc')->get();
        return view('procurement-modes.index', compact('modes'));
    }

    public function create()
    {
        return view('procurement-modes.create');
    }

    public function store(StoreModeRequest $request)
    {
        $mode = ProcurementMode::create($request->validated());

        return redirect()->route('procurement-modes.builder', $mode->id)
            ->with('success', "Procurement Mode '{$mode->name}' created. Design your workflow phases below.");
    }

    public function edit(ProcurementMode $mode)
    {
        return view('procurement-modes.edit', compact('mode'));
    }

    public function update(StoreModeRequest $request, ProcurementMode $mode)
    {
        $mode->update($request->validated());

        return redirect()->route('procurement-modes.index')
            ->with('success', "Procurement Mode '{$mode->name}' updated successfully.");
    }

    public function builder(ProcurementMode $mode)
    {
        $mode->load([
            'phases' => function ($q) {
                $q->orderBy('phase_sequence', 'asc')->with(['roles', 'documents']);
            }
        ]);

        $roles = Role::all();

        return view('procurement-modes.builder', compact('mode', 'roles'));
    }
}
