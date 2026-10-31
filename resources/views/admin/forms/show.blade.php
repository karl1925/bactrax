@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6 space-y-4">
    <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-gradient-to-br from-white to-blue-50 p-5 shadow-sm dark:border-slate-800 dark:from-slate-900 dark:to-blue-950/30 sm:flex-row sm:items-center sm:justify-between sm:rounded-3xl sm:p-6">
        <div class="min-w-0"><a href="{{ route('admin.forms.index') }}" class="mb-3 inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-blue-700 dark:text-slate-400 dark:hover:text-blue-300"><i class="fa-solid fa-arrow-left"></i> Back to Form Templates</a><h1 class="break-words text-xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-2xl">{{ $form->name }}</h1><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Template preview <span class="mx-1">·</span><span class="font-mono">{{ $form->code }}</span></p></div>
        <a href="{{ route('admin.forms.edit', $form) }}" class="inline-flex w-fit items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-xs font-extrabold text-white shadow-sm hover:bg-blue-800"><i class="fa-solid fa-pen"></i> Edit Template</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p><strong>Code:</strong> {{ $form->code }}</p>
            <p><strong>Status:</strong> <span class="capitalize">{{ $form->status }}</span></p>
            <p><strong>Page Size:</strong> {{ strtoupper($form->configuration['page_size'] ?? '-') }}</p>
            <p><strong>Margins:</strong> {{ $form->configuration['margins'] ?? '-' }}</p>
            @if($form->description)
                <p><strong>Description:</strong> {{ $form->description }}</p>
            @endif
        </div>

        <div class="h-[55vh] min-h-[24rem] rounded-2xl border border-slate-200 bg-white p-2 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:col-span-3 md:h-[600px]">
            <iframe src="{{ route('admin.forms.file', $form) }}" class="w-full h-full border-0"></iframe>
        </div>
    </div>
</div>
@endsection