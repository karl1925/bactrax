@extends('layouts.app')

@section('title', 'Edit Item Bank Entry')

@section('page-header')
    <div>@include('partials.page-back-link', ['href' => route('item-bank.index'), 'label' => 'Item Bank', 'discardTitle' => 'Leave item editing?', 'discardMessage' => 'Any unsaved item bank changes will be discarded.'])<h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">Edit item bank entry</h1><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Update this reusable catalog item and its reference details.</p></div>
@endsection

@section('content')
    <div class="mx-auto max-w-3xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800 sm:px-7"><h2 class="text-sm font-extrabold text-slate-900 dark:text-white">Catalog details</h2></div>
        <div class="p-5 sm:p-7">@include('item-bank._form')</div>
    </div>
@endsection
