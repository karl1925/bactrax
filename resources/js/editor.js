const MM_TO_PX = 3.779527559;
const INCH_TO_MM = 25.4;

const PAGE_SIZES = {
    'A4': { width: 210, height: 297 },
    'A3': { width: 297, height: 420 },
    'A5': { width: 148, height: 210 },
    'Letter': { width: 215.9, height: 279.4 },
    'Legal': { width: 215.9, height: 355.6 },
    'Folio': { width: 215.9, height: 330.2 }
};

window.editorInstance = null;
window.saving = false;

// Default Header / Footer Height set to 0.8 inches (approx 20.32 mm => 76.8px)
let headerHeightPx = 0.8 * INCH_TO_MM * MM_TO_PX;
let footerHeightPx = 0.8 * INCH_TO_MM * MM_TO_PX;

/* PAGINATION ENGINE */
window.updatePageLayout = function () {
    if (!window.editorInstance) return;

    const pageSize = document.getElementById('cfgPageSize').value;
    const hasHeader = document.getElementById('cfgEnableHeader').checked;
    const diffFirstHeader = document.getElementById('cfgDiffFirstHeader').checked;
    const hasFooter = document.getElementById('cfgEnableFooter').checked;
    const diffFirstFooter = document.getElementById('cfgDiffFirstFooter').checked;

    // Dynamic Margin calculations: Default is 1 inch (25.4mm), Top/Bottom drops to 0.2 inch (5.08mm) when Header/Footer inserted
    const defaultMarginMm = 25.4;
    const reducedMarginMm = 5.08;

    let topMm = hasHeader ? reducedMarginMm : parseFloat(document.getElementById('marginTop').value) || defaultMarginMm;
    let bottomMm = hasFooter ? reducedMarginMm : parseFloat(document.getElementById('marginBottom').value) || defaultMarginMm;
    let rightMm = parseFloat(document.getElementById('marginRight').value) || defaultMarginMm;
    let leftMm = parseFloat(document.getElementById('marginLeft').value) || defaultMarginMm;

    // Keep inputs in sync with calculated margins
    document.getElementById('marginTop').value = topMm;
    document.getElementById('marginBottom').value = bottomMm;

    const widthPx = PAGE_SIZES[pageSize].width * MM_TO_PX;
    const heightPx = PAGE_SIZES[pageSize].height * MM_TO_PX;
    const mtPx = topMm * MM_TO_PX;
    const mrPx = rightMm * MM_TO_PX;
    const mbPx = bottomMm * MM_TO_PX;
    const mlPx = leftMm * MM_TO_PX;

    const body = window.editorInstance.getBody();
    body.style.width = `${widthPx}px`;
    body.style.minHeight = `${heightPx}px`;
    body.style.padding = `${mtPx}px ${mrPx}px ${mbPx}px ${mlPx}px`;

    body.style.setProperty('--margin-top', `${mtPx}px`);
    body.style.setProperty('--margin-right', `${mrPx}px`);
    body.style.setProperty('--margin-bottom', `${mbPx}px`);
    body.style.setProperty('--margin-left', `${mlPx}px`);
    body.style.setProperty('--header-height', `${headerHeightPx}px`);
    body.style.setProperty('--footer-height', `${footerHeightPx}px`);

    calculatePageBreaks(heightPx, mtPx, mbPx, mlPx, mrPx, hasHeader, hasFooter, diffFirstHeader, diffFirstFooter);
};

