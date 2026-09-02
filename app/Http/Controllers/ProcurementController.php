<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProcurementRequest;
use App\Models\Procurement;
use App\Models\ProcurementMode;
use App\Models\ProcurementModePhase;
use App\Models\{User, Office};
use App\Models\ProcurementDocument;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProcurementController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $offices = Office::where('is_active', true)->get();
        $modes = ProcurementMode::where('is_active', true)->get();

        return view('procurements.index', compact('offices', 'modes'));
    }

    public function create()
    {
        $offices = Office::where('is_active', true)->get();
        $modes = ProcurementMode::where('is_active', true)->get();

        return view('procurements.create', compact('offices', 'modes'));
    }

    public function edit(Procurement $procurement)
    {
        $offices = Office::where('is_active', true)->get();
        $modes = ProcurementMode::where('is_active', true)->get();

        return view('procurements.edit', compact('offices', 'modes', 'procurement'));
    }

    /**
     * Update the procurement project and line items.
     */
    public function update(Request $request, Procurement $procurement)
    {
        $this->authorize('update', $procurement);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'end_user_office_id' => 'required|exists:offices,id',
            'procurement_mode_id' => 'required|exists:procurement_modes,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:procurement_items,id',
            'items.*.description' => 'required|string|max:1000',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_of_measure' => 'required|string|max:50',
            'items.*.unit_cost' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($validated, $procurement) {
            // 1. Update general metadata
            $procurement->update([
                'title' => $validated['title'],
                'end_user_office_id' => $validated['end_user_office_id'],
                'procurement_mode_id' => $validated['procurement_mode_id'],
            ]);

            // 2. Sync line items
            $submittedIds = collect($validated['items'])->pluck('id')->filter()->toArray();
            $procurement->items()->whereNotIn('id', $submittedIds)->delete();

            foreach ($validated['items'] as $itemData) {
                $procurement->items()->updateOrCreate(
                    ['id' => $itemData['id'] ?? null],
                    [
                        'description' => $itemData['description'],
                        'quantity' => $itemData['quantity'],
                        'unit_of_measure' => $itemData['unit_of_measure'],
                        'unit_cost' => $itemData['unit_cost'],
                        'total_cost' => $itemData['quantity'] * $itemData['unit_cost'],
                    ]
                );
            }

            // 3. Recompute and force-save total ABC from database items
            $procurement->abc_amount = $procurement->items()->sum('total_cost');
            $procurement->save();
        });

        return redirect()
            ->route('procurements.show', $procurement->id)
            ->with('success', 'Purchase Request updated successfully.');
    }

    public function store(StoreProcurementRequest $request)
    {
        $user = $request->user();

        $procurement = DB::transaction(function () use ($request, $user) {
            $mode = ProcurementMode::findOrFail($request->input('procurement_mode_id'));
            $firstPhase = $mode->phases()->orderBy('phase_sequence', 'asc')->first();

            // Calculate total ABC
            $itemsData = $request->input('items');
            $totalAbc = 0;
            foreach ($itemsData as $item) {
                $totalAbc += $item['quantity'] * $item['unit_cost'];
            }

            $trackingNumber = Setting::getNextPrNumberAndIncrement();

            $procurement = Procurement::create([
                'tracking_number' => $trackingNumber,
                'title' => $request->input('title'),
                'end_user_office_id' => $request->input('end_user_office_id'),
                'procurement_mode_id' => $mode->id,
                'current_phase_id' => $firstPhase?->id,
                'abc_amount' => $totalAbc,
                'status' => 'DRAFT',
                'created_by' => $user->id,
            ]);

            // Save Line Items
            foreach ($itemsData as $index => $item) {
                $procurement->items()->create([
                    'item_code' => 'ITEM-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_of_measure' => $item['unit_of_measure'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $item['quantity'] * $item['unit_cost'],
                ]);
            }

            return $procurement;
        });

        return redirect()->route('procurements.show', $procurement->id)
            ->with('success', "Purchase Request {$procurement->tracking_number} created successfully.");
    }

    public function show(Procurement $procurement)
    {
        $procurement->load([
            'endUserOffice',
            'mode.phases.roles',
            'currentPhase',
            'currentPhase.documents',
            'currentPhase.roles',
            'items',
            'documents',
            'documents.uploader',
            'routes.fromOffice',
            'routes.toOffice',
            'history.user'
        ]);

        $nextPhase = $procurement->getNextPhase();
        $nextPhaseTargetOffices = $procurement->getNextPhaseTargetOffices();
        $nextPhaseTargetUsers = $procurement->getNextPhaseTargetUsers();

        return view('procurements.show', compact(
            'procurement',
            'nextPhase',
            'nextPhaseTargetOffices',
            'nextPhaseTargetUsers'
        ));
    }

    public function uploadDocument(Request $request, Procurement $procurement)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,jpeg,png', 'max:10240'],
            'phase_id' => ['required', 'exists:procurement_mode_phases,id'],
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $filePath = $file->storeAs("procurements/{$procurement->id}", time() . '_' . $fileName, 'public');

        ProcurementDocument::create([
            'procurement_id' => $procurement->id,
            'phase_id' => $request->input('phase_id'),
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Phase document attached successfully.');
    }

    public function returnTargetOffices(ProcurementModePhase $phase)
    {
        // Get roles authorized for this phase
        $roleIds = $phase->roles()
            ->pluck('roles.id');

        // No roles configured for this phase
        if ($roleIds->isEmpty()) {
            return response()->json([
                'offices' => [],
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | Find offices that have at least one user who:
    | 1. Belongs to the office
    | 2. Has one of the authorized phase roles
    |--------------------------------------------------------------------------
    */

        $offices = Office::query()
            ->where('is_active', true)
            ->whereHas('users', function ($query) use ($roleIds) {
                $query->whereHas('roles', function ($roleQuery) use ($roleIds) {
                    $roleQuery->whereIn('roles.id', $roleIds);
                });
            })
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
                'location_type',
            ]);

        return response()->json([
            'offices' => $offices,
        ]);
    }


    public function returnTargetUsers(
        ProcurementModePhase $phase,
        Office $office
    ) {
        /*
    |--------------------------------------------------------------------------
    | Get roles authorized for the selected return phase
    |--------------------------------------------------------------------------
    */

        $roleIds = $phase->roles()
            ->pluck('roles.id');

        // No roles configured for this phase
        if ($roleIds->isEmpty()) {
            return response()->json([
                'users' => [],
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | Find users who:
    |
    | 1. Belong to the selected office
    | 2. Have at least one role authorized for this phase
    |--------------------------------------------------------------------------
    */

        $users = User::query()
            ->whereHas('offices', function ($query) use ($office) {
                $query->where('offices.id', $office->id);
            })
            ->whereHas('roles', function ($query) use ($roleIds) {
                $query->whereIn('roles.id', $roleIds);
            })
            ->with([
                'roles' => function ($query) use ($roleIds) {
                    $query
                        ->whereIn('roles.id', $roleIds)
                        ->select([
                            'roles.id',
                            'roles.name',
                        ]);
                },
            ])
            ->orderBy('name')
            ->get([
                'users.id',
                'users.name',
            ]);

        /*
    |--------------------------------------------------------------------------
    | Return only matching phase roles
    |--------------------------------------------------------------------------
    */

        return response()->json([
            'users' => $users->map(function ($user) {
                return [
                    'id'   => $user->id,
                    'name' => $user->name,

                    // Only roles authorized for the selected phase
                    'role' => $user->roles
                        ->pluck('name')
                        ->implode(', '),
                ];
            })->values(),
        ]);
    }
}
