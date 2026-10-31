<?php

namespace App\Http\Controllers;

use App\Models\Procurement\{ItemBankItem, ProcurementItem};
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ItemBankController extends Controller
{
    public function index(Request $request): View
    {
        $items = ItemBankItem::query()
            ->where('is_active', true)
            ->withCount('procurementItems')
            ->withCount(['historicalBidItems as price_source_count'])
            ->with(['historicalBidItems' => function ($query) {
                $query->with(['bid.supplier', 'bid.procurement', 'procurementItem'])
                    ->orderByDesc('procurement_bid_items.created_at')
                    ->limit(3);
            }])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('description', 'like', '%'.trim($request->search).'%')->orWhere('item_code', 'like', '%'.trim($request->search).'%')->orWhere('category', 'like', '%'.trim($request->search).'%')))
            ->orderBy('description')->paginate(20)->withQueryString();

        $archivedCount = ItemBankItem::query()->where('is_active', false)->count();

        return view('item-bank.index', compact('items', 'archivedCount'));
    }

    public function archivedIndex(Request $request): View
    {
        $items = ItemBankItem::query()
            ->where('is_active', false)
            ->withCount('procurementItems')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('description', 'like', '%'.trim($request->search).'%')->orWhere('item_code', 'like', '%'.trim($request->search).'%')->orWhere('category', 'like', '%'.trim($request->search).'%')))
            ->orderBy('description')->paginate(20)->withQueryString();

        return view('item-bank.archive', compact('items'));
    }

    public function toggleActive(ItemBankItem $item): RedirectResponse
    {
        $item->update(['is_active' => ! $item->is_active]);

        return back()->with('success', $item->is_active
            ? 'Item bank entry restored to the active catalog.'
            : 'Item bank entry moved to the archive.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        if ($duplicate = $this->findDuplicate($data)) {
            return redirect()->route('item-bank.edit', $duplicate)
                ->with('error', 'An identical catalog entry already exists. Review it before creating another record.');
        }

        $item = DB::transaction(function () use ($data, $request) {
            $item = ItemBankItem::create($data + ['created_by' => $request->user()->id]);
            $this->linkExactUnmatchedProcurementLines($item);

            return $item;
        });

        return back()->with('success', "{$item->description} added to the item bank.");
    }

    public function edit(ItemBankItem $item): View
    {
        return view('item-bank.edit', compact('item'));
    }

    public function update(Request $request, ItemBankItem $item): RedirectResponse
    {
        $data = $this->validated($request);
        if ($duplicate = $this->findDuplicate($data, $item->id)) {
            return redirect()->route('item-bank.edit', $duplicate)
                ->with('error', 'Another identical catalog entry already exists. Review it before saving this change.');
        }

        DB::transaction(function () use ($item, $data) {
            $item->update($data);
            $this->linkExactUnmatchedProcurementLines($item);
        });

        return back()->with('success', 'Item bank entry updated.');
    }

    public function destroy(ItemBankItem $item): RedirectResponse
    {
        if ($item->procurementItems()->exists()) {
            return back()->with('error', 'This catalog item is linked to procurement history and cannot be removed. Deactivate it instead.');
        }

        $item->delete();

        return back()->with('success', 'Item bank entry removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'item_code' => ['nullable', 'string', 'max:64', Rule::unique('item_bank_items', 'item_code')->ignore($request->route('item')?->id)],
            'description' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:64'],
            'reference_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'price_source' => ['nullable', 'required_with:reference_price', 'string', 'max:255'],
            'reference_date' => ['required', 'date'],
            'specifications' => ['nullable', 'string', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }

    private function findDuplicate(array $data, ?int $exceptId = null): ?ItemBankItem
    {
        $query = ItemBankItem::query()
            ->whereRaw('LOWER(TRIM(description)) = ?', [mb_strtolower(trim($data['description']))])
            ->whereRaw("LOWER(TRIM(COALESCE(unit, ''))) = ?", [mb_strtolower(trim((string) ($data['unit'] ?? '')))])
            ->whereRaw("LOWER(TRIM(COALESCE(specifications, ''))) = ?", [mb_strtolower(trim((string) ($data['specifications'] ?? '')))]);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->first();
    }

    /** Link existing identical procurement lines to this one canonical catalog entry. */
    private function linkExactUnmatchedProcurementLines(ItemBankItem $item): void
    {
        if (! $item->is_active) {
            return;
        }

        $description = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $item->description) ?? $item->description));
        $unit = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $item->unit) ?? (string) $item->unit));
        $query = ProcurementItem::query()
            ->whereNull('item_bank_item_id')
            ->where('is_lot', false)
            ->whereRaw('LOWER(TRIM(description)) = ?', [$description]);

        if ($unit === '') {
            $query->where(function ($query) {
                $query->whereNull('unit')->orWhereRaw("TRIM(unit) = ''");
            });
        } else {
            $query->whereRaw('LOWER(TRIM(unit)) = ?', [$unit]);
        }

        $query->update(['item_bank_item_id' => $item->id]);
    }
}
