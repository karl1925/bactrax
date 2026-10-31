/**
 * Shared Procurement Form & Preview Handler (Create & Edit)
 */
window.initProcurementBuilder = function initProcurementBuilder(config = {}) {
    const { initialItems = [], formsApiUrl = '/api/procurement-modes' } = config;

    let formsRequestId = 0;
    const table = document.getElementById('items-table');
    const form = document.getElementById('pr-form');
    const sidebar = document.getElementById('form-preview-sidebar');
    const resizer = document.getElementById('sidebar-resizer');
    const modeSelect = document.getElementById('procurement_mode_id');
    const formsList = document.getElementById('dynamic-forms-list');
    const modal = document.getElementById('fullscreen-preview-modal');
    const modalTitle = document.getElementById('modal-form-title');
    const modalBody = document.getElementById('modal-form-body');
    const expandTab = document.getElementById('expand-sidebar-tab');
    const toggleBtn = document.getElementById('toggle-sidebar-btn');

    if (!table || !form || !sidebar || !modeSelect || !formsList) return;

    const states = {
        noMode: document.getElementById('state-no-mode'),
        loading: document.getElementById('state-loading'),
        noForms: document.getElementById('state-no-forms'),
    };

    let currentForms = [],
        draggedTarget = null,
        dragType = null,
        currentDragHandle = null,
        resizeState = null,
        templateCache = new Map(),
        modalResize = null,
        activeModalForm = null,
        previewUpdateFrame = null,
        activeMenuRow = null,
        activeMenuToggle = null;

    const money = n => '₱ ' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const isMobile = () => window.innerWidth < 768;

    // --- Template Instantiation Helpers ---
    function createItemGroup() {
        const tpl = document.getElementById('tpl-item-group');
        if (!tpl) {
            console.error('Missing <template id="tpl-item-group"> in Blade!');
            return document.createElement('tbody');
        }
        return tpl.content.cloneNode(true).querySelector('tbody');
    }

    function createSubRow() {
        const tpl = document.getElementById('tpl-sub-row');
        if (!tpl) {
            console.error('Missing <template id="tpl-sub-row"> in Blade!');
            return document.createElement('tr');
        }
        return tpl.content.cloneNode(true).querySelector('tr');
    }

    function createLotGroup() {
        const b = document.createElement('tbody');
        b.className = 'item-group border-t-2 border-indigo-100 dark:border-indigo-950';
        b.dataset.type = 'lot';
        b.appendChild(createSubRow());
        return b;
    }

    // --- Floating Action Menu ---
    let floatingMenu = document.getElementById('floating-item-action-menu');
    if (!floatingMenu) {
        floatingMenu = document.createElement('div');
        floatingMenu.className = 'floating-item-action-menu';
        floatingMenu.id = 'floating-item-action-menu';
        floatingMenu.setAttribute('role', 'menu');
        floatingMenu.innerHTML = `
            <button type="button" role="menuitem" data-action="item-above"><i class="fa-solid fa-arrow-up mr-2 text-slate-400"></i> Row Above</button>
            <button type="button" role="menuitem" data-action="item-below"><i class="fa-solid fa-arrow-down mr-2 text-slate-400"></i> Row Below</button>
            <div class="my-1 h-px bg-slate-100 dark:bg-slate-800"></div>
            <button type="button" role="menuitem" data-action="lot-above"><i class="fa-solid fa-layer-group mr-2 text-indigo-400"></i> Lot Above</button>
            <button type="button" role="menuitem" data-action="lot-below"><i class="fa-solid fa-layer-group mr-2 text-indigo-400"></i> Lot Below</button>
            <div class="my-1 h-px bg-slate-100 dark:bg-slate-800"></div>
            <button type="button" role="menuitem" data-action="delete-row"><i class="fa-regular fa-trash-can mr-2"></i> Delete</button>`;
        document.body.appendChild(floatingMenu);
    }

    function setName(row, selector, name) {
        const el = row.querySelector(selector);
        if (el) el.name = name;
    }

    // --- Render Initial Data to DOM ---
    function renderInitialItems(items) {
        table.querySelectorAll('tbody.item-group').forEach(b => b.remove());

        if (!items || !items.length) {
            table.appendChild(createItemGroup());
            return;
        }

        items.forEach(item => {
            if (item.is_lot || (item.sub_items && item.sub_items.length)) {
                const group = document.createElement('tbody');
                group.className = 'item-group border-t-2 border-indigo-100 dark:border-indigo-950';
                group.dataset.type = 'lot';

                const subItems = item.sub_items && item.sub_items.length ? item.sub_items : [{}];
                subItems.forEach(sub => {
                    const row = createSubRow();
                    if (row.querySelector('[data-field="description"]')) row.querySelector('[data-field="description"]').value = sub.description || '';
                    if (row.querySelector('[data-field="quantity"]')) row.querySelector('[data-field="quantity"]').value = sub.quantity ?? 1.00;
                    if (row.querySelector('[data-field="cost"]')) row.querySelector('[data-field="cost"]').value = sub.cost ?? '';
                    group.appendChild(row);
                });
                table.appendChild(group);
            } else {
                const group = createItemGroup();
                const row = group.querySelector('tr');
                if (row) {
                    if (row.querySelector('[data-field="unit"]')) row.querySelector('[data-field="unit"]').value = item.unit || '';
                    if (row.querySelector('[data-field="description"]')) row.querySelector('[data-field="description"]').value = item.description || '';
                    if (row.querySelector('[data-field="quantity"]')) row.querySelector('[data-field="quantity"]').value = item.quantity ?? 1.00;
                    if (row.querySelector('[data-field="cost"]')) row.querySelector('[data-field="cost"]').value = item.cost ?? '';
                }
                table.appendChild(group);
            }
        });
    }

    function refreshTableStructure() {
        table.querySelectorAll('tbody.item-group').forEach(group => {
            const rows = group.querySelectorAll('tr.draggable-row');
            if (!rows.length) group.remove();
        });

        let groupIndex = 0, lineNo = 1;

        [...table.querySelectorAll('tbody.item-group')].forEach(group => {
            const isLot = group.dataset.type === 'lot';

            if (!isLot) {
                const row = group.querySelector('tr.draggable-row');
                if (!row) { group.remove(); return; }
                row.className = 'item-row draggable-row border-b border-slate-100 dark:border-slate-800/50';

                row.querySelector('.lot-merged-unit')?.remove();
                if (!row.querySelector('[data-field="unit"]')) {
                    row.children[0].insertAdjacentHTML('afterend', `<td class="p-2"><input type="text" data-field="unit" required placeholder="e.g. Pcs" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs"></td>`);
                }

                if (row.querySelector('.line-no')) row.querySelector('.line-no').textContent = lineNo;
                if (row.querySelector('.line-no-input')) row.querySelector('.line-no-input').value = lineNo;
                setName(row, '.line-no-input', `items[${groupIndex}][line_no]`);

                row.querySelectorAll('[data-field="is_lot"]').forEach(el => el.remove());
                const hidden = document.createElement('input');
                hidden.type = 'hidden'; hidden.dataset.field = 'is_lot'; hidden.value = '0';
                row.querySelector('td').appendChild(hidden);

                setName(row, '[data-field="is_lot"]', `items[${groupIndex}][is_lot]`);
                ['unit', 'description', 'quantity', 'cost'].forEach(k => setName(row, `[data-field="${k}"]`, `items[${groupIndex}][${k}]`));
                lineNo++;
            } else {
                const rows = [...group.querySelectorAll('tr.draggable-row')];
                rows.forEach(r => {
                    r.className = 'lot-sub-item draggable-row border-b border-slate-100 dark:border-slate-800/50 bg-slate-50/20 dark:bg-slate-800/10';
                    r.querySelector('.lot-merged-unit')?.remove();
                    if (r.querySelector('[data-field="unit"]:not([value="Lot"])')?.closest('td')) {
                        r.querySelector('[data-field="unit"]')?.closest('td').remove();
                    }
                });

                if (!rows.length) { group.remove(); return; }

                const merged = document.createElement('td');
                merged.className = 'lot-merged-unit p-2 text-center align-middle bg-indigo-50/40 dark:bg-indigo-950/30 border-r border-indigo-100 dark:border-indigo-900/40';
                merged.rowSpan = rows.length;
                merged.innerHTML = `
                    <div class="flex flex-col items-center justify-center gap-1">
                        <button type="button" class="lot-drag-handle text-indigo-400 hover:text-indigo-600 cursor-grab" title="Drag whole Lot to reorder"><i class="fa-solid fa-grip-vertical"></i></button>
                        <span class="px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 text-[10px] font-extrabold rounded">LOT</span>
                        <input type="hidden" data-field="unit" value="Lot">
                        <input type="hidden" data-field="is_lot" value="1">
                    </div>`;

                rows[0].insertBefore(merged, rows[0].children[1]);
                setName(merged, '[data-field="unit"]', `items[${groupIndex}][unit]`);
                setName(merged, '[data-field="is_lot"]', `items[${groupIndex}][is_lot]`);

                rows.forEach((row, subIndex) => {
                    if (row.querySelector('.line-no')) row.querySelector('.line-no').textContent = lineNo;
                    if (row.querySelector('.line-no-input')) row.querySelector('.line-no-input').value = lineNo;
                    setName(row, '.line-no-input', `items[${groupIndex}][sub_items][${subIndex}][line_no]`);
                    ['description', 'quantity', 'cost'].forEach(k => setName(row, `[data-field="${k}"]`, `items[${groupIndex}][sub_items][${subIndex}][${k}]`));
                    lineNo++;
                });
            }
            groupIndex++;
        });

        calculateTotals();
        syncItemsToPreview();
    }

    function calculateTotals() {
        let total = 0;
        table.querySelectorAll('tbody.item-group tr.draggable-row').forEach(row => {
            const qty = parseFloat(row.querySelector('[data-field="quantity"]')?.value) || 0;
            const cost = parseFloat(row.querySelector('[data-field="cost"]')?.value) || 0;
            const amount = qty * cost;
            total += amount;
            const out = row.querySelector('.item-total');
            if (out) out.textContent = money(amount);
        });
        const grandTotal = document.getElementById('grand-total');
        const abc = document.getElementById('abc-amount');
        if (grandTotal) grandTotal.textContent = money(total);
        if (abc) abc.value = total.toFixed(2);
        return total;
    }

    function readItems() {
        return [...table.querySelectorAll('tbody.item-group')].map(group => {
            if (group.dataset.type === 'lot') {
                return {
                    is_lot: 1,
                    unit: 'Lot',
                    sub_items: [...group.querySelectorAll('tr.draggable-row')].map(r => ({
                        description: r.querySelector('[data-field="description"]')?.value || '',
                        quantity: r.querySelector('[data-field="quantity"]')?.value || 0,
                        cost: r.querySelector('[data-field="cost"]')?.value || 0,
                        line_no: r.querySelector('.line-no-input')?.value || ''
                    }))
                };
            }
            const r = group.querySelector('tr.draggable-row');
            return {
                is_lot: 0,
                unit: r.querySelector('[data-field="unit"]')?.value || '',
                description: r.querySelector('[data-field="description"]')?.value || '',
                quantity: r.querySelector('[data-field="quantity"]')?.value || 0,
                cost: r.querySelector('[data-field="cost"]')?.value || 0,
                line_no: r.querySelector('.line-no-input')?.value || ''
            };
        });
    }

    function renderItemsFromTemplate(tbody, items) {
        if (!tbody) return;
        let templateRow = tbody.dataset.rowTemplate;
        if (!templateRow) {
            const row = tbody.querySelector('tr');
            if (!row) return;
            templateRow = row.outerHTML;
            tbody.dataset.rowTemplate = templateRow;
        }

        const fragment = document.createDocumentFragment();

        items.forEach((item, i) => {
            if (Number(item.is_lot)) {
                const subItems = item.sub_items || [];
                const rowSpanCount = subItems.length;

                subItems.forEach((s, j) => {
                    const qty = parseFloat(s.quantity) || 0, cost = parseFloat(s.cost) || 0;
                    const temp = document.createElement('tbody');
                    let html = templateRow;

                    html = html.replaceAll('[LN]', j === 0 ? esc(`${i + 1}.`) : '')
                        .replaceAll('[PN]', j === 0 ? esc(`${i + 1}.`) : '')
                        .replaceAll('[UNIT]', j === 0 ? 'LOT' : '')
                        .replaceAll('[DESCRIPTION]', esc(s.description))
                        .replaceAll('[QTY]', esc(s.quantity))
                        .replaceAll('[COST]', money(s.cost))
                        .replaceAll('[LINE-COST]', money(qty * cost));

                    temp.innerHTML = html;
                    const row = temp.firstElementChild;

                    if (row) {
                        if (j === 0) {
                            const cells = row.querySelectorAll('td, th');
                            if (cells[0]) cells[0].setAttribute('rowspan', rowSpanCount);
                            if (cells[1]) cells[1].setAttribute('rowspan', rowSpanCount);
                        } else {
                            const cells = row.querySelectorAll('td, th');
                            if (cells[0]) cells[0].remove();
                            if (cells[1]) cells[1].remove();
                        }
                        fragment.appendChild(row);
                    }
                });
            } else {
                const qty = parseFloat(item.quantity) || 0, cost = parseFloat(item.cost) || 0;
                const temp = document.createElement('tbody');
                let html = templateRow;

                html = html.replaceAll('[LN]', esc(i + 1))
                    .replaceAll('[PN]', esc(i + 1))
                    .replaceAll('[UNIT]', esc(item.unit))
                    .replaceAll('[DESCRIPTION]', esc(item.description))
                    .replaceAll('[QTY]', esc(item.quantity))
                    .replaceAll('[COST]', money(item.cost))
                    .replaceAll('[LINE-COST]', money(qty * cost));

                temp.innerHTML = html;
                if (temp.firstElementChild) fragment.appendChild(temp.firstElementChild);
            }
        });
        tbody.replaceChildren(fragment);
    }

    function getData(formObj = {}) {
        const value = name => form.querySelector(`[name="${name}"]`)?.value || '';
        const officeText = form.querySelector('[name="end_user_office_id"] option:checked')?.textContent.trim() || '';
        const officeCode = officeText.match(/\(([^)]+)\)/)?.[1] || officeText;

        return {
            purpose: value('purpose'),
            office: officeCode,
            fund_cluster: value('fund_cluster'),
            responsibility_code: value('responsibility_code'),
            pr_date: value('pr_date'),
            pr_number: formObj.pr_number || value('pr_number'),
            procurement_mode: modeSelect.selectedOptions[0]?.textContent.trim() || '',
            name: formObj.name || 'Purchase Request',
            code: formObj.code || '',
            grand_total: money(calculateTotals())
        };
    }

    function populateTemplate(template, data) {
        return template.replace(/\{\{\s*(\w+)\s*\}\}/g, (_, key) => esc(data[key] ?? ''));
    }

    function updatePaperTotals(paper, total) {
        paper.querySelectorAll('[data-pr-total]').forEach(el => el.textContent = money(total));
    }

    function renderPaper(paper, template, f) {
        paper.dataset.template = template;
        paper.dataset.formIndex = String(currentForms.indexOf(f));
        paper.innerHTML = populateTemplate(template, getData(f));
        const tbody = paper.querySelector('[data-each="procurement.items"]');
        if (tbody) renderItemsFromTemplate(tbody, readItems());
        updatePaperTotals(paper, calculateTotals());
    }

    function syncItemsToPreview() {
        const items = readItems(), total = calculateTotals();
        document.querySelectorAll('.form-preview-paper,.form-preview-paper-modal,[data-form-preview-modal]').forEach(paper => {
            const tbody = paper.querySelector('[data-each="procurement.items"]');
            if (tbody) renderItemsFromTemplate(tbody, items);
            updatePaperTotals(paper, total);
        });
    }

    function loadFormTemplate(formId) {
        if (!formId) return Promise.reject(new Error('No Form ID provided'));
        if (!templateCache.has(formId)) {
            const promise = fetch(`/admin/forms/${encodeURIComponent(formId)}/file`, { headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' } }).then(r => {
                if (!r.ok) throw Error(`Form Template HTTP ${r.status}`);
                return r.text();
            }).catch(e => { templateCache.delete(formId); throw e; });
            templateCache.set(formId, promise);
        }
        return templateCache.get(formId);
    }

    const pageSizes = { A4: [210, 297], A3: [297, 420], A5: [148, 210], LETTER: [215.9, 279.4], LEGAL: [215.9, 355.6], FOLIO: [215.9, 330.2] };
    function pageData(f) {
        let c = f.configuration || {};
        if (typeof c === 'string') { try { c = JSON.parse(c) } catch { c = {} } }
        const size = String(c.page_size || c.pageSize || 'A4').toUpperCase(), d = pageSizes[size] || pageSizes.A4, m = c.margins || {};
        return { size, d, mt: parseFloat(m.top) || 0, mr: parseFloat(m.right) || 0, mb: parseFloat(m.bottom) || 0, ml: parseFloat(m.left) || 0 };
    }

    let isUpdatingPreview = false;
    function schedulePreviewUpdate() {
        if (isUpdatingPreview) return;
        if (previewUpdateFrame) cancelAnimationFrame(previewUpdateFrame);

        previewUpdateFrame = requestAnimationFrame(() => {
            previewUpdateFrame = null;
            isUpdatingPreview = true;
            try {
                document.querySelectorAll('#dynamic-forms-list .form-preview-paper').forEach(paper => {
                    const f = currentForms[Number(paper.dataset.formIndex)];
                    if (f && paper.dataset.template) renderPaper(paper, paper.dataset.template, f);
                });
                const modalPaper = modalBody?.querySelector('.form-preview-paper-modal');
                if (modalPaper && activeModalForm && modalPaper.dataset.template) {
                    renderPaper(modalPaper, modalPaper.dataset.template, activeModalForm);
                }
            } finally {
                isUpdatingPreview = false;
            }
        });
    }

    async function renderFormCards(forms) {
        formsList.innerHTML = '';
        try {
            for (let i = 0; i < forms.length; i++) {
                const f = forms[i], p = pageData(f), w = p.d[0] * 3.779527559, h = p.d[1] * 3.779527559, card = document.createElement('div');
                card.className = 'space-y-3';
                card.innerHTML = `
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold truncate">${esc(f.name || 'Untitled Form')}</h3>
                            <p class="text-[10px] text-slate-400">${esc(f.code || '')} · ${esc(p.size)}</p>
                        </div>
                        <div class="flex gap-1">
                            <button type="button" class="btn-view-fullscreen p-2 rounded-lg hover:text-indigo-600" data-index="${i}" title="View Fullscreen"><i class="fa-solid fa-expand"></i></button>
                            ${f.is_end_phase ? `<button type="button" class="btn-compile-pdf p-2 rounded-lg bg-indigo-600 text-white" data-form-id="${esc(f.id)}" data-index="${i}" title="Compile Form"><i class="fa-solid fa-file-pdf"></i></button>` : ''}
                        </div>
                    </div>
                    <div class="form-preview-viewport w-full overflow-hidden border bg-slate-100 dark:bg-slate-800 rounded-lg">
                        <div class="form-preview-scale relative" style="width:${w}px;height:${h}px">
                            <div class="form-preview-paper bg-white text-black shadow-inner overflow-hidden" data-form-index="${i}" style="width:${p.d[0]}mm;height:${p.d[1]}mm;padding:${p.mt}mm ${p.mr}mm ${p.mb}mm ${p.ml}mm;box-sizing:border-box;transform-origin:top left"></div>
                        </div>
                    </div>
                `;
                formsList.appendChild(card);
                const viewport = card.querySelector('.form-preview-viewport'), scaleWrap = card.querySelector('.form-preview-scale'), paper = card.querySelector('.form-preview-paper');
                const template = await loadFormTemplate(f.id);
                renderPaper(paper, template, f);
                const resize = () => { const width = viewport.clientWidth; if (!width) return; const scale = Math.min(1, width / w); paper.style.transform = `scale(${scale})`; scaleWrap.style.width = `${w * scale}px`; scaleWrap.style.height = `${h * scale}px`; };
                requestAnimationFrame(resize);
                if (window.ResizeObserver) { const observer = new ResizeObserver(resize); observer.observe(viewport); card._observer = observer; }
            }
            syncItemsToPreview();
        } catch (e) {
            console.error('Form preview rendering failed:', e);
            formsList.innerHTML = '<p class="text-sm text-rose-500">Unable to load form HTML templates from storage. Check server connection.</p>';
        }
    }

    function setViewState(state) {
        Object.values(states).forEach(el => { if (el) { el.classList.add('hidden'); el.classList.remove('flex'); } });
        formsList.classList.add('hidden');
        if (state === 'noMode' && states.noMode) states.noMode.classList.remove('hidden');
        if (state === 'loading' && states.loading) { states.loading.classList.remove('hidden'); states.loading.classList.add('flex'); }
        if (state === 'noForms' && states.noForms) { states.noForms.classList.remove('hidden'); states.noForms.classList.add('flex'); }
        if (state === 'success') formsList.classList.remove('hidden');
    }

    async function fetchModeForms(id) {
        const requestId = ++formsRequestId;
        if (!id) { currentForms = []; formsList.innerHTML = ''; setViewState('noMode'); return; }
        setViewState('loading');
        try {
            const r = await fetch(`${formsApiUrl}/${encodeURIComponent(id)}/forms?current_phase=1`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!r.ok) throw Error(`Failed to fetch mode forms (${r.status})`);
            const data = await r.json();
            if (requestId !== formsRequestId) return;
            currentForms = data.forms || [];
            if (!currentForms.length) { formsList.innerHTML = ''; setViewState('noForms'); return; }
            await renderFormCards(currentForms);
            if (requestId !== formsRequestId) return;
            setViewState('success');
        } catch (e) {
            if (requestId !== formsRequestId) return;
            console.error('AJAX Error:', e);
            currentForms = [];
            setViewState('noForms');
        }
    }

    // --- FULLSCREEN MODAL HANDLERS ---
    async function openFullscreenModal(index) {
        const f = currentForms[index];
        if (!f || !modal || !modalTitle || !modalBody) return;

        activeModalForm = f;
        if (modalTitle) modalTitle.textContent = `${f.name || 'Form Preview'} (${f.code || ''})`;

        const p = pageData(f);
        const w = p.d[0] * 3.779527559;
        const h = p.d[1] * 3.779527559;

        modalBody.innerHTML = `
            <div class="modal-viewport w-full h-full overflow-auto flex justify-center items-start p-4">
                <div class="modal-scale-wrap relative" style="width:${w}px;height:${h}px">
                    <div class="form-preview-paper-modal bg-white text-black shadow-2xl overflow-hidden" data-form-index="${index}" style="width:${p.d[0]}mm;height:${p.d[1]}mm;padding:${p.mt}mm ${p.mr}mm ${p.mb}mm ${p.ml}mm;box-sizing:border-box;transform-origin:top left"></div>
                </div>
            </div>
        `;

        const viewport = modalBody.querySelector('.modal-viewport');
        const scaleWrap = modalBody.querySelector('.modal-scale-wrap');
        const modalPaper = modalBody.querySelector('.form-preview-paper-modal');

        const template = await loadFormTemplate(f.id);
        renderPaper(modalPaper, template, f);

        const resizeModalPaper = () => {
            if (!viewport || !modalPaper || !scaleWrap) return;
            const availableWidth = viewport.clientWidth - 32;
            if (!availableWidth) return;
            const scale = Math.min(1.2, availableWidth / w);
            modalPaper.style.transform = `scale(${scale})`;
            scaleWrap.style.width = `${w * scale}px`;
            scaleWrap.style.height = `${h * scale}px`;
        };

        requestAnimationFrame(resizeModalPaper);

        if (window.ResizeObserver) {
            if (modalResize) modalResize.disconnect();
            modalResize = new ResizeObserver(resizeModalPaper);
            modalResize.observe(viewport);
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeFullscreenModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (modalBody) modalBody.innerHTML = '';
        activeModalForm = null;
        if (modalResize) {
            modalResize.disconnect();
            modalResize = null;
        }
    }

    // --- COMPILE PDF HANDLER ---
    async function compilePDF(formId, formIndex) {
        let f = currentForms.find(item => String(item.id) === String(formId));
        if (!f && formIndex !== undefined && formIndex !== null) {
            f = currentForms[formIndex];
        }
        if (!f) return;

        try {
            const template = await loadFormTemplate(f.id);
            const p = pageData(f);

            const tempPaper = document.createElement('div');
            tempPaper.className = 'form-preview-paper bg-white text-black';
            tempPaper.style.cssText = `width:${p.d[0]}mm;min-height:${p.d[1]}mm;padding:${p.mt}mm ${p.mr}mm ${p.mb}mm ${p.ml}mm;box-sizing:border-box;background:#fff;color:#000;font-family:sans-serif;margin:0 auto;`;

            renderPaper(tempPaper, template, f);

            const printWindow = window.open('', '_blank', 'width=950,height=1000');
            if (!printWindow) {
                alert('Please allow popups to compile and print/download the PDF.');
                return;
            }

            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${esc(f.name || 'Document')} - ${esc(f.code || 'PR')}</title>
                    <script src="https://cdn.tailwindcss.com"></script>
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
                    <style>
                        @page {
                            size: ${p.size.toLowerCase()} portrait;
                            margin: 0;
                        }
                        body {
                            margin: 0;
                            padding: 0;
                            background: #f8fafc;
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                        @media print {
                            body { background: #fff; padding: 0; }
                            .no-print { display: none !important; }
                            .print-card { shadow: none !important; margin: 0 !important; }
                        }
                    </style>
                </head>
                <body class="p-6 flex flex-col items-center min-h-screen">
                    <div class="no-print mb-4 flex gap-3">
                        <button onclick="window.print()" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow cursor-pointer flex items-center gap-2">
                            <i class="fa-solid fa-print"></i> Print / Save as PDF
                        </button>
                        <button onclick="window.close()" class="px-4 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs rounded-xl cursor-pointer">
                            Close
                        </button>
                    </div>
                    <div class="print-card shadow-2xl bg-white border border-slate-200 rounded-lg overflow-hidden">
                        ${tempPaper.outerHTML}
                    </div>
                </body>
                </html>
            `);

            printWindow.document.close();
            printWindow.focus();

            setTimeout(() => {
                printWindow.print();
            }, 400);
        } catch (err) {
            console.error('Failed to compile PDF:', err);
            alert('Failed to load form template for PDF compilation.');
        }
    }

    // Action Menu Listeners
    function closeItemMenus() {
        floatingMenu.classList.remove('is-open');
        activeMenuToggle?.setAttribute('aria-expanded', 'false');
        activeMenuRow = null; activeMenuToggle = null;
    }

    function positionItemMenu(row, toggle) {
        if (!row || !toggle) return;
        const rr = row.getBoundingClientRect(), tr = toggle.getBoundingClientRect(), gap = 8, pad = 8;
        floatingMenu.style.visibility = 'hidden';
        floatingMenu.classList.add('is-open');
        const mr = floatingMenu.getBoundingClientRect();
        let left = rr.right + gap;
        if (left + mr.width > window.innerWidth - pad) left = rr.left - mr.width - gap;
        left = Math.max(pad, Math.min(left, window.innerWidth - mr.width - pad));
        let top = tr.top + tr.height / 2 - mr.height / 2;
        if (top + mr.height > window.innerHeight - pad) top = window.innerHeight - mr.height - pad;
        top = Math.max(pad, top);
        floatingMenu.style.left = `${left}px`; floatingMenu.style.top = `${top}px`; floatingMenu.style.visibility = 'visible';
        activeMenuRow = row; activeMenuToggle = toggle;
        toggle.setAttribute('aria-expanded', 'true');
    }

    table.addEventListener('click', e => {
        const toggle = e.target.closest('.item-menu-toggle');
        if (!toggle) return;
        e.stopPropagation();
        const row = toggle.closest('tr.draggable-row');
        if (!row) return;

        if (activeMenuToggle === toggle && floatingMenu.classList.contains('is-open')) {
            closeItemMenus();
        } else {
            positionItemMenu(row, toggle);
        }
    });

    table.addEventListener('input', e => {
        if (e.target.matches('[data-field="unit"],[data-field="description"],[data-field="quantity"],[data-field="cost"]')) {
            calculateTotals();
            schedulePreviewUpdate();
        }
    });

    floatingMenu.addEventListener('click', e => {
        const action = e.target.closest('[data-action]');
        if (!action || !activeMenuRow) return;
        e.preventDefault(); e.stopPropagation();
        const row = activeMenuRow, a = action.dataset.action;
        closeItemMenus();
        if (!row.isConnected) return;
        if (a === 'delete-row') {
            const group = row.closest('tbody.item-group');
            row.remove();
            if (group && !group.querySelector('.item-row,.lot-sub-item')) group.remove();
            refreshTableStructure();
            schedulePreviewUpdate();
            return;
        }
        addAt(row, a.startsWith('lot-') ? 'lot' : 'item', a.endsWith('above') ? 'above' : 'below');
    });

    document.addEventListener('click', e => {
        const fullBtn = e.target.closest('.btn-view-fullscreen');
        if (fullBtn) {
            e.preventDefault();
            e.stopPropagation();
            const index = Number(fullBtn.dataset.index);
            openFullscreenModal(index);
            return;
        }

        const compileBtn = e.target.closest('.btn-compile-pdf');
        if (compileBtn) {
            e.preventDefault();
            e.stopPropagation();
            const formId = compileBtn.dataset.formId;
            const formIndex = compileBtn.dataset.index;
            compilePDF(formId, formIndex);
            return;
        }

        if (!e.target.closest('#items-table .item-menu-toggle') && !e.target.closest('#floating-item-action-menu')) closeItemMenus();
    });

    document.getElementById('close-modal-btn')?.addEventListener('click', closeFullscreenModal);

    modal?.addEventListener('click', e => {
        if (e.target === modal) closeFullscreenModal();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            closeFullscreenModal();
        }
    });

    function addAt(reference, kind, where) {
        const group = reference.closest('tbody.item-group');
        if (!group) return;
        if (kind === 'lot') {
            const lot = createLotGroup();
            table.insertBefore(lot, where === 'above' ? group : group.nextSibling);
        } else if (group.dataset.type === 'lot') {
            const row = createSubRow();
            group.insertBefore(row, where === 'above' ? reference : reference.nextSibling);
        } else {
            const item = createItemGroup();
            table.insertBefore(item, where === 'above' ? group : group.nextSibling);
        }
        refreshTableStructure();
        schedulePreviewUpdate();
    }

    document.getElementById('add-item-btn')?.addEventListener('click', () => {
        table.appendChild(createItemGroup()); refreshTableStructure(); schedulePreviewUpdate();
    });
    document.getElementById('add-lot-btn')?.addEventListener('click', () => {
        table.appendChild(createLotGroup()); refreshTableStructure(); schedulePreviewUpdate();
    });

    // --- Drag Handle Tracking on Mousedown ---
    table.addEventListener('mousedown', e => {
        currentDragHandle = e.target.closest('.lot-drag-handle, .drag-handle, .lot-merged-unit');
    });

    // --- Drag and Drop Events ---
    table.addEventListener('dragstart', e => {
        const row = e.target.closest('tr.draggable-row');
        if (!row) return;

        closeItemMenus();

        const isLotDrag = currentDragHandle && (
            currentDragHandle.classList.contains('lot-drag-handle') ||
            currentDragHandle.classList.contains('lot-merged-unit')
        );

        if (isLotDrag) {
            dragType = 'lot';
            draggedTarget = row.closest('tbody.item-group');
            draggedTarget.classList.add('is-dragging');
        } else {
            dragType = 'row';
            draggedTarget = row;
            draggedTarget.classList.add('is-dragging');
        }

        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', 'dragging');
    });

    table.addEventListener('dragend', () => {
        draggedTarget?.classList.remove('is-dragging');
        draggedTarget = null;
        dragType = null;
        currentDragHandle = null;
        table.querySelectorAll('.drop-target-above, .drop-target-below, .drop-target-inside').forEach(x => {
            x.classList.remove('drop-target-above', 'drop-target-below', 'drop-target-inside');
        });
    });

    table.addEventListener('dragover', e => {
        if (!draggedTarget) return;
        e.preventDefault();

        table.querySelectorAll('.drop-target-above, .drop-target-below, .drop-target-inside').forEach(x => {
            x.classList.remove('drop-target-above', 'drop-target-below', 'drop-target-inside');
        });

        if (dragType === 'lot') {
            const targetGroup = e.target.closest('tbody.item-group');
            if (!targetGroup || targetGroup === draggedTarget) return;

            const rect = targetGroup.getBoundingClientRect();
            const above = e.clientY < rect.top + rect.height / 2;
            targetGroup.classList.add(above ? 'drop-target-above' : 'drop-target-below');
        } else {
            const dstGroup = e.target.closest('tbody.item-group');
            if (!dstGroup) return;

            const targetRow = e.target.closest('tr.draggable-row');

            if (dstGroup.dataset.type === 'lot') {
                if (targetRow && targetRow !== draggedTarget && targetRow.closest('tbody') === dstGroup) {
                    const rect = targetRow.getBoundingClientRect();
                    const above = e.clientY < rect.top + rect.height / 2;
                    targetRow.classList.add(above ? 'drop-target-above' : 'drop-target-below');
                } else {
                    dstGroup.classList.add('drop-target-inside');
                }
            } else {
                if (targetRow && targetRow !== draggedTarget) {
                    const rect = targetRow.getBoundingClientRect();
                    const above = e.clientY < rect.top + rect.height / 2;
                    targetRow.classList.add(above ? 'drop-target-above' : 'drop-target-below');
                } else if (dstGroup !== draggedTarget.closest('tbody.item-group')) {
                    const rect = dstGroup.getBoundingClientRect();
                    const above = e.clientY < rect.top + rect.height / 2;
                    dstGroup.classList.add(above ? 'drop-target-above' : 'drop-target-below');
                }
            }
        }
    });

    table.addEventListener('drop', e => {
        if (!draggedTarget) return;
        e.preventDefault();

        table.querySelectorAll('.drop-target-above, .drop-target-below, .drop-target-inside').forEach(x => {
            x.classList.remove('drop-target-above', 'drop-target-below', 'drop-target-inside');
        });

        if (dragType === 'lot') {
            const targetGroup = e.target.closest('tbody.item-group');
            if (!targetGroup || targetGroup === draggedTarget) return;

            const rect = targetGroup.getBoundingClientRect();
            const above = e.clientY < rect.top + rect.height / 2;

            if (above) targetGroup.before(draggedTarget);
            else targetGroup.after(draggedTarget);
        } else {
            const dstGroup = e.target.closest('tbody.item-group');
            if (!dstGroup) return;

            const targetRow = e.target.closest('tr.draggable-row');
            const srcGroup = draggedTarget.closest('tbody.item-group');

            if (dstGroup.dataset.type === 'lot') {
                // Dragging into a lot
                if (targetRow && targetRow !== draggedTarget && targetRow.closest('tbody') === dstGroup) {
                    const rect = targetRow.getBoundingClientRect();
                    const above = e.clientY < rect.top + rect.height / 2;
                    if (above) targetRow.before(draggedTarget);
                    else targetRow.after(draggedTarget);
                } else {
                    dstGroup.appendChild(draggedTarget);
                }
            } else {
                // Dragging out of a lot or between standalone items
                if (srcGroup === dstGroup) {
                    if (targetRow && targetRow !== draggedTarget) {
                        const rect = targetRow.getBoundingClientRect();
                        const above = e.clientY < rect.top + rect.height / 2;
                        if (above) dstGroup.before(srcGroup);
                        else dstGroup.after(srcGroup);
                    }
                } else {
                    const newGroup = document.createElement('tbody');
                    newGroup.className = 'item-group';
                    newGroup.dataset.type = 'item';
                    newGroup.appendChild(draggedTarget);

                    const rect = dstGroup.getBoundingClientRect();
                    const above = e.clientY < rect.top + rect.height / 2;

                    if (above) dstGroup.before(newGroup);
                    else dstGroup.after(newGroup);
                }
            }
        }

        // Clean up empty groups (automatically deletes a lot if all items are dragged out)
        table.querySelectorAll('tbody.item-group').forEach(group => {
            if (!group.querySelector('tr.draggable-row')) group.remove();
        });

        refreshTableStructure();
        schedulePreviewUpdate();
    });

    // Layout Collapse/Expand & Resize
    if (resizer) {
        resizer.addEventListener('pointerdown', e => {
            if (isMobile() || sidebar.classList.contains('sidebar-collapsed')) return;
            resizeState = { x: e.clientX, width: sidebar.getBoundingClientRect().width };
            resizer.classList.add('resizing'); document.body.classList.add('select-none', 'cursor-col-resize');
            resizer.setPointerCapture(e.pointerId); e.preventDefault();
        });
        resizer.addEventListener('pointermove', e => { if (resizeState) setWidth(resizeState.width + resizeState.x - e.clientX); });
        function stopResize() {
            if (!resizeState) return;
            resizeState = null; resizer.classList.remove('resizing'); document.body.classList.remove('select-none', 'cursor-col-resize');
        }
        resizer.addEventListener('pointerup', stopResize);
        resizer.addEventListener('pointercancel', stopResize);
    }

    function setWidth(w) {
        if (isMobile()) { sidebar.style.width = '100%'; sidebar.style.minWidth = '100%'; sidebar.style.flexBasis = '100%'; return; }
        w = Math.max(320, Math.min(750, w));
        sidebar.style.width = w + 'px'; sidebar.style.minWidth = w + 'px'; sidebar.style.flexBasis = w + 'px';
        localStorage.setItem('procurementPreviewWidth', w);
    }

    toggleBtn?.addEventListener('click', () => {
        sidebar.classList.add('sidebar-collapsed');
        if (expandTab) expandTab.classList.remove('hidden');
    });

    expandTab?.addEventListener('click', () => {
        sidebar.classList.remove('sidebar-collapsed');
        if (expandTab) expandTab.classList.add('hidden');
        if (!isMobile()) setWidth(parseInt(localStorage.getItem('procurementPreviewWidth'), 10) || 480);
    });

    modeSelect.addEventListener('change', () => fetchModeForms(modeSelect.value));
    form.addEventListener('input', schedulePreviewUpdate);
    form.addEventListener('change', schedulePreviewUpdate);

    // --- INITIALIZATION STEP ---
    renderInitialItems(initialItems);
    refreshTableStructure();
    if (modeSelect.value != 0) fetchModeForms(modeSelect.value);
    else setViewState('noMode');
};
