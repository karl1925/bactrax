@extends('layouts.app')

@section('title', 'Form CMS')

@section('page-header')
    @include('partials.page-back-link', ['href' => route('dashboard'), 'label' => 'Dashboard'])
    <div>
        <h1 class="flex items-center gap-2 text-xl font-bold text-slate-800 dark:text-slate-100">
            <i class="fa-solid fa-code text-blue-600 dark:text-blue-400"></i> Form CMS
        </h1>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Manage shared HTML sections inserted into forms that use the matching section markers.</p>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.form-cms.update') }}" class="mx-auto max-w-5xl space-y-6">
        @csrf
        @method('PUT')

        @foreach([
            'forms_header' => ['Shared Forms Header', 'Inserted before the title in <section data-header data-title="..."></section>. The title is supplied by each form.'],
            'rfq_notice' => ['RFQ Notice', 'Inserted into <section data-rfq-notice></section>.'],
            'rfq_instructions' => ['RFQ Instructions', 'Inserted into <section data-rfq-instructions></section>.'],
            'rfq_terms' => ['RFQ Terms and Conditions', 'Inserted into <section data-rfq-terms></section>.'],
        ] as $key => [$label, $help])
            <section class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div>
                    <label for="{{ $key }}" class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $label }}</label>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $help }} HTML is accepted; unsafe elements and attributes are removed when the form renders.</p>
                </div>
                <textarea id="{{ $key }}" name="{{ $key }}" rows="{{ $key === 'forms_header' ? 8 : 14 }}" maxlength="100000"
                    class="w-full rounded-xl border border-slate-300 bg-slate-50 p-3 font-mono text-xs text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">{{ old($key, $content[$key] ?? '') }}</textarea>
                @error($key)<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </section>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white transition hover:bg-blue-700">
                <i class="fa-solid fa-floppy-disk mr-2"></i>Save Form Content
            </button>
        </div>
    </form>
@endsection
