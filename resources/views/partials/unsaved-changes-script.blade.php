<script>
(() => {
    let navigationConfirmed = false;

    const serializeForm = (form) => Array.from(form.elements)
        .filter((control) => control.name && !control.disabled && control.name !== '_token' && !['submit', 'button', 'reset'].includes(control.type))
        .map((control) => {
            if (control.type === 'checkbox' || control.type === 'radio') {
                return [control.name, control.type, control.value, control.checked];
            }
            if (control.type === 'file') {
                return [control.name, 'file', Array.from(control.files || []).map((file) => `${file.name}:${file.size}:${file.lastModified}`)];
            }
            if (control instanceof HTMLSelectElement && control.multiple) {
                return [control.name, Array.from(control.selectedOptions).map((option) => option.value)];
            }
            return [control.name, control.value];
        });

    const initialize = () => {
        document.querySelectorAll('form[data-unsaved-changes]').forEach((form) => {
            const initialState = JSON.stringify(serializeForm(form));
            form.dataset.unsavedDirty = '0';

            const updateDirtyState = () => {
                form.dataset.unsavedDirty = JSON.stringify(serializeForm(form)) === initialState ? '0' : '1';
            };

            form.addEventListener('input', updateDirtyState);
            form.addEventListener('change', updateDirtyState);
            form.addEventListener('submit', () => {
                form.dataset.unsavedSubmitting = '1';
            });
        });
    };

    const hasUnsavedChanges = () => Array.from(document.querySelectorAll('form[data-unsaved-changes]'))
        .some((form) => form.dataset.unsavedDirty === '1' && form.dataset.unsavedSubmitting !== '1');

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link || !hasUnsavedChanges() || event.defaultPrevented || event.button !== 0
            || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
            || link.target === '_blank' || link.hasAttribute('download')) return;

        const destination = new URL(link.href, window.location.href);
        if (destination.origin !== window.location.origin
            || (destination.pathname === window.location.pathname && destination.search === window.location.search && destination.hash)) return;

        event.preventDefault();
        window.dispatchEvent(new CustomEvent('open-confirmation', {
            detail: {
                url: destination.href,
                title: link.dataset.discardTitle || 'Leave this page?',
                message: link.dataset.discardMessage || 'Any unsaved changes will be discarded.',
                eyebrow: 'Unsaved work',
                tone: 'amber',
                icon: 'fa-triangle-exclamation',
                confirmButton: 'Discard and leave',
                cancelButton: 'Keep editing',
                onConfirm: () => {
                    navigationConfirmed = true;
                    window.location.assign(destination.href);
                },
            },
        }));
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }

    window.addEventListener('beforeunload', (event) => {
        if (navigationConfirmed) return;
        if (!hasUnsavedChanges()) return;
        event.preventDefault();
        event.returnValue = '';
    });
})();
</script>