function calculatePageBreaks(pageHeightPx, mtPx, mbPx, mlPx, mrPx, hasHeader, hasFooter, diffFirstHeader, diffFirstFooter) {
    const body = window.editorInstance.getBody();

    const existingGaps = body.querySelectorAll('.page-break-gap, .header-wrapper, .footer-wrapper');
    existingGaps.forEach(el => el.remove());

    const GAP_HEIGHT = 10;

    const usableHeightFirstPage = pageHeightPx - mtPx - mbPx - (hasHeader ? headerHeightPx : 0) - (hasFooter ? footerHeightPx : 0);
    const usableHeightSubsequent = pageHeightPx - mtPx - mbPx - (hasHeader ? headerHeightPx : 0) - (hasFooter ? footerHeightPx : 0);

    let pageCount = 1;

    const addPageBreakBefore = node => {
        if (hasFooter) {
            const footerLabel = (pageCount === 1 && diffFirstFooter) ? 'First Page Footer' : 'Footer';
            node.insertAdjacentHTML('beforebegin', `
                <div class="footer-wrapper mceNonEditable" contenteditable="false" style="height: ${footerHeightPx}px;">
                    <div class="footer-resizer mceNonEditable" contenteditable="false"></div>
                    <div class="editable-footer mceEditable" contenteditable="true" data-placeholder="${footerLabel}">${footerLabel}</div>
                </div>`);
        }
        node.insertAdjacentHTML('beforebegin', `<div class="page-break-gap" style="height: ${GAP_HEIGHT}px; margin-top: ${mbPx}px; margin-bottom: ${mtPx}px;"></div>`);
        pageCount++;
        if (hasHeader) {
            node.insertAdjacentHTML('beforebegin', `
                <div class="header-wrapper mceNonEditable" contenteditable="false" style="height: ${headerHeightPx}px;">
                    <div class="editable-header mceEditable" contenteditable="true" data-placeholder="Header">Header</div>
                    <div class="header-resizer mceNonEditable" contenteditable="false"></div>
                </div>`);
        }
    };

    if (hasHeader) {
        let headerLabel = diffFirstHeader ? 'First Page Header' : 'Header';
        let headerHtml = `
                <div class="header-wrapper mceNonEditable" contenteditable="false" style="height: ${headerHeightPx}px;">
                    <div class="editable-header mceEditable" contenteditable="true" data-placeholder="${headerLabel}">${headerLabel}</div>
                    <div class="header-resizer mceNonEditable" contenteditable="false"></div>
                </div>`;
        body.insertAdjacentHTML('afterbegin', headerHtml);
    }

    let nodes = Array.from(body.children);
    let accumulatedHeight = 0;

    nodes.forEach(node => {
        if (node.classList.contains('header-wrapper') || node.classList.contains('footer-wrapper') || node.classList.contains('page-break-gap')) return;

        const computed = window.editorInstance.getWin().getComputedStyle(node);
        const marginTop = parseFloat(computed.marginTop) || 0;
        const marginBottom = parseFloat(computed.marginBottom) || 0;
        const blockHeight = node.offsetHeight + marginTop + marginBottom;
        const breakBefore = node.matches('[data-page-break-before="always"]')
            || ['page', 'always', 'left', 'right', 'recto', 'verso'].includes(computed.breakBefore)
            || ['always', 'left', 'right'].includes(computed.pageBreakBefore);
        let limit = pageCount === 1 ? usableHeightFirstPage : usableHeightSubsequent;

        if ((breakBefore && accumulatedHeight > 0) || (accumulatedHeight + blockHeight > limit && accumulatedHeight > 0)) {
            addPageBreakBefore(node);
            accumulatedHeight = 0;
        }
        accumulatedHeight += blockHeight;
    });

    if (hasFooter) {
        let footerLabel = (pageCount === 1 && diffFirstFooter) ? 'First Page Footer' : 'Footer';
        let footerHtml = `
                <div class="footer-wrapper mceNonEditable" contenteditable="false" style="height: ${footerHeightPx}px;">
                    <div class="footer-resizer mceNonEditable" contenteditable="false"></div>
                    <div class="editable-footer mceEditable" contenteditable="true" data-placeholder="${footerLabel}">${footerLabel}</div>
                </div>`;
        body.insertAdjacentHTML('beforeend', footerHtml);
    }
}

