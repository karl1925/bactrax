@extends('layouts.app')

@section('title', 'Edit Procurement Mode - ' . $mode->code)

@section('page-header')
<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
  <div><h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">Edit Procurement Modality</h1>
    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Adjust RA 12009 statutory rules and financial caps</p></div>
  @include('partials.page-back-link', ['href' => route('admin.modes.index'), 'label' => 'Back to Registry', 'discardTitle' => 'Leave mode editing?', 'discardMessage' => 'Any unsaved mode changes will be discarded.'])
</div>
@endsection

@push('styles')
    <style>
        .active-switch {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 22px;
            cursor: pointer;
            flex-shrink: 0;
        }

        .active-switch input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .active-switch-slider {
            position: absolute;
            inset: 0;
            background: #cbd5e1;
            border-radius: 9999px;
            transition: background-color 0.2s ease;
        }

        /* The white sliding knob */
        .active-switch-slider::before {
            content: "";
            position: absolute;

            width: 18px;
            height: 18px;

            left: 2px;
            top: 2px;

            background: #ffffff;
            border-radius: 50%;

            box-shadow:
                0 1px 3px rgba(0, 0, 0, 0.25),
                0 1px 2px rgba(0, 0, 0, 0.15);

            transform: translateX(0);
            transition:
                transform 0.2s cubic-bezier(0.4, 0, 0.2, 1),
                box-shadow 0.2s ease;
        }

        /* ON — blue track */
        .active-switch input:checked + .active-switch-slider {
            background: #4f46e5;
        }

        /* ON — move knob to the right */
        .active-switch input:checked + .active-switch-slider::before {
            transform: translateX(18px);

            box-shadow:
                0 1px 4px rgba(0, 0, 0, 0.3),
                0 0 0 2px rgba(255, 255, 255, 0.15);
        }

        /* Keyboard accessibility */
        .active-switch:focus-within .active-switch-slider {
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }
    </style>
@endpush

@section('content')
<div class="max-w-xl p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
    <form action="{{ route('admin.modes.update', $mode->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Code <span class="text-rose-500">*</span></label>
            <input type="text" name="code" value="{{ old('code', $mode->code) }}" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs font-mono font-bold text-blue-600 dark:text-blue-400 outline-none focus:border-blue-600">
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Modality Title <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $mode->name) }}" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-blue-600">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Minimum ABC (₱)</label>
                <input type="number" step="0.01" name="min_abc" value="{{ old('min_abc', $mode->min_abc) }}" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs font-mono text-slate-800 dark:text-slate-100 outline-none focus:border-blue-600">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Maximum ABC Cap (₱)</label>
                <input type="number" step="0.01" name="max_abc" value="{{ old('max_abc', $mode->max_abc) }}" placeholder="Leave blank if uncapped" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs font-mono text-slate-800 dark:text-slate-100 outline-none focus:border-blue-600">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Description / Statutory IRR Notes</label>
            <textarea name="description" rows="3" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-blue-600">{{ old('description', $mode->description) }}</textarea>
        </div>

        <div class="flex items-center justify-between gap-4">
            {{-- Label --}}
            <div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Active Status
                </p>

                <p
                    id="isActiveLabel"
                    class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400"
                >
                    {{ old('is_active', $mode->is_active) ? 'Active' : 'Inactive' }}
                </p>
            </div>


            {{-- Switch --}}
            <div class="flex items-center justify-between gap-4">

                {{-- Switch --}}
                <div class="flex shrink-0 items-center">

                    {{-- Laravel fallback --}}
                    <input
                        type="hidden"
                        name="is_active"
                        value="0"
                    >

                    {{-- Switch button --}}
                    <button
                        type="button"
                        id="isActiveToggle"
                        aria-label="Toggle active status"
                        aria-pressed="{{ old('is_active', $mode->is_active) ? 'true' : 'false' }}"
                        style="
                            position: relative;
                            display: block;
                            width: 44px;
                            height: 24px;
                            padding: 0;
                            border: 0;
                            border-radius: 9999px;
                            background-color: {{ old('is_active', $mode->is_active) ? '#4f46e5' : '#cbd5e1' }};
                            cursor: pointer;
                            transition: background-color 0.2s ease;
                            box-sizing: border-box;
                        "
                    >

                        {{-- Sliding knob --}}
                        <span
                            id="isActiveKnob"
                            style="
                                position: absolute;
                                display: block;
                                width: 18px;
                                height: 18px;
                                top: 3px;
                                left: 3px;
                                margin: 0;
                                padding: 0;
                                border-radius: 50%;
                                background: #ffffff;
                                box-shadow: 0 1px 4px rgba(0,0,0,0.25);
                                transform: translateX({{ old('is_active', $mode->is_active) ? '20px' : '0' }});
                                transition: transform 0.2s ease;
                                box-sizing: border-box;
                            "
                        ></span>

                    </button>


                    {{-- Actual checkbox --}}
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        id="is_active"
                        style="display:none;"
                        {{ old('is_active', $mode->is_active) ? 'checked' : '' }}
                    >

                </div>

            </div>

        </div>




        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('admin.modes.index') }}" data-discard-url="{{ route('admin.modes.index') }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold hover:bg-slate-300 transition">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-blue-500/20 transition">Save Changes</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <<script>
document.addEventListener('DOMContentLoaded', function () {

    const toggle = document.getElementById('isActiveToggle');
    const checkbox = document.getElementById('is_active');
    const knob = document.getElementById('isActiveKnob');
    const label = document.getElementById('isActiveLabel');

    if (!toggle || !checkbox || !knob || !label) {
        return;
    }

    function updateSwitch() {

        if (checkbox.checked) {

            // BLUE TRACK
            toggle.style.backgroundColor = '#4f46e5';

            // MOVE KNOB RIGHT
            knob.style.transform = 'translateX(20px)';

            // LABEL
            label.textContent = 'Active';

            label.classList.remove('text-slate-500');
            label.classList.add('text-blue-600');

            toggle.setAttribute('aria-pressed', 'true');

        } else {

            // GRAY TRACK
            toggle.style.backgroundColor = '#cbd5e1';

            // MOVE KNOB LEFT
            knob.style.transform = 'translateX(0)';

            // LABEL
            label.textContent = 'Inactive';

            label.classList.remove('text-blue-600');
            label.classList.add('text-slate-500');

            toggle.setAttribute('aria-pressed', 'false');
        }
    }


    toggle.addEventListener('click', function () {

        checkbox.checked = !checkbox.checked;

        updateSwitch();

    });


    updateSwitch();

});
</script>
@endpush
