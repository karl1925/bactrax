<script>
document.querySelectorAll('[data-directory-filters]').forEach(form => {
    const clear = form.querySelector('[data-clear-filters]');
    if (!clear) return;
    const sync = () => { clear.hidden = ![...form.querySelectorAll('input, select')].some(field => field.value.trim() !== ''); };
    form.addEventListener('input', sync);
    form.addEventListener('change', sync);
    sync();
});
</script>