/* DARK MODE TOGGLE */
document.getElementById('btnToggleTheme').addEventListener('click', function () {
    document.body.classList.toggle('dark');
    const isDark = document.body.classList.contains('dark');

    if (window.editorInstance) {
        const iframeHtml = window.editorInstance.getDoc().documentElement;
        if (isDark) {
            iframeHtml.classList.add('dark-mode');
        } else {
            iframeHtml.classList.remove('dark-mode');
        }
    }
});

window.setIcon = function (state) {
    const $button = $('#saveBtn');
    switch (state) {
        case 'saving':
            $button
                .attr('title', 'Saving')
                .addClass('disabled')
                .html('<i class="bi bi-cloud-arrow-up text-lg !text-amber-500"></i>');
            break;
        case 'saved':
            $button
                .attr('title', 'Saved')
                .removeClass('disabled')
                .html('<i class="bi bi-cloud-check-fill text-lg !text-lime-500"></i>');
            break;
        default:
            $button
                .attr('title', 'Not Saved')
                .prop('disabled', false)
                .html('<i class="bi bi-cloud text-lg !text-red-500"></i>');
            break;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const ZOOM_MIN = 0.10;
    const ZOOM_MAX = 2.00;
    const ZOOM_STEP = 0.10;
    let zoomScale = 1;

    const configSidebar = document.getElementById('configSidebar');
    const iconBtns = configSidebar.querySelectorAll('.sidebar-icon-btn');
    const subpanels = configSidebar.querySelectorAll('.subpanel');

    const scrollContainer = document.querySelector('.gdocs-editor-body');
    const autoScrollBtn = document.getElementById('btnToggleAutoScroll');
    let isAutoScrolling = false;
    let scrollAnimationId = null;
    const scrollSpeed = 1.2;

    function stepScroll() {
        if (!isAutoScrolling || !scrollContainer) return;

        const maxScroll = scrollContainer.scrollHeight - scrollContainer.clientHeight;
        if (scrollContainer.scrollTop >= maxScroll - 1) {
            stopAutoScroll();
            return;
        }

        scrollContainer.scrollTop += scrollSpeed;
        scrollAnimationId = requestAnimationFrame(stepScroll);
    }

    function startAutoScroll() {
        isAutoScrolling = true;
        if (autoScrollBtn) {
            autoScrollBtn.classList.add('text-indigo-600', 'bg-indigo-50', 'dark:bg-slate-800');
            autoScrollBtn.innerHTML = '<i class="fa-solid fa-pause text-sm"></i>';
            autoScrollBtn.title = "Pause Auto-Scroll";
        }
        scrollAnimationId = requestAnimationFrame(stepScroll);
    }

    function stopAutoScroll() {
        isAutoScrolling = false;
        if (scrollAnimationId) {
            cancelAnimationFrame(scrollAnimationId);
            scrollAnimationId = null;
        }
        if (autoScrollBtn) {
            autoScrollBtn.classList.remove('text-indigo-600', 'bg-indigo-50', 'dark:bg-slate-800');
            autoScrollBtn.innerHTML = '<i class="fa-solid fa-angles-down text-sm"></i>';
            autoScrollBtn.title = "Start Auto-Scroll";
        }
    }

    autoScrollBtn?.addEventListener('click', function () {
        if (isAutoScrolling) {
            stopAutoScroll();
        } else {
            startAutoScroll();
        }
    });

    scrollContainer?.addEventListener('wheel', function () {
        if (isAutoScrolling) stopAutoScroll();
    }, { passive: true });

    scrollContainer?.addEventListener('touchstart', function () {
        if (isAutoScrolling) stopAutoScroll();
    }, { passive: true });

    function togglePanel(panelId) {
        const targetBtn = Array.from(iconBtns).find(btn => btn.getAttribute('data-panel') === panelId);
        const targetPanel = document.getElementById(panelId);

        if (!targetBtn || !targetPanel) return;

        const isAlreadyActive = targetBtn.classList.contains('active') && !configSidebar.classList.contains('collapsed');

        if (isAlreadyActive) {
            configSidebar.classList.add('collapsed');
            targetBtn.classList.remove('active');
        } else {
            configSidebar.classList.remove('collapsed');
            iconBtns.forEach(b => b.classList.remove('active'));
            subpanels.forEach(p => p.classList.remove('active'));

            targetBtn.classList.add('active');
            targetPanel.classList.add('active');
        }
    }

    iconBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const panelId = this.getAttribute('data-panel');
            togglePanel(panelId);
        });
    });

    document.getElementById('btnToggleConfigPanel')?.addEventListener('click', function () {
        togglePanel('panel-config');
    });

    document.querySelectorAll('.field-chip').forEach(chip => {
        chip.addEventListener('click', function () {
            const token = this.getAttribute('data-token');
            if (window.editorInstance) {
                window.editorInstance.insertContent(token);
            }
        });
    });

    function updateZoomUi() {
        const value = Math.round(zoomScale * 100);
        const valueEl = document.getElementById('editor-zoom-value');
        const minusBtn = document.getElementById('editor-zoom-minus');
        const plusBtn = document.getElementById('editor-zoom-plus');

        if (valueEl) valueEl.textContent = `${value}%`;
        if (minusBtn) minusBtn.disabled = zoomScale <= ZOOM_MIN;
        if (plusBtn) plusBtn.disabled = zoomScale >= ZOOM_MAX;
    }

    function applyEditorZoom() {
        if (!window.editorInstance) return;
        const body = window.editorInstance.getBody();
        if (!body) return;
        body.style.transform = `scale(${zoomScale})`;
        body.style.transformOrigin = 'top center';
    }

    function setZoom(nextZoom) {
        const normalized = Math.round(nextZoom * 100) / 100;
        zoomScale = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, normalized));
        updateZoomUi();
        applyEditorZoom();
    }

    document.getElementById('editor-zoom-minus')?.addEventListener('click', function () {
        setZoom(zoomScale - ZOOM_STEP);
    });

    document.getElementById('editor-zoom-plus')?.addEventListener('click', function () {
        setZoom(zoomScale + ZOOM_STEP);
    });

    updateZoomUi();

    function findNativeMenuButton(menuName) {
        if (!window.editorInstance) return null;
        const menubar = window.editorInstance.editorContainer?.querySelector('.tox-menubar');
        if (!menubar) return null;
        const buttons = Array.from(menubar.querySelectorAll('.tox-mbtn'));
        const wanted = menuName.toLowerCase();
        return buttons.find(button => {
            const label = (button.getAttribute('aria-label') || button.textContent || '').trim().toLowerCase();
            const id = (button.getAttribute('data-alloy-tabstop') || '').toLowerCase();
            return label === wanted || label.startsWith(`${wanted} `) || id === wanted;
        }) || null;
    }

    function placeOpenedMenu(anchorButton) {
        const anchorRect = anchorButton.getBoundingClientRect();
        requestAnimationFrame(() => {
            const menus = Array.from(document.querySelectorAll('.tox.tox-tinymce-aux .tox-menu'))
                .filter(menu => {
                    const style = window.getComputedStyle(menu);
                    return style.visibility !== 'hidden' && style.display !== 'none';
                });
            const menu = menus[menus.length - 1];
            if (!menu) return;
            const menuRect = menu.getBoundingClientRect();
            const gap = 2;
            const viewportPadding = 8;
            let left = anchorRect.left;
            let top = anchorRect.bottom + gap;
            if (left + menuRect.width > window.innerWidth - viewportPadding) {
                left = Math.max(viewportPadding, window.innerWidth - menuRect.width - viewportPadding);
            }
            if (top + menuRect.height > window.innerHeight - viewportPadding) {
                top = Math.max(viewportPadding, anchorRect.top - menuRect.height - gap);
            }
            menu.style.position = 'fixed';
            menu.style.left = `${Math.round(left)}px`;
            menu.style.top = `${Math.round(top)}px`;
            menu.style.zIndex = '20001';
        });
    }

    function setupExternalTinyMceMenus() {
        const target = document.getElementById('gdocs-menubar-target');
        if (!target) return;
        target.querySelectorAll('.gdocs-menu-btn').forEach(button => {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                const menuName = this.dataset.tmMenu;
                const nativeButton = findNativeMenuButton(menuName);
                if (!nativeButton) return;
                const wasOpen = this.getAttribute('aria-expanded') === 'true';
                if (wasOpen) {
                    nativeButton.click();
                    this.setAttribute('aria-expanded', 'false');
                    return;
                }
                document.querySelectorAll('.gdocs-menu-btn').forEach(other => {
                    other.setAttribute('aria-expanded', 'false');
                });
                nativeButton.click();
                this.setAttribute('aria-expanded', 'true');
                placeOpenedMenu(this);
            });
        });

        document.addEventListener('mousedown', function (event) {
            if (target.contains(event.target)) return;
            if (event.target.closest('.tox.tox-tinymce-aux')) return;
            document.querySelectorAll('.gdocs-menu-btn').forEach(button => {
                button.setAttribute('aria-expanded', 'false');
            });
        });
    }

    

    tinymce.init({
        selector: '#editor',
        license_key: 'gpl',
        height: '100%',
        promotion: false,
        plugins: ['advlist', 'anchor', 'autolink', 'charmap', 'code', 'fullscreen', 'image', 'insertdatetime', 'link', 'lists', 'media', 'searchreplace', 'table'],
        menubar: 'file edit view insert format tools table help',
        toolbar: ['undo redo', '|', 'blocks fontfamily fontsize', '|', 'bold italic underline strikethrough', '|', 'align numlist bullist', '|', 'link image', '|', 'table media', '|', 'lineheight outdent indent', '|', 'forecolor backcolor removeformat', '|', 'charmap emoticons', '|', 'code fullscreen preview', '|', 'save print', '|', 'pagebreak anchor codesample', '|', 'ltr rtl'].join(' '),
        toolbar_mode: 'sliding',
        font_family_formats: 'Andale Mono=andale mono,monospace;' +
            'Arial=arial,helvetica,sans-serif;' +
            'Arial Black=arial black,sans-serif;' +
            'Book Antiqua=book antiqua,palatino,serif;' +
            'Comic Sans MS=comic sans ms,sans-serif;' +
            'Courier New=courier new,courier,monospace;' +
            'Georgia=georgia,palatino,serif;' +
            'Helvetica=helvetica,arial,sans-serif;' +
            'Impact=impact,chicago,sans-serif;' +
            'Symbol=symbol;' +
            'Tahoma=tahoma,arial,helvetica,sans-serif;' +
            'Terminal=terminal,monaco,monospace;' +
            'Times New Roman=times new roman,times,serif;' +
            'Trebuchet MS=trebuchet ms,geneva,sans-serif;' +
            'Verdana=verdana,geneva,sans-serif;' +
            'Webdings=webdings;' +
            'Palatino=Palatino,serif;',
        fontsize_formats: '8pt 9pt 10pt 11pt 12pt 13pt 14pt 16pt 18pt 20pt 24pt 28pt 32pt 36pt',
        statusbar: false,
        editable_root: true,
        noneditable_class: 'mceNonEditable',
        editable_class: 'mceEditable',
        selector: '#editor',
        skin: document.body.classList.contains('dark') ? 'oxide-dark' : 'oxide',
        content_css: document.body.classList.contains('dark') ? 'dark' : 'default',
        content_style: `
                html {
                    background-color: #f8f9fa;
                    padding: 24px 0;
                    display: flex;
                    justify-content: center;
                    transition: background-color 0.3s;
                    min-height: 100%;
                }
                body {
                    background-color: #ffffff !important;
                    margin: 0 auto !important;
                    box-shadow: 0 1px 3px 0 rgba(60,64,67,0.3), 0 4px 8px 3px rgba(60,64,67,0.15) !important;
                    border-radius: 2px !important;
                    box-sizing: border-box !important;
                    font-family: Arial, Helvetica, sans-serif;
                    font-size: 11pt;
                    color: #000000;
                    position: relative;
                    overflow-y: visible !important;
                    display: flex;
                    flex-direction: column;
                }
                
                .page-break-gap {
                    display: block;
                    width: calc(100% + var(--margin-left) + var(--margin-right));
                    background-color: #f8f9fa;
                    box-shadow: inset 0 1px 3px rgba(0,0,0,0.1), inset 0 -1px 3px rgba(0,0,0,0.1);
                    user-select: none;
                    contenteditable: false;
                    margin-left: calc(-1 * var(--margin-left));
                    margin-right: calc(-1 * var(--margin-right));
                    flex-shrink: 0;
                }
                
                .header-wrapper {
                    position: relative;
                    width: 100%;
                    user-select: none;
                    box-sizing: border-box;
                    margin-bottom: 10px;
                    flex-shrink: 0;
                    display: flex;
                    flex-direction: column;
                }

                .footer-wrapper {
                    position: relative;
                    width: 100%;
                    user-select: none;
                    box-sizing: border-box;
                    margin-top: auto !important;
                    margin-bottom: 0;
                    flex-shrink: 0;
                    display: flex;
                    flex-direction: column;
                }

                .header-resizer {
                    position: absolute;
                    bottom: -3px;
                    left: 0;
                    width: 100%;
                    height: 6px;
                    cursor: ns-resize;
                    background: transparent;
                    z-index: 100;
                }

                .footer-resizer {
                    position: absolute;
                    top: -3px;
                    left: 0;
                    width: 100%;
                    height: 6px;
                    cursor: ns-resize;
                    background: transparent;
                    z-index: 100;
                }

                .header-resizer:hover, .footer-resizer:hover {
                    background-color: #1a73e8;
                }

                .editable-header, .editable-footer {
                    width: 100%;
                    height: 100%;
                    border: 1px dashed #cccccc;
                    padding: 6px 10px;
                    box-sizing: border-box;
                    color: #333333;
                    font-size: 9pt;
                    outline: none;
                    user-select: text;
                    transition: border-color 0.2s ease, box-shadow 0.2s ease;
                }

                .editable-header:focus, .editable-footer:focus {
                    border: 1px solid #1a73e8 !important;
                    box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
                }

                .editable-header:empty::before {
                    content: "Header content...";
                    color: #aaa;
                }
                .editable-footer:empty::before {
                    content: "Footer content...";
                    color: #aaa;
                }

                html.dark-mode { background-color: #0f172a; }
                html.dark-mode body { background-color: #1e293b !important; color: #f1f5f9; box-shadow: 0 4px 6px rgba(0,0,0,0.5) !important; }
                html.dark-mode .page-break-gap { background-color: #0f172a; box-shadow: none; }
                html.dark-mode .editable-header, html.dark-mode .editable-footer { border-color: #334155; color: #cbd5e1; }
                html.dark-mode .editable-header:focus, html.dark-mode .editable-footer:focus { border-color: #60a5fa !important; }
            `,

        setup: function (editor) {
            editor.on('init', function () {
                window.editorInstance = editor;

                setupExternalTinyMceMenus();
                applyEditorZoom();
                updateZoomUi();

                setTimeout(() => {
                    window.updatePageLayout();
                    if (document.body.classList.contains('dark')) {
                        editor.getDoc().documentElement.classList.add('dark-mode');
                    }
                }, 100);
            });

            /* Double-Click Outside Margin Logic */
            editor.on('dblclick', function (e) {
                const rect = editor.getBody().getBoundingClientRect();
                const mtPx = (parseFloat(document.getElementById('marginTop').value) || 0) * MM_TO_PX;
                const mbPx = (parseFloat(document.getElementById('marginBottom').value) || 0) * MM_TO_PX;

                const clickY = e.clientY - rect.top;

                // Double click on Top Margin Area
                if (clickY <= mtPx + 10) {
                    const headerCheck = document.getElementById('cfgEnableHeader');
                    headerCheck.checked = true;
                    window.updatePageLayout();
                    setTimeout(() => {
                        const headerEl = editor.getBody().querySelector('.editable-header');
                        if (headerEl) {
                            editor.selection.select(headerEl, true);
                            headerEl.focus();
                        }
                    }, 50);
                }

                // Double click on Bottom Margin Area
                if (clickY >= rect.height - mbPx - 10) {
                    const footerCheck = document.getElementById('cfgEnableFooter');
                    footerCheck.checked = true;
                    window.updatePageLayout();
                    setTimeout(() => {
                        const footerEl = editor.getBody().querySelector('.editable-footer');
                        if (footerEl) {
                            editor.selection.select(footerEl, true);
                            footerEl.focus();
                        }
                    }, 50);
                }
            });

            editor.on('input change undo redo', function () {
                setIcon('default');
            });

            /* Draggable Resizer Event Handlers */
            let isDraggingHeader = false;
            let isDraggingFooter = false;
            let startY = 0;
            let initialHeight = 0;

            editor.on('mousedown', function (e) {
                if (e.target.classList.contains('header-resizer')) {
                    isDraggingHeader = true;
                    startY = e.clientY;
                    initialHeight = headerHeightPx;
                    e.preventDefault();
                } else if (e.target.classList.contains('footer-resizer')) {
                    isDraggingFooter = true;
                    startY = e.clientY;
                    initialHeight = footerHeightPx;
                    e.preventDefault();
                }
            });

            editor.on('mousemove', function (e) {
                if (isDraggingHeader) {
                    const deltaY = e.clientY - startY;
                    headerHeightPx = Math.max(25, initialHeight + deltaY);
                    window.updatePageLayout();
                } else if (isDraggingFooter) {
                    const deltaY = startY - e.clientY;
                    footerHeightPx = Math.max(25, initialHeight + deltaY);
                    window.updatePageLayout();
                }
            });

            editor.on('mouseup', function () {
                isDraggingHeader = false;
                isDraggingFooter = false;
            });

            editor.on('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'a') {
                    const win = editor.getWin();
                    const doc = editor.getDoc();
                    const sel = win.getSelection();

                    if (!sel.rangeCount) return;

                    let node = sel.anchorNode;
                    if (node.nodeType === 3) node = node.parentNode;

                    const editableTarget = node.closest('.editable-header, .editable-footer');

                    if (editableTarget) {
                        e.preventDefault();
                        e.stopPropagation();

                        const range = doc.createRange();
                        range.selectNodeContents(editableTarget);
                        sel.removeAllRanges();
                        sel.addRange(range);
                    } else {
                        e.preventDefault();
                        e.stopPropagation();

                        const bodyNodes = Array.from(editor.getBody().children).filter(child =>
                            !child.classList.contains('header-wrapper') &&
                            !child.classList.contains('footer-wrapper') &&
                            !child.classList.contains('page-break-gap')
                        );

                        if (bodyNodes.length > 0) {
                            const range = doc.createRange();
                            range.setStartBefore(bodyNodes[0]);
                            range.setEndAfter(bodyNodes[bodyNodes.length - 1]);
                            sel.removeAllRanges();
                            sel.addRange(range);
                        }
                    }
                }
            });

            let timeout;
            editor.on('keyup NodeChange', function () {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    window.updatePageLayout();
                }, 300);
            });
        }
    });
});
