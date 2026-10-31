@extends('layouts.app')
@section('title', 'Add Supplier')
@section('page-header')
<div>@include('partials.page-back-link', ['href' => route('suppliers.index'), 'label' => 'Supplier directory', 'discardTitle' => 'Leave supplier creation?', 'discardMessage' => 'Any supplier details you entered will be discarded.'])<h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">Add supplier profile</h1><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Enter the supplier's business and registration details.</p></div>
@endsection
@section('content')
<div class="mx-auto max-w-4xl">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800 sm:px-7"><h2 class="text-sm font-extrabold text-slate-900 dark:text-white">Supplier details</h2><p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Fields marked with an asterisk are required.</p></div>
        <div class="p-5 sm:p-7">
        @include('suppliers._form', ['supplier' => new \App\Models\Procurement\Supplier()])
        </div>
    </section>
</div>
@endsection
