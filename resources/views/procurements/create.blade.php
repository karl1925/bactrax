@extends('layouts.app')

@section('title', 'Create Purchase Request')

@section('page-header')
<div>
    <a
        href="{{ route('procurements.index') }}"
        class="inline-flex items-center gap-1.5 mb-2 text-xs font-semibold text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition"
    >
        <i class="fa-solid fa-arrow-left text-[10px]"></i>
        Back to Directory
    </a>

    <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">
        Create Purchase Request
    </h1>

    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
        Initiate new project under RA 12009 statutory rules
    </p>
</div>
@endsection

@section('content')
<form action="{{ route('procurements.store') }}" method="POST" class="space-y-6">
    @csrf

    <!-- General Project Information -->
    <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
        <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-2">Project Details</h2>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Project Title <span class="text-rose-500">*</span></label>
            <input type="text" name="title" required value="{{ old('title') }}" placeholder="e.g. Procurement of High-Capacity Network Switches" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">End-User Office <span class="text-rose-500">*</span></label>
                
                    @php
                        $primaryOfficeId = Auth::user()->primaryOffice()?->id;
                    @endphp
                    <select name="end_user_office_id" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
                    <option value="">Select Office</option>
                    
                    @foreach(Auth::user()->offices as $office)
                        <option value="{{ $office->id }}"
                            {{ old('end_user_office_id', $primaryOfficeId) == $office->id ? 'selected' : '' }}>
                            {{ $office->name }} ({{ $office->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Procurement Mode <span class="text-rose-500">*</span></label>
                <select name="procurement_mode_id" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
                    <option value="">Select Modality</option>
                    @foreach($modes as $mode)
                        <option value="{{ $mode->id }}" {{ old('procurement_mode_id') == $mode->id ? 'selected' : '' }}>
                            {{ $mode->name }} (Cap: {{ $mode->max_abc ? '₱ ' . number_format($mode->max_abc, 2) : 'Uncapped' }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Line Items Breakdown -->
    <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Itemized Breakdown</h2>
            <button type="button" id="add-item-btn" class="px-3 py-1 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 rounded-lg text-xs font-semibold transition">
                <i class="fa-solid fa-plus mr-1"></i> Add Line Item
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                <thead>
                    <tr class="text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <th class="py-2 px-2">Item Description</th>
                        <th class="py-2 px-2 w-24">Qty</th>
                        <th class="py-2 px-2 w-28">Unit</th>
                        <th class="py-2 px-2 w-36">Unit Price (₱)</th>
                        <th class="py-2 px-2 w-36 text-right">Extended Cost</th>
                        <th class="py-2 px-2 w-12 text-center"></th>
                    </tr>
                </thead>
                <tbody id="items-container" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr class="item-row">
                        <td class="p-2"><input type="text" name="items[0][description]" required placeholder="Item specifications..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-100 outline-none"></td>
                        <td class="p-2"><input type="number" name="items[0][quantity]" min="1" value="1" required class="item-qty w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-100 outline-none"></td>
                        <td class="p-2"><input type="text" name="items[0][unit_of_measure]" required placeholder="unit / lot" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-100 outline-none"></td>
                        <td class="p-2"><input type="number" step="0.01" min="0.01" name="items[0][unit_cost]" required placeholder="0.00" class="item-cost w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-100 outline-none"></td>
                        <td class="p-2 text-right font-mono font-bold item-total">₱ 0.00</td>
                        <td class="p-2 text-center"><button type="button" class="remove-row-btn text-rose-500 hover:text-rose-700"><i class="fa-solid fa-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
            <div class="text-right">
                <span class="text-xs text-slate-500 uppercase font-bold tracking-wider">Calculated Total ABC:</span>
                <span id="grand-total" class="block text-xl font-extrabold font-mono text-indigo-600 dark:text-indigo-400">₱ 0.00</span>
            </div>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="flex justify-end gap-3">
        <a href="{{ route('procurements.index') }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold hover:bg-slate-300 transition">Cancel</a>
        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-500/20 transition">Next</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let itemIndex = 1;

        function calculateTotals() {
            let grandTotal = 0;
            $('.item-row').each(function() {
                let qty = parseFloat($(this).find('.item-qty').val()) || 0;
                let cost = parseFloat($(this).find('.item-cost').val()) || 0;
                let lineTotal = qty * cost;
                $(this).find('.item-total').text('₱ ' + lineTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                grandTotal += lineTotal;
            });
            $('#grand-total').text('₱ ' + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        }

        $(document).on('input', '.item-qty, .item-cost', calculateTotals);

        $('#add-item-btn').on('click', function() {
            let rowHtml = `
                <tr class="item-row">
                    <td class="p-2"><input type="text" name="items[${itemIndex}][description]" required placeholder="Item specifications..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-100 outline-none"></td>
                    <td class="p-2"><input type="number" name="items[${itemIndex}][quantity]" min="1" value="1" required class="item-qty w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-100 outline-none"></td>
                    <td class="p-2"><input type="text" name="items[${itemIndex}][unit_of_measure]" required placeholder="unit / lot" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-100 outline-none"></td>
                    <td class="p-2"><input type="number" step="0.01" min="0.01" name="items[${itemIndex}][unit_cost]" required placeholder="0.00" class="item-cost w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-100 outline-none"></td>
                    <td class="p-2 text-right font-mono font-bold item-total">₱ 0.00</td>
                    <td class="p-2 text-center"><button type="button" class="remove-row-btn text-rose-500 hover:text-rose-700"><i class="fa-solid fa-trash"></i></button></td>
                </tr>`;
            $('#items-container').append(rowHtml);
            itemIndex++;
        });

        $(document).on('click', '.remove-row-btn', function() {
            if ($('.item-row').length > 1) {
                $(this).closest('tr').remove();
                calculateTotals();
            }
        });
    });
</script>
@endpush