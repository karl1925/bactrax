<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FundCluster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FundClusterController extends Controller
{
    public function index(Request $request): View
    {
        $fundClusters = FundCluster::where('is_active', true)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%' . trim($request->search) . '%')->orWhere('code', 'like', '%' . trim($request->search) . '%')))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.fundclusters.index', compact('fundClusters'));
    }

    public function archivedIndex(Request $request): View
    {
        $fundClusters = FundCluster::where('is_active', false)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%' . trim($request->search) . '%')->orWhere('code', 'like', '%' . trim($request->search) . '%')))
            ->orderBy('name')->paginate(15)->withQueryString();
        return view('admin.fundclusters.archive', compact('fundClusters'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (is_string($request->input('code'))) $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/', 'unique:fund_clusters,code'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        FundCluster::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.fundclusters.index')
            ->with('success', 'Fund cluster registered successfully.');
    }

    public function update(Request $request, FundCluster $fundCluster): RedirectResponse
    {
        if (is_string($request->input('code'))) $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/', 'unique:fund_clusters,code,' . $fundCluster->id],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $fundCluster->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.fundclusters.index')
            ->with('success', 'Fund cluster updated successfully.');
    }

    public function toggle(FundCluster $fundCluster): RedirectResponse
    {
        $fundCluster->update([
            'is_active' => !$fundCluster->is_active,
        ]);

        return redirect()
            ->route('admin.fundclusters.index')
            ->with(
                'success',
                'Fund cluster ' . ($fundCluster->is_active ? 'activated' : 'deactivated') . ' successfully.'
            );
    }
}
