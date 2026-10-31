@props(['configuration' => []])

<div x-data="formRenderer({{ json_encode($configuration) }})" class="space-y-4">
    <template x-for="(field, index) in schema" :key="index">
        <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                <span x-text="field.label"></span>
                <template x-if="field.required">
                    <span class="text-rose-500">*</span>
                </template>
            </label>

            <!-- Text Input -->
            <template x-if="field.type === 'text'">
                <input type="text" :name="field.name" :placeholder="field.placeholder || ''" :required="field.required" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs">
            </template>

            <!-- Textarea -->
            <template x-if="field.type === 'textarea'">
                <textarea :name="field.name" rows="3" :placeholder="field.placeholder || ''" :required="field.required" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs"></textarea>
            </template>

            <!-- Select Dropdown -->
            <template x-if="field.type === 'select'">
                <select :name="field.name" :required="field.required" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs">
                    <option value="">-- Select --</option>
                    <template x-for="opt in field.options" :key="opt">
                        <option :value="opt" x-text="opt"></option>
                    </template>
                </select>
            </template>
        </div>
    </template>
</div>

<script>
function formRenderer(config) {
    return {
        schema: typeof config === 'string' ? JSON.parse(config) : (config || [])
    }
}
</script>