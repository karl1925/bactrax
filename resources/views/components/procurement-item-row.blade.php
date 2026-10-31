@props([
    'groupIndex' => 0,
    'subIndex' => null,
    'lineNo' => 1,
    'item' => [],
    'isLot' => false
])

@php
    $description = $item['description'] ?? '';
    $quantity = $item['quantity'] ?? 1;
    $cost = $item['cost'] ?? '';
    $unit = $item['unit'] ?? '';
    $isSub = !is_null($subIndex);
    $inputClass = 'w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-100';
    $namePrefix = $isSub 
        ? "items[{$groupIndex}][sub_items][{$subIndex}]" 
        : "items[{$groupIndex}]";
@endphp

<tr class="{{ $isSub ? 'lot-sub-item bg-slate-50/20 dark:bg-slate-800/10' : 'item-row' }} draggable-row border-b border-slate-100 dark:border-slate-800/50" draggable="true">
    <td class="line-no-cell p-2 text-center relative">
        <div class="flex items-center justify-center gap-1.5">
            <button type="button" class="drag-handle" title="Drag row to reorder" aria-label="Drag row to reorder">
                <i class="fa-solid fa-grip-vertical"></i>
            </button>
            <span class="line-no">{{ $lineNo }}</span>
        </div>
        <input type="hidden" name="{{ $namePrefix }}[line_no]" value="{{ $lineNo }}" data-field="line_no" class="line-no-input">
        @if(!$isSub)
            <input type="hidden" name="items[{{ $groupIndex }}][is_lot]" value="{{ $isLot ? 1 : 0 }}" data-field="is_lot">
        @endif
    </td>

    @if(!$isSub && !$isLot)
        <td class="p-2">
            <input type="text" name="items[{{ $groupIndex }}][unit]" value="{{ $unit }}" data-field="unit" required placeholder="e.g. Pcs" class="{{ $inputClass }}">
        </td>
    @endif

    <td class="p-2">
        <input type="text" name="{{ $namePrefix }}[description]" value="{{ $description }}" data-field="description" required placeholder="{{ $isSub ? 'Lot item specification...' : 'Item specifications...' }}" class="{{ $inputClass }}">
    </td>
    <td class="p-2">
        <input type="number" name="{{ $namePrefix }}[quantity]" value="{{ $quantity }}" data-field="quantity" required min="0.001" step="1" class="{{ $inputClass }} item-qty">
    </td>
    <td class="p-2">
        <input type="number" name="{{ $namePrefix }}[cost]" value="{{ $cost }}" data-field="cost" required min="0.01" step="0.01" placeholder="0.00" class="{{ $inputClass }} item-cost">
    </td>
    <td class="p-2 item-total-cell">
        <span class="item-total">₱ {{ number_format(((float)$quantity) * ((float)$cost), 2) }}</span>
        <button type="button" class="item-menu-toggle" aria-label="Line item actions" aria-haspopup="menu" aria-expanded="false" title="Line item actions">
            <i class="fa-solid fa-ellipsis-vertical"></i>
        </button>
    </td>
</tr>