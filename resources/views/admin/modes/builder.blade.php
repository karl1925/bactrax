@extends('layouts.app')

@section('title', 'Workflow Designer - ' . $mode->code)

@section('page-header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2">
            <span class="font-mono text-lg font-extrabold text-blue-600 dark:text-blue-400">{{ $mode->code }}</span>
            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                Cap: {{ $mode->max_abc ? '₱ ' . number_format($mode->max_abc, 2) : 'Uncapped' }}
            </span>
        </div>
        <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 mt-1">{{ $mode->name }} — Workflow Designer</h1>
    </div>
    
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.modes.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold hover:bg-slate-300 dark:hover:bg-slate-700 transition">
            <i class="fa-solid fa-arrow-left"></i> Back to Registry
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="workflowBuilder()" class="w-full space-y-8">

    <!-- ========================================== -->
    <!-- WORKFLOW PHASES & ATTACHMENT RULES TABLE   -->
    <!-- ========================================== -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        
        <!-- Table Header & Actions -->
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50 dark:bg-slate-800/40">
            <div>
                <h2 class="text-base font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i class="fa-solid fa-diagram-project text-blue-500"></i> Workflow Sequence & Phase Attachments
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Configure sequence phases, required upload/form attachments, and signatory workflows.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-900/50">
                    <span x-text="phases.length"></span> Configured Steps
                </span>
                <button @click="openPhaseCreateModal()" type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition">
                    <i class="fa-solid fa-plus"></i> Add Phase Step
                </button>
            </div>
        </div>

        <!-- Phases Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/80 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th scope="col" class="w-10 px-2 py-3 text-center"></th> <!-- Drag Handle -->
                        <th scope="col" class="w-16 px-4 py-3 text-center">Seq #</th>
                        <th scope="col" class="px-4 py-3 min-w-[180px]">Phase Title</th>
                        <th scope="col" class="w-28 px-4 py-3 text-center">Target SLA</th>
                        <th scope="col" class="px-4 py-3">Authorized Roles</th>
                        <th scope="col" class="px-4 py-3 min-w-[280px]">Required Attachments & Signatories</th>
                        <th scope="col" class="w-24 px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800 text-xs">
                    <template x-for="(phase, index) in phases" :key="phase.id">
                        <tr draggable="true"
                            @dragstart="onDragStart($event, index)"
                            @dragover.prevent="onDragOver($event, index)"
                            @dragleave="onDragLeave($event)"
                            @drop="onDrop($event, index)"
                            @dragend="onDragEnd()"
                            :class="{
                                'bg-blue-50/40 dark:bg-blue-950/20': draggedIndex === index,
                                'border-t-2 border-t-blue-600': dropTargetIndex === index && dragOverPosition === 'above',
                                'border-b-2 border-b-blue-600': dropTargetIndex === index && dragOverPosition === 'below'
                            }"
                            class="group hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                            
                            <!-- Drag Handle -->
                            <td class="px-2 py-3 text-center align-middle cursor-grab active:cursor-grabbing">
                                <i class="fa-solid fa-grip-vertical text-slate-300 dark:text-slate-600 group-hover:text-blue-500 transition-colors text-sm"></i>
                            </td>

                            <!-- Sequence Number -->
                            <td class="px-4 py-3 text-center align-middle font-mono font-bold text-slate-700 dark:text-slate-300">
                                <span class="w-7 h-7 inline-flex items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-blue-600 dark:text-blue-400 font-extrabold">
                                    <span x-text="index + 1"></span>
                                </span>
                            </td>

                            <!-- Phase Name & Tooltip -->
                            <td class="px-4 py-3 align-middle">
                                <div class="flex items-center gap-2">
                                    <span x-text="phase.phase_name" class="font-bold text-slate-800 dark:text-slate-200 text-xs"></span>
                                    
                                    <template x-if="phase.description">
                                        <div class="relative group/tooltip inline-block">
                                            <i class="fa-solid fa-circle-info text-slate-400 hover:text-blue-500 cursor-pointer text-xs"></i>
                                            <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 hidden group-hover/tooltip:block w-64 p-2.5 bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 text-[11px] rounded-lg shadow-xl z-30 pointer-events-none">
                                                <p x-text="phase.description" class="leading-normal"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </td>

                            <!-- Allowable SLA Days -->
                            <td class="px-4 py-3 text-center align-middle whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-mono font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-900/50 inline-flex items-center gap-1">
                                    <i class="fa-regular fa-clock text-[10px]"></i>
                                    <span x-text="phase.allowable_days"></span> Days
                                </span>
                            </td>

                            <!-- Assigned Roles -->
                            <td class="px-4 py-3 align-middle">
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="role in phase.roles" :key="role.id">
                                        <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 text-[10px] font-semibold border border-blue-200/60 dark:border-blue-900/40" x-text="role.name"></span>
                                    </template>
                                    <template x-if="!phase.roles || phase.roles.length === 0">
                                        <span class="text-[10px] text-slate-400 italic">All authorized</span>
                                    </template>
                                </div>
                            </td>

                            <!-- Required Phase Attachments (Forms / Uploads) -->
                            <td class="px-4 py-3 align-middle">
                                <div class="space-y-1.5">
                                    <template x-for="att in phase.attachments" :key="att.id">
                                        <div class="flex flex-col gap-0.5 p-1.5 rounded-lg bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-1.5 min-w-0">
                                                    <i class="fa-solid" :class="att.form_id ? 'fa-file-signature text-blue-500' : 'fa-file-arrow-up text-amber-500'"></i>
                                                    <span class="font-bold text-[11px] text-slate-700 dark:text-slate-200 truncate" x-text="att.name"></span>
                                                    <template x-if="att.form">
                                                        <span class="px-1.5 py-0.2 rounded bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 text-[9px] font-mono font-bold" x-text="att.form.code"></span>
                                                    </template>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <template x-if="att.is_mandatory">
                                                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300">Mandatory</span>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- Signatory Chain Preview -->
                                            <template x-if="att.signatories && att.signatories.length > 0">
                                                <div class="flex items-center gap-1 pt-1 border-t border-slate-200/60 dark:border-slate-700/60 overflow-x-auto">
                                                    <span class="text-[9px] text-slate-400 uppercase font-bold">Signatories:</span>
                                                    <template x-for="(sig, sIdx) in att.signatories" :key="sig.id || sIdx">
                                                        <span class="inline-flex items-center gap-1 text-[9px] px-1.5 py-0.2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded font-semibold text-slate-600 dark:text-slate-300">
                                                            <span x-text="`${sig.sequence}. ${sig.role ? sig.role.name : 'Role'}`"></span>
                                                        </span>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <template x-if="!phase.attachments || phase.attachments.length === 0">
                                        <span class="text-[10px] text-slate-400 italic">No attachments configured</span>
                                    </template>
                                </div>
                            </td>

                            <!-- Action Column -->
                            <td class="px-4 py-3 align-middle text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openPhaseEditModal(phase)" type="button" class="p-1.5 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800" title="Edit Step">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    
                                    <form :action="`/admin/modes/{{ $mode->id }}/phases/${phase.id}`" method="POST" data-confirmation data-confirm-title="Delete this phase step?" data-confirm-message="This phase and its configuration will be permanently deleted." data-confirm-eyebrow="Delete phase" data-confirm-button="Delete phase" data-confirm-method="POST" data-confirm-tone="rose" data-confirm-icon="fa-trash-can" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-500 transition-colors rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800" title="Delete Step">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <template x-if="phases.length === 0">
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                <i class="fa-solid fa-diagram-project text-3xl text-slate-300 dark:text-slate-600 mb-2"></i>
                                <p class="text-sm font-semibold">No phases configured.</p>
                                <p class="text-xs text-slate-400 mt-1">Click "Add Phase Step" above to configure sequence rules.</p>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>


    <!-- ========================================== -->
    <!-- COMPREHENSIVE PHASE & ATTACHMENTS MODAL    -->
    <!-- ========================================== -->
    <div x-show="isPhaseModalOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[120] overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="isPhaseModalOpen = false" 
             class="bg-white dark:bg-slate-900 rounded-2xl max-w-3xl w-full border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden transform transition-all my-8">
            
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid" :class="isPhaseEditMode ? 'fa-pen-to-square text-blue-500' : 'fa-plus text-blue-500'"></i>
                    <span x-text="isPhaseEditMode ? 'Edit Workflow Phase & Requirements' : 'Configure New Phase Step'"></span>
                </h3>
                <button @click="isPhaseModalOpen = false" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <form :action="phaseFormAction" method="POST" class="p-6 space-y-6">
                @csrf
                <template x-if="isPhaseEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- Phase General Settings -->
                <div class="space-y-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 border-b border-slate-100 dark:border-slate-800 pb-1">
                        1. Phase Rules & SLA
                    </h4>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Sequence <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" name="phase_sequence" min="1" x-model="phaseForm.phase_sequence" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-blue-600">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Phase Title <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="phase_name" x-model="phaseForm.phase_name" required placeholder="e.g. Market Survey & Technical Review" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-blue-600">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Target SLA (Days) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" name="allowable_days" min="1" max="60" x-model="phaseForm.allowable_days" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-blue-600">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Authorized Roles
                            </label>
                            <div class="flex flex-wrap gap-2 max-h-24 overflow-y-auto p-2 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700">
                                @foreach($roles as $role)
                                <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs text-slate-700 dark:text-slate-300 hover:text-blue-600">
                                    <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" x-model="phaseForm.role_ids" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span>{{ $role->name }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Description / Instructions Field -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Operational Instructions / Description
                        </label>
                        <textarea name="description" rows="2" x-model="phaseForm.description" placeholder="Provide operational guidelines, conditions, or regulatory rules for this step..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-blue-600 resize-y"></textarea>
                    </div>
                </div>

                <!-- Phase Required Attachments Management -->
                <div class="space-y-4 pt-2">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-1">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                            2. Required Attachments & Signatory Chains
                        </h4>
                        <button @click="addAttachmentRule()" type="button" class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 rounded-lg text-[11px] font-bold hover:bg-blue-100 transition">
                            <i class="fa-solid fa-plus"></i> Add Attachment
                        </button>
                    </div>

                    <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                        <template x-for="(att, aIdx) in phaseForm.attachments" :key="aIdx">
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                                    <div class="sm:col-span-5">
                                        <input type="hidden" :name="`attachments[${aIdx}][id]`" :value="att.id || ''">
                                        <input type="text" :name="`attachments[${aIdx}][name]`" x-model="att.name" required placeholder="Attachment Name (e.g. Market Survey)" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs">
                                    </div>
                                    <div class="sm:col-span-4">
                                        <select :name="`attachments[${aIdx}][form_id]`" x-model="att.form_id" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs">
                                            <option value="">-- File Upload (No Form) --</option>
                                            @foreach($forms as $form)
                                                <option value="{{ $form->id }}">{{ $form->code }} — {{$form->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2 flex items-center gap-2">
                                        <label class="inline-flex items-center gap-1 text-[11px] text-slate-600 dark:text-slate-300">
                                            <input type="checkbox" :name="`attachments[${aIdx}][is_mandatory]`" value="1" x-model="att.is_mandatory" class="rounded text-blue-600"> Mandatory
                                        </label>
                                    </div>
                                    <div class="sm:col-span-1 text-right">
                                        <button @click="removeAttachmentRule(aIdx)" type="button" class="text-rose-500 hover:text-rose-700 p-1">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Nested Signatories Sequence -->
                                <div class="pl-3 border-l-2 border-blue-200 dark:border-blue-900 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold uppercase text-slate-500">Signatory Role Sequence</span>
                                        <button @click="addSignatory(aIdx)" type="button" class="text-[10px] text-blue-600 font-bold hover:underline">+ Add Role Step</button>
                                    </div>
                                    
                                    <div class="space-y-1.5">
                                        <template x-for="(sig, sIdx) in att.signatories" :key="sIdx">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-mono font-bold text-slate-400" x-text="`${sIdx + 1}.`"></span>
                                                <select :name="`attachments[${aIdx}][signatories][${sIdx}][role_id]`" x-model="sig.role_id" required class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded px-2 py-1 text-[11px] w-full">
                                                    <option value="">Select Designated Role...</option>
                                                    @foreach($roles as $role)
                                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" :name="`attachments[${aIdx}][signatories][${sIdx}][sequence]`" :value="sIdx + 1">
                                                <button @click="removeSignatory(aIdx, sIdx)" type="button" class="text-slate-400 hover:text-rose-500 text-xs px-1">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="!phaseForm.attachments || phaseForm.attachments.length === 0">
                            <p class="text-xs text-slate-400 italic">No attachments configured for this phase.</p>
                        </template>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                    <button @click="isPhaseModalOpen = false" type="button" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition">
                        <span x-text="isPhaseEditMode ? 'Update Phase Step' : 'Save Phase Step'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- JavaScript Component State & Logic -->
<script>
function workflowBuilder() {
    return {
        // State Collections
        phases: @json($mode->phases->sortBy('phase_sequence')->values()),
        
        // Modal State
        isPhaseModalOpen: false,
        isPhaseEditMode: false,
        phaseFormAction: '{{ route('admin.modes.phases.store', $mode->id) }}',
        phaseForm: {
            id: null,
            phase_sequence: 1,
            phase_name: '',
            allowable_days: 7,
            description: '',
            role_ids: [],
            attachments: []
        },

        // Drag & Drop State
        draggedIndex: null,
        dropTargetIndex: null,
        dragOverPosition: null,

        // Phase Modal Controls
        openPhaseCreateModal() {
            this.isPhaseEditMode = false;
            this.phaseFormAction = '{{ route('admin.modes.phases.store', $mode->id) }}';
            this.phaseForm = {
                id: null,
                phase_sequence: this.phases.length + 1,
                phase_name: '',
                allowable_days: 7,
                description: '',
                role_ids: [],
                attachments: []
            };
            this.isPhaseModalOpen = true;
        },

        openPhaseEditModal(phase) {
            this.isPhaseEditMode = true;
            this.phaseFormAction = `/admin/modes/{{ $mode->id }}/phases/${phase.id}`;
            this.phaseForm = {
                id: phase.id,
                phase_sequence: phase.phase_sequence,
                phase_name: phase.phase_name,
                allowable_days: phase.allowable_days,
                description: phase.description || '',
                role_ids: phase.roles ? phase.roles.map(r => r.id) : [],
                attachments: phase.attachments ? phase.attachments.map(a => ({
                    id: a.id || null,
                    name: a.name || '',
                    form_id: a.form_id || '',
                    is_mandatory: Boolean(a.is_mandatory),
                    signatories: Array.isArray(a.signatories) ? a.signatories.map(s => ({
                        id: s.id || null,
                        role_id: s.role_id || '',
                        sequence: s.sequence || 1,
                        role: s.role || null
                    })) : []
                })) : []
            };
            this.isPhaseModalOpen = true;
        },

        addSignatory(attIndex) {
            if (!this.phaseForm.attachments[attIndex].signatories) {
                this.phaseForm.attachments[attIndex].signatories = [];
            }
            this.phaseForm.attachments[attIndex].signatories.push({
                role_id: '',
                sequence: this.phaseForm.attachments[attIndex].signatories.length + 1
            });
        },

        // Dynamic Attachment & Signatory Array Helpers
        addAttachmentRule() {
            this.phaseForm.attachments.push({
                name: '',
                form_id: '',
                is_mandatory: true,
                signatories: []
            });
        },

        removeAttachmentRule(index) {
            this.phaseForm.attachments.splice(index, 1);
        },

        removeSignatory(attIndex, sigIndex) {
            this.phaseForm.attachments[attIndex].signatories.splice(sigIndex, 1);
            this.phaseForm.attachments[attIndex].signatories.forEach((sig, idx) => {
                sig.sequence = idx + 1;
            });
        },

        // Drag and Drop Logic
        onDragStart(event, index) {
            this.draggedIndex = index;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', index);
        },

        onDragOver(event, index) {
            if (this.draggedIndex === index) return;
            this.dropTargetIndex = index;
            const rect = event.currentTarget.getBoundingClientRect();
            this.dragOverPosition = event.clientY < (rect.top + rect.height / 2) ? 'above' : 'below';
        },

        onDragLeave(event) {},

        onDrop(event, index) {
            if (this.draggedIndex === null || this.draggedIndex === index) return;
            const movedItem = this.phases.splice(this.draggedIndex, 1)[0];
            let targetIndex = index;
            this.phases.splice(targetIndex, 0, movedItem);

            this.phases.forEach((phase, idx) => {
                phase.phase_sequence = idx + 1;
            });

            this.saveReorderedSequence();
            this.onDragEnd();
        },

        onDragEnd() {
            this.draggedIndex = null;
            this.dropTargetIndex = null;
            this.dragOverPosition = null;
        },

        saveReorderedSequence() {
            const payload = {
                phases: this.phases.map((p, idx) => ({
                    id: p.id,
                    sequence: idx + 1
                }))
            };
            
            fetch('{{ route('admin.modes.phases.reorder', $mode->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                console.log('Sequence reordered successfully:', data.message);
            })
            .catch(error => {
                console.error('Error auto-saving phase sequence:', error);
            });
        }
    }
}
</script>
@endsection
