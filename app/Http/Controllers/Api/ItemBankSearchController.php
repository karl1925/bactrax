<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Procurement\ItemBankItem;
use App\Models\Procurement\ProcurementBidItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemBankSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || $user->hasPermission('procurement.add') || $user->hasPermission('procurement.view') || $user->hasPermission('items.view')), 403);

        $validated = $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $term = trim($validated['q'] ?? '');
        if (mb_strlen($term) < 2) {
            return response()->json(['bids' => [], 'manual' => []]);
        }

        $cutoff = now()->subMonths(5)->startOfDay();
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
        $roleIds = $user->roles()->pluck('roles.id');
        $officeIds = $user->offices()->pluck('offices.id');
        $central = $user->isAdmin() || $user->isCentralRole();

        $bidItems = ProcurementBidItem::query()
            ->with(['bid.supplier:id,business_name', 'bid.procurement:id,pr_number,end_user_office_id', 'procurementItem.catalogItem:id,description,unit'])
            ->whereNotNull('unit_price')
            ->whereHas('procurementItem.catalogItem', fn ($query) => $query
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->where('description', 'like', $like))
            ->whereHas('bid', function ($query) use ($cutoff, $central, $officeIds, $roleIds) {
                $query->whereIn('status', ['SUBMITTED', 'EVALUATED'])
                    ->whereNotNull('received_at')
                    ->where('received_at', '>=', $cutoff)
                    ->whereHas('procurement', function ($procurement) use ($central, $officeIds, $roleIds) {
                        if ($central) {
                            return;
                        }
                        $procurement->where(function ($scope) use ($officeIds, $roleIds) {
                            $scope->whereIn('end_user_office_id', $officeIds)
                                ->orWhereHas('mode.phases.roles', fn ($roles) => $roles
                                    ->whereIn('roles.id', $roleIds)
                                    ->where('procurement_mode_phase_roles.can_act', true));
                        });
                    });
            })
            ->orderByDesc('procurement_bid_items.created_at')
            ->limit(8)
            ->get()
            ->map(function (ProcurementBidItem $bidItem) {
                $catalogItem = $bidItem->procurementItem?->catalogItem;
                $bid = $bidItem->bid;

                return [
                    'id' => $catalogItem?->id,
                    'description' => $catalogItem?->description,
                    'unit' => $catalogItem?->unit,
                    'price' => $bidItem->unit_price,
                    'date' => $bid?->received_at?->toDateString(),
                    'source' => trim(implode(' · ', array_filter([
                        $bid?->supplier?->business_name,
                        $bid?->procurement?->pr_number ? 'PR '.$bid->procurement->pr_number : null,
                    ]))),
                ];
            })
            ->filter(fn ($item) => filled($item['id']) && filled($item['description']))
            ->values();

        $manualItems = ItemBankItem::query()
            ->where('is_active', true)
            ->whereNotNull('reference_date')
            ->where('reference_date', '>=', $cutoff->toDateString())
            ->where('description', 'like', $like)
            ->orderByDesc('reference_date')
            ->limit(8)
            ->get(['id', 'description', 'unit', 'reference_price', 'price_source', 'reference_date'])
            ->map(fn (ItemBankItem $item) => [
                'id' => $item->id,
                'description' => $item->description,
                'unit' => $item->unit,
                'price' => $item->reference_price,
                'date' => $item->reference_date?->toDateString(),
                'source' => $item->price_source,
            ]);

        return response()->json(['bids' => $bidItems, 'manual' => $manualItems]);
    }
}
