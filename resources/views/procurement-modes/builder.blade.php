@extends('layouts.app')

@section('title', 'Workflow Builder - ' . $mode->code)

@section('page-header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2">
            <span class="font-mono text-lg font-extrabold text-indigo-600 dark:text-indigo-400">{{ $mode->code }}</span>
            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 border border-slate-200 dark:border-slate-700">
                Cap: {{ $mode->max_abc ? '₱ ' . number_format($mode->max_abc, 2) : 'Uncapped' }}
            </span>
        </div>
        <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 mt-1">{{ $mode->name }} — Workflow Designer</h1>
    </div>
    
    <a href="{{ route('procurement-modes.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold hover:bg-slate-300 transition">
        <i class="fa-solid fa-arrow-left"></i> Back to Registry
    </a>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Left Column: Add New Phase Form -->
    <div class="space-y-6">
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-2">Add Workflow Phase Step</h2>

            <form action="{{ route('procurement-phases.store', $mode->id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Sequence Index <span class="text-rose-500">*</span></label>
                    <input type="number" name="phase_sequence" min="1" value="{{ $mode->phases->count() + 1 }}" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Phase Title <span class="text-rose-500">*</span></label>
                    <input type="text" name="phase_name" required placeholder="e.g. Pre-Procurement Conference" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Target SLA Turnaround (Days) <span class="text-rose-500">*</span></label>
                    <input type="number" name="allowable_days" min="1" max="60" value="7" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600">
                    <p class="text-[10px] text-slate-400 mt-1">Must remain within the RA 12009 60-day statutory ceiling.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Phase Operational Description</label>
                    <textarea name="description" rows="2" placeholder="Instructions or procedural rules for this step..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-600"></textarea>
                </div>

                <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-indigo-500/20 transition">
                    <i class="fa-solid fa-plus mr-1"></i> Add Phase Step
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Configured Phase Cards -->
    <div class="lg:col-span-2 space-y-6">
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-2">Configured Sequence Cards</h2>

            <div class="space-y-4">
                @forelse($mode->phases as $phase)
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/80 space-y-3">
                    
                    <!-- Phase Header -->
                    <div class="flex items-center justify-between border-b border-slate-200/60 dark:border-slate-700/60 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-bold text-xs flex items-center justify-center font-mono">
                                {{ $phase->phase_sequence }}
                            </span>
                            <h3 class="font-bold text-sm text-slate-800 dark:text-slate-200">{{ $phase->phase_name }}</h3>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200">
                                SLA: {{ $phase->allowable_days }} Days
                            </span>
                            
                            <form action="{{ route('procurement-phases.destroy', [$mode->id, $phase->id]) }}" method="POST" onsubmit="return confirm('Delete this phase step?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs p-1" title="Delete Step"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </div>

                    <!-- Roles & Documents Assignment Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs pt-1">
                        
                        <!-- Authorized Roles List -->
                        <div class="p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 space-y-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Authorized Phase Roles:</span>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse($phase->roles as $role)
                                    <span class="px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-[10px] font-semibold border border-indigo-200/60">
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    <span class="text-[10px] text-slate-400 italic">All roles authorized</span>
                                @endforelse
                            </div>

                            <!-- Sync Roles Form -->
                            <form action="{{ route('procurement-phases.sync-roles', [$mode->id, $phase->id]) }}" method="POST" class="pt-2 border-t border-slate-100 dark:border-slate-800">
                                @csrf
                                <div class="flex flex-wrap gap-2 text-[10px]">
                                    @foreach($roles as $r)
                                    <label class="inline-flex items-center gap-1 cursor-pointer">
                                        <input type="checkbox" name="role_ids[]" value="{{ $r->id }}" {{ $phase->roles->contains($r->id) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span>{{ $r->name }}</span>
                                    </label>
                                    @endforeach
                                </div>
                                <button type="submit" class="mt-2 text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Update Roles</button>
                            </form>
                        </div>

                        <!-- Mandatory Document Requirements -->
                        <div class="p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 space-y-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Required Phase Documents:</span>
                            
                            <ul class="space-y-1">
                                @forelse($phase->documents as $docReq)
                                <li class="flex items-center justify-between text-[11px]">
                                    <span class="truncate font-medium text-slate-700 dark:text-slate-300">• {{ $docReq->document_type_name }}</span>
                                    <form action="{{ route('procurement-phases.documents.destroy', [$mode->id, $phase->id, $docReq->id]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-500 text-[10px] hover:underline">Remove</button>
                                    </form>
                                </li>
                                @empty
                                <li class="text-[10px] text-slate-400 italic">No document requirements set.</li>
                                @endforelse
                            </ul>

                            <!-- Add Document Requirement Form -->
                            <form action="{{ route('procurement-phases.documents.store', [$mode->id, $phase->id]) }}" method="POST" class="pt-2 border-t border-slate-100 dark:border-slate-800 flex gap-2">
                                @csrf
                                <input type="text" name="document_type_name" required placeholder="New required doc name..." class="flex-1 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded px-2 py-1 text-[10px]">
                                <input type="hidden" name="is_mandatory" value="1">
                                <button type="submit" class="px-2 py-1 bg-indigo-600 text-white rounded text-[10px] font-bold">Add</button>
                            </form>
                        </div>

                    </div>

                </div>
                @empty
                <p class="text-center text-slate-400 text-xs py-8">No workflow phases configured for {{ $mode->code }} yet.</p>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection