<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="role-id" content="{{ Auth::user()->roles()->first()->id }}">
    {{-- <meta name="office-id" content="{{ Auth::user()->offices()->first()->id }}"> --}}
    <meta name="user-id" content="{{ Auth::user()->id }}">
    <title>@yield('title', config('app.name', 'BACTrax')) - DICT R2</title>

    @vite(['resources/css/app.css', 'resources/css/editor.css', 'resources/js/app.js', 'resources/js/editor.js'])

    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.tailwindcss.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">

    @stack('styles')
</head>

<body
    x-data="{
        editingName: false,
        formName: @js($form->name ?? 'Untitled Form'),
        originalName: @js($form->name ?? 'Untitled Form'),
        darkMode: localStorage.getItem('darkMode') === 'true',

        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            this.applyTheme();
        },

        applyTheme() {
            document.documentElement.classList.toggle('dark', this.darkMode);
            localStorage.setItem('darkMode', this.darkMode ? 'true' : 'false');
        },

        startEditing() {
            this.originalName = this.formName;
            this.editingName = true;

            this.$nextTick(() => {
                this.$refs.formNameInput?.focus();
                this.$refs.formNameInput?.select();

                this.resizeFilenameInput();
            });
        },

        resizeFilenameInput() {
            this.$nextTick(() => {
                const input = this.$refs.formNameInput;
                if (!input) return;

                const mirror = document.createElement('span');
                const style = getComputedStyle(input);

                mirror.style.position = 'absolute';
                mirror.style.visibility = 'hidden';
                mirror.style.whiteSpace = 'pre';
                mirror.style.font = style.font;
                mirror.style.fontSize = style.fontSize;
                mirror.style.fontFamily = style.fontFamily;
                mirror.style.fontWeight = style.fontWeight;
                mirror.style.letterSpacing = style.letterSpacing;
                mirror.style.paddingLeft = style.paddingLeft;
                mirror.style.paddingRight = style.paddingRight;
                mirror.textContent = input.value || input.placeholder || '';

                document.body.appendChild(mirror);

                const minWidth = 80;
                const maxWidth = 400;

                const width = Math.min(
                    maxWidth,
                    Math.max(minWidth, Math.ceil(mirror.getBoundingClientRect().width))
                );

                input.style.width = `${width}px`;

                mirror.remove();
            });
        },

        cancelEditing() {
            if (!this.editingName) return;
            this.formName = this.originalName;
            this.editingName = false;
        },

        saveEditing() {
            const name = this.formName.trim();

            if (!name) {
                this.formName = this.originalName;
                return;
            }

            this.formName = name;
            this.editingName = false;

            this.$nextTick(() => {
                const saveBtn = document.getElementById('saveBtn');
                if (saveBtn) saveBtn.click();

                document.dispatchEvent(new CustomEvent('form-name-changed', {
                    detail: { name: this.formName }
                }));
            });
        }
    }"
    x-init="applyTheme()"
    class="relative bg-slate-100 text-slate-800 antialiased overflow-x-hidden transition-colors duration-200 dark:bg-slate-950 dark:text-slate-100">

    @yield('content')

    <div id="toastContainer" class="pointer-events-none fixed right-5 top-5 z-[1000] w-full max-w-sm space-y-3"></div>

    @stack('modals')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function showToast(title, message, type = 'success', url = null) {
                const icons = {
                    success: 'fa-circle-check',
                    error: 'fa-circle-exclamation',
                    warning: 'fa-triangle-exclamation',
                    info: 'fa-circle-info'
                };

                const classes = {
                    success: 'border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400',
                    error: 'border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-400',
                    warning: 'border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400',
                    info: 'border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-400'
                };

                const urlLink = url ? `
                    <a href="${url}"
                       class="mt-2 inline-flex items-center gap-1 text-[10px] font-bold text-blue-600 hover:text-blue-700 hover:underline dark:text-blue-400 dark:hover:text-blue-300">
                        View Procurement
                        <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                    </a>
                ` : '';

                const $toast = $(`
                    <div class="toast pointer-events-auto translate-x-full rounded-xl border bg-white px-4 py-3 opacity-0 shadow-xl transition-all duration-300 dark:bg-slate-900 ${classes[type] || classes.info}">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid ${icons[type] || icons.info} shrink-0 text-base"></i>
                            <p class="min-w-0 flex-1 text-xs font-bold text-slate-800 dark:text-slate-100">
                                ${title || 'Notification'}
                            </p>
                            <button type="button" class="toast-close shrink-0 text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-200">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div class="mt-1 pl-7">
                            <p class="text-xs font-medium text-slate-600 dark:text-slate-300">${message || ''}</p>
                            ${urlLink}
                        </div>
                    </div>
                `);

                $('#toastContainer').append($toast);

                requestAnimationFrame(() => {
                    $toast.removeClass('translate-x-full opacity-0');
                });

                function removeToast() {
                    $toast.addClass('translate-x-full opacity-0');
                    setTimeout(() => $toast.remove(), 300);
                }

                $toast.find('.toast-close').on('click', removeToast);
                setTimeout(removeToast, 6000);
            }

            window.showToast = showToast;

            const userId = $('meta[name="user-id"]').attr('content');
            const officeId = $('meta[name="office-id"]').attr('content');

            function handleProcurementUpdate(event, context = 'office') {
                let title = 'Procurement Update';
                let toastType = 'info';

                switch (event.action) {
                    case 'ROUTING':
                        title = context === 'user'
                            ? 'Procurement Assigned to You'
                            : 'Procurement Routed to Office';
                        break;
                    case 'RETURNED':
                        title = 'Procurement Returned';
                        toastType = 'warning';
                        break;
                    case 'COMPLETED':
                        title = 'Procurement Completed';
                        toastType = 'success';
                        break;
                    case 'CANCELLED':
                        title = 'Procurement Cancelled';
                        toastType = 'error';
                        break;
                    case 'REJECTED':
                        title = 'Procurement Rejected';
                        toastType = 'error';
                        break;
                }

                const message = event.message ||
                    `Procurement #${event.procurement_id ?? ''} has been updated.`;

                showToast(title, message, toastType, event.url || null);
            }

            if (typeof window.Echo !== 'undefined' && officeId) {
                window.Echo.private(`office.${officeId}`)
                    .listen('.procurement.office_updated', (event) => {
                        handleProcurementUpdate(event, 'office');
                    });
            }

            if (typeof window.Echo !== 'undefined' && userId) {
                window.Echo.private(`user.${userId}`)
                    .listen('.procurement.user_updated', (event) => {
                        handleProcurementUpdate(event, 'user');
                    });
            }

            
        });
    </script>

    @stack('scripts')
    <x-confirmation-modal />
    @include('partials.confirmation-modal-script')
</body>
</html>
