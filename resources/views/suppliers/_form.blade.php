<form method="POST" action="{{ $supplier?->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}" data-unsaved-changes class="space-y-5">
    @csrf
    @if($supplier?->exists) @method('PUT') @endif
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach(['business_name'=>'Business name','business_type'=>'Business type','contact_person'=>'Contact person','email'=>'Email','phone'=>'Phone','tin'=>'TIN','philgeps_registration_number'=>'PhilGEPS registration number'] as $field=>$label)
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $label }} @if($field==='business_name') <span class="text-rose-500">*</span> @endif
                <input name="{{ $field }}" value="{{ old($field, $supplier?->$field) }}" @required($field==='business_name') type="{{ $field==='email' ? 'email' : 'text' }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs text-slate-800 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:ring-blue-950">
                @error($field)<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
            </label>
        @endforeach
        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 sm:col-span-2">Business address
            <textarea name="business_address" rows="2" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs text-slate-800 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:ring-blue-950">{{ old('business_address', $supplier?->business_address) }}</textarea>
        </label>
        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 sm:col-span-2">Notes
            <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs text-slate-800 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:ring-blue-950">{{ old('notes', $supplier?->notes) }}</textarea>
        </label>
    </div>
    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier?->is_active ?? true)) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">Active supplier</label>
    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><a href="{{ $supplier?->exists ? route('suppliers.show', $supplier) : route('suppliers.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</a><button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-xs font-extrabold text-white transition hover:bg-blue-800"><i class="fa-solid fa-check"></i>Save supplier</button></div>
</form>
