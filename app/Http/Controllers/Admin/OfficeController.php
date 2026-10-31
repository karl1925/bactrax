<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOfficeRequest;
use App\Models\Office;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    public function index(Request $request)
    {
        $offices = Office::withCount(['users', 'procurements'])
            ->where('is_active', true)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%' . trim($request->search) . '%')->orWhere('code', 'like', '%' . trim($request->search) . '%')))
            ->when($request->filled('location_type'), fn ($q) => $q->where('location_type', $request->location_type))
            ->orderBy('location_type', 'asc')
            ->orderBy('code', 'asc')
            ->paginate(15)->withQueryString();

        return view('admin.offices.index', compact('offices'));
    }

    public function archivedIndex(Request $request)
    {
        $offices = Office::withCount(['users', 'procurements'])->where('is_active', false)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%' . trim($request->search) . '%')->orWhere('code', 'like', '%' . trim($request->search) . '%')))
            ->when($request->filled('location_type'), fn ($q) => $q->where('location_type', $request->location_type))
            ->orderBy('location_type')->orderBy('code')->paginate(15)->withQueryString();
        return view('admin.offices.archive', compact('offices'));
    }

    public function store(StoreOfficeRequest $request)
    {
        $office = Office::create($request->validated());

        return back()->with('success', "Office unit '{$office->name}' ({$office->code}) registered successfully.");
    }

    public function update(StoreOfficeRequest $request, Office $office)
    {
        $office->update($request->validated());

        return back()->with('success', "Office unit '{$office->code}' updated successfully.");
    }

    public function toggleStatus(Office $office)
    {
        $office->is_active = !$office->is_active;
        $office->save();

        $statusText = $office->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Office unit '{$office->code}' has been {$statusText}.");
    }
}
