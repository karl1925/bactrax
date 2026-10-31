<script>
(() => {
    const open = (detail) => window.dispatchEvent(new CustomEvent('open-confirmation', { detail }));
    document.addEventListener('click', (event) => {
        const discard = event.target.closest('[data-discard-url]');
        if (discard) { event.preventDefault(); open({url: discard.dataset.discardUrl || discard.href, title: discard.dataset.discardTitle || 'Leave this page?', message: discard.dataset.discardMessage || 'Any unsaved changes will be discarded.', eyebrow: 'Unsaved work', tone: 'amber', icon: 'fa-triangle-exclamation', confirmButton: 'Discard and leave', cancelButton: 'Keep editing'}); return; }
        const trigger = event.target.closest('[data-confirm-open]');
        if (trigger) { event.preventDefault(); const form = trigger.closest('form'); if (!form) return; open({action: form.action, title: form.dataset.confirmTitle, message: form.dataset.confirmMessage, eyebrow: form.dataset.confirmEyebrow, confirmButton: form.dataset.confirmButton, cancelButton: form.dataset.confirmCancel || 'Cancel', method: form.dataset.confirmMethod || 'POST', tone: form.dataset.confirmTone, icon: form.dataset.confirmIcon}); return; }
        const button = event.target.closest('[data-confirm-action]');
        if (button && !button.hasAttribute('x-on:click')) open({action: button.dataset.confirmAction, title: button.dataset.confirmTitle, message: button.dataset.confirmMessage, confirmButton: button.dataset.confirmButton, method: button.dataset.confirmMethod || 'POST', tone: button.dataset.confirmTone || 'blue', icon: button.dataset.confirmIcon});
    });
    document.addEventListener('submit', (event) => { const form = event.target; if (!form.matches('[data-confirmation]')) return; if (form.dataset.confirmationBypass === '1') { delete form.dataset.confirmationBypass; return; } event.preventDefault(); open({sourceForm: form, sourceSubmitter: event.submitter, action: form.action, title: form.dataset.confirmTitle, message: form.dataset.confirmMessage, eyebrow: form.dataset.confirmEyebrow, confirmButton: form.dataset.confirmButton, cancelButton: form.dataset.confirmCancel, method: form.dataset.confirmMethod || 'POST', tone: form.dataset.confirmTone, icon: form.dataset.confirmIcon}); }, true);
})();
</script>
