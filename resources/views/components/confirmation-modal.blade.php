<div x-data="{
    open: false,
    action: '', url: '', sourceForm: null, sourceSubmitter: null, onConfirm: null,
    title: 'Confirm action', message: 'Are you sure you want to continue?', eyebrow: 'Please confirm',
    confirmButton: 'Confirm', cancelButton: 'Cancel', showCancel: true,
    method: 'POST', tone: 'blue', icon: 'fa-circle-question',
    palette: {
        blue: {bar:'from-blue-500 via-blue-500 to-cyan-400', icon:'bg-blue-50 text-blue-700 ring-blue-50/60 dark:bg-blue-950 dark:text-blue-300 dark:ring-blue-950/40', eyebrow:'text-blue-600 dark:text-blue-300', button:'bg-blue-600 hover:bg-blue-700 shadow-blue-600/20'},
        rose: {bar:'from-rose-500 via-rose-500 to-amber-400', icon:'bg-rose-50 text-rose-700 ring-rose-50/60 dark:bg-rose-950 dark:text-rose-300 dark:ring-rose-950/40', eyebrow:'text-rose-600 dark:text-rose-300', button:'bg-rose-600 hover:bg-rose-700 shadow-rose-600/20'},
        amber: {bar:'from-amber-400 via-amber-500 to-rose-500', icon:'bg-amber-50 text-amber-700 ring-amber-50/60 dark:bg-amber-950 dark:text-amber-300 dark:ring-amber-950/40', eyebrow:'text-amber-700 dark:text-amber-300', button:'bg-amber-600 hover:bg-amber-700 shadow-amber-600/20'},
        emerald: {bar:'from-emerald-400 via-teal-500 to-cyan-500', icon:'bg-emerald-50 text-emerald-700 ring-emerald-50/60 dark:bg-emerald-950 dark:text-emerald-300 dark:ring-emerald-950/40', eyebrow:'text-emerald-700 dark:text-emerald-300', button:'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/20'},
        slate: {bar:'from-slate-400 via-slate-500 to-blue-500', icon:'bg-slate-100 text-slate-700 ring-slate-100/60 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-800/40', eyebrow:'text-slate-600 dark:text-slate-300', button:'bg-slate-800 hover:bg-slate-700 shadow-slate-800/20 dark:bg-slate-200 dark:text-slate-900 dark:hover:bg-white'}
    },
    show(detail = {}) {
        this.action = detail.action || ''; this.url = detail.url || '';
        this.sourceForm = detail.sourceForm || null; this.sourceSubmitter = detail.sourceSubmitter || null;
        this.onConfirm = typeof detail.onConfirm === 'function' ? detail.onConfirm : null;
        this.title = detail.title || 'Confirm action'; this.message = detail.message || 'Are you sure you want to continue?';
        this.eyebrow = detail.eyebrow || 'Please confirm'; this.confirmButton = detail.confirmButton || detail.button || 'Confirm';
        this.cancelButton = detail.cancelButton || 'Cancel'; this.showCancel = detail.showCancel !== false;
        this.method = detail.method || 'POST';
        const requestedTone = detail.tone || detail.color || 'blue';
        this.tone = ({archive: 'slate', delete: 'rose'})[requestedTone] || (this.palette[requestedTone] ? requestedTone : 'blue');
        this.icon = detail.icon || (this.tone === 'rose' ? 'fa-trash-can' : this.tone === 'amber' ? 'fa-triangle-exclamation' : this.tone === 'emerald' ? 'fa-circle-check' : this.tone === 'slate' ? 'fa-box-archive' : 'fa-circle-question');
        this.open = true; document.body.classList.add('overflow-hidden'); this.$nextTick(() => this.$refs.cancelButton?.focus());
    },
    close() { this.open = false; this.sourceForm = null; this.onConfirm = null; document.body.classList.remove('overflow-hidden'); },
    confirm() {
        const source = this.sourceForm, submitter = this.sourceSubmitter, url = this.url, action = this.action, callback = this.onConfirm;
        this.close();
        if (callback) callback();
        else if (source) { source.dataset.confirmationBypass = '1'; source.requestSubmit(submitter || undefined); }
        else if (url) window.location.assign(url);
        else if (action) this.$refs.actionForm.submit();
    }
}" @open-confirmation.window="show($event.detail)" @open-action-confirmation.window="show($event.detail)" @open-discard-confirmation.window="show({...$event.detail, tone:'amber', icon:'fa-triangle-exclamation', eyebrow:'Unsaved work', confirmButton:'Discard and leave', cancelButton:'Keep editing'})" @keydown.escape.window="close()">
    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-[160] flex items-center justify-center overflow-y-auto bg-slate-950/65 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="confirmation-dialog-title" @click.self="close()">
        <div x-transition class="w-full max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <div class="h-1.5 bg-gradient-to-r" :class="palette[tone].bar"></div>
            <div class="p-6 sm:p-7">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl ring-8" :class="palette[tone].icon"><i class="fa-solid text-xl" :class="icon"></i></div>
                <p class="mt-6 text-[10px] font-extrabold uppercase tracking-[.18em]" :class="palette[tone].eyebrow" x-text="eyebrow"></p>
                <h2 id="confirmation-dialog-title" class="mt-2 text-lg font-extrabold tracking-tight text-slate-900 dark:text-white" x-text="title"></h2>
                <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400" x-text="message"></p>
                <form x-ref="actionForm" :action="action" method="POST" class="mt-7 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end" @submit.prevent="confirm()">
                    @csrf
                    <input type="hidden" :name="method === 'POST' ? '' : '_method'" :value="method">
                    <button x-ref="cancelButton" x-show="showCancel" type="button" @click="close()" class="rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800" x-text="cancelButton"></button>
                    <button type="button" @click="confirm()" class="rounded-xl px-4 py-2.5 text-xs font-extrabold text-white shadow-lg transition" :class="palette[tone].button" x-text="confirmButton"></button>
                </form>
            </div>
        </div>
    </div>
</div>
