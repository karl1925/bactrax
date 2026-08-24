@extends('layouts.app')

@section('title', 'System Settings & Tracking Number Rules')

@section('page-header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <i class="fa-solid fa-gear text-indigo-600 dark:text-indigo-400"></i>
            System Settings & PR Number Rules
        </h1>

        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Configure automated Purchase Request tracking sequence formatting and counters
        </p>
    </div>
</div>
@endsection

@section('content')

<div
    class="max-w-2xl mx-auto space-y-6"
    x-data="{
        prefix: '{{ $settings['pr_prefix'] }}',
        nextNumber: {{ $settings['pr_next_number'] }},
        padding: {{ $settings['pr_number_padding'] }},

        get preview() {
            let padded = String(this.nextNumber).padStart(this.padding, '0');
            let year = '{{ date('Y') }}';

            return (this.prefix || 'PR-') + year + '-' + padded;
        }
    }"
>

    {{-- ============================================================
        LIVE PREVIEW
    ============================================================= --}}
    <div
        class="relative overflow-hidden rounded-2xl border
               border-indigo-200 dark:border-indigo-900/70
               bg-gradient-to-br
               from-indigo-50 via-white to-slate-50
               dark:from-indigo-950/70 dark:via-slate-950 dark:to-slate-900
               shadow-sm dark:shadow-black/20"
    >

        <div class="p-5 space-y-3">

            <div class="flex items-center gap-2">
                <span
                    class="flex h-7 w-7 items-center justify-center rounded-lg
                           bg-indigo-100 dark:bg-indigo-500/15
                           text-indigo-600 dark:text-indigo-400"
                >
                    <i class="fa-solid fa-eye text-xs"></i>
                </span>

                <span
                    class="text-[10px] font-bold uppercase tracking-widest
                           text-indigo-700 dark:text-indigo-300"
                >
                    Live Format Preview
                </span>
            </div>

            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3
                       rounded-xl border
                       border-slate-200 dark:border-slate-800
                       bg-white/70 dark:bg-slate-900/70
                       px-4 py-3"
            >
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                    Next PR Generated
                </span>

                <span
                    x-text="preview"
                    class="inline-flex items-center justify-center
                           font-mono text-lg sm:text-xl font-extrabold
                           text-indigo-700 dark:text-indigo-300
                           bg-indigo-50 dark:bg-indigo-950/60
                           px-3 py-1.5 rounded-lg
                           border border-indigo-200 dark:border-indigo-800/70"
                ></span>
            </div>

        </div>
    </div>


    {{-- ============================================================
        SETTINGS FORM
    ============================================================= --}}
    <div
        class="p-6
               bg-white dark:bg-slate-900
               rounded-2xl
               border border-slate-200 dark:border-slate-800
               shadow-sm dark:shadow-black/20"
    >

        <form
            action="{{ route('admin.settings.update') }}"
            method="POST"
            class="space-y-6"
        >
            @csrf


            {{-- Section Header --}}
            <div class="border-b border-slate-200 dark:border-slate-800 pb-4">

                <div class="flex items-start gap-3">

                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center
                               rounded-lg
                               bg-indigo-50 dark:bg-indigo-500/10
                               text-indigo-600 dark:text-indigo-400"
                    >
                        <i class="fa-solid fa-hashtag text-sm"></i>
                    </div>

                    <div>
                        <h2
                            class="text-xs font-bold uppercase tracking-wider
                                   text-slate-800 dark:text-slate-100"
                        >
                            Purchase Request Number Sequence Configuration
                        </h2>

                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            Format structure:
                            <code
                                class="font-mono font-semibold
                                       text-indigo-600 dark:text-indigo-400"
                            >PREFIX + YEAR + - + NUMBER</code>

                            <span class="text-slate-400 dark:text-slate-600 mx-1">
                                •
                            </span>

                            Example:
                            <code
                                class="font-mono font-semibold
                                       text-slate-700 dark:text-slate-300"
                            >PR-2026-00005</code>
                        </p>
                    </div>

                </div>

            </div>


            {{-- Prefix & Padding --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                {{-- Prefix --}}
                <div>

                    <label
                        for="pr_prefix"
                        class="block text-xs font-bold uppercase tracking-wider
                               text-slate-700 dark:text-slate-300 mb-1.5"
                    >
                        Tracking Number Prefix
                        <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="text"
                        id="pr_prefix"
                        name="pr_prefix"
                        x-model="prefix"
                        required
                        placeholder="PR-"
                        autocomplete="off"
                        class="w-full
                               bg-slate-50 dark:bg-slate-800/80
                               border border-slate-300 dark:border-slate-700
                               rounded-xl
                               px-3.5 py-2.5
                               text-xs font-mono font-bold
                               text-slate-800 dark:text-slate-100
                               placeholder:text-slate-400 dark:placeholder:text-slate-600
                               uppercase
                               outline-none
                               transition
                               focus:bg-white dark:focus:bg-slate-800
                               focus:border-indigo-500 dark:focus:border-indigo-400
                               focus:ring-2 focus:ring-indigo-500/20
                               dark:focus:ring-indigo-400/20"
                    >

                    <p class="text-[10px] text-slate-500 dark:text-slate-500 mt-1.5">
                        Appended before the year, e.g. <span class="font-mono">PR-</span>.
                    </p>

                </div>


                {{-- Padding --}}
                <div>

                    <label
                        for="pr_number_padding"
                        class="block text-xs font-bold uppercase tracking-wider
                               text-slate-700 dark:text-slate-300 mb-1.5"
                    >
                        Zero Padding Digits
                        <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="number"
                        min="1"
                        max="10"
                        id="pr_number_padding"
                        name="pr_number_padding"
                        x-model.number="padding"
                        required
                        class="w-full
                               bg-slate-50 dark:bg-slate-800/80
                               border border-slate-300 dark:border-slate-700
                               rounded-xl
                               px-3.5 py-2.5
                               text-xs font-mono font-bold
                               text-slate-800 dark:text-slate-100
                               outline-none
                               transition
                               focus:bg-white dark:focus:bg-slate-800
                               focus:border-indigo-500 dark:focus:border-indigo-400
                               focus:ring-2 focus:ring-indigo-500/20
                               dark:focus:ring-indigo-400/20"
                    >

                    <p class="text-[10px] text-slate-500 dark:text-slate-500 mt-1.5">
                        5 digits produces
                        <span class="font-mono font-semibold text-slate-600 dark:text-slate-400">
                            00005
                        </span>.
                    </p>

                </div>

            </div>


            {{-- Next Counter --}}
            <div>

                <label
                    for="pr_next_number"
                    class="block text-xs font-bold uppercase tracking-wider
                           text-slate-700 dark:text-slate-300 mb-1.5"
                >
                    Next Sequence Counter Value
                    <span class="text-rose-500">*</span>
                </label>

                <input
                    type="number"
                    min="1"
                    id="pr_next_number"
                    name="pr_next_number"
                    x-model.number="nextNumber"
                    required
                    class="w-full
                           bg-slate-50 dark:bg-slate-800/80
                           border border-slate-300 dark:border-slate-700
                           rounded-xl
                           px-3.5 py-2.5
                           text-xs font-mono font-bold
                           text-slate-800 dark:text-slate-100
                           outline-none
                           transition
                           focus:bg-white dark:focus:bg-slate-800
                           focus:border-indigo-500 dark:focus:border-indigo-400
                           focus:ring-2 focus:ring-indigo-500/20
                           dark:focus:ring-indigo-400/20"
                >

                <p class="text-[10px] text-slate-500 dark:text-slate-500 mt-1.5">
                    The next available sequence number to be generated.
                </p>

            </div>


            {{-- Submit --}}
            <div
                class="flex items-center justify-end gap-3
                       pt-4
                       border-t border-slate-200 dark:border-slate-800"
            >

                <button
                    type="submit"
                    class="inline-flex items-center gap-2
                           px-5 py-2.5
                           bg-indigo-600 hover:bg-indigo-700
                           dark:bg-indigo-500 dark:hover:bg-indigo-600
                           text-white
                           rounded-xl
                           text-xs font-semibold
                           shadow-sm shadow-indigo-500/20
                           hover:shadow-md hover:shadow-indigo-500/25
                           transition-all duration-200
                           focus:outline-none
                           focus:ring-2 focus:ring-indigo-500/30"
                >
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Settings
                </button>

            </div>

        </form>

    </div>

</div>

@endsection