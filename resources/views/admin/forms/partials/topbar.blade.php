<header class="gdocs-header">
        <div class="gdocs-left">
            <a href="{{ route('admin.forms.index') }}" data-discard-url="{{ route('admin.forms.index') }}" data-discard-title="Leave the form designer?" data-discard-message="Any unsaved designer changes may be lost. Save the form before leaving if you want to keep them." class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800" aria-label="Back to Form Templates"><i class="fa-solid fa-arrow-left"></i><span class="hidden sm:inline">Back to forms</span></a>
            <div class="gdocs-logo">
                <img src="{{ asset('favicon.png') }}" height="50" width="50" alt="Logo">
            </div>

            <div class="gdocs-bars">
                <div class="gdocs-title-bar">
                    <div class="flex min-w-0 items-center gap-2">
                        <div
                            x-show="!editingName"
                            x-cloak
                            class="gdocs-filename">
                            <span
                                @click="startEditing()"
                                x-text="formName || 'Untitled Form'">
                            </span>
                            <button
                                type="button"
                                @click="startEditing()"
                                class="gdocs-btn"
                                title="Rename Form Template"
                                aria-label="Rename Form Template">
                                <i class="bi bi-pen"></i>
                            </button>
                            <button
                                type="button"
                                id="btnToggleConfigPanel"
                                class="gdocs-btn"
                                title="Open Form Configuration"
                                aria-label="Open Form Configuration">
                                <i class="bi bi-gear"></i>
                            </button>
                        </div>

                        <div
                            x-show="editingName"
                            x-cloak
                            @click.outside="cancelEditing()"
                            class="gdocs-filename-edit gap-0">
                            <input
                                x-ref="formNameInput"
                                type="text"
                                id="formName"
                                name="form_name"
                                x-model="formName"
                                @input="resizeFilenameInput()"
                                @keydown.enter.prevent="saveEditing()"
                                @keydown.escape.prevent="cancelEditing()"
                                @blur="cancelEditing()"
                                class="gdocs-filename-input w-auto min-w-[50px] max-w-[400px]"
                                placeholder="Filename"
                                autocomplete="off">

                            <button
                                type="button"
                                @click="cancelEditing()"
                                class="flex h-9 w-9 shrink-0 items-center justify-center
                                    border-0 bg-transparent text-slate-400 rounded-full
                                    hover:bg-slate-100 hover:text-rose-500
                                    dark:bg-transparent dark:text-slate-500
                                    dark:hover:bg-slate-800 dark:hover:text-rose-400"
                                title="Cancel"
                                aria-label="Cancel Rename">
                                <i class="fa-solid fa-xmark text-sm"></i>
                            </button>

                            <button
                                type="button"
                                @mousedown.prevent="saveEditing()"
                                :disabled="!formName.trim()"
                                class="flex h-9 w-9 shrink-0 items-center justify-center
                                    border-0 bg-transparent text-blue-600 rounded-full
                                    hover:bg-blue-50
                                    disabled:cursor-not-allowed disabled:opacity-30
                                    dark:bg-transparent dark:text-blue-400
                                    dark:hover:bg-blue-950/50"
                                title="Save Name"
                                aria-label="Save Name">
                                <i class="fa-solid fa-check text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <nav id="gdocs-menubar-target" aria-label="Editor menu">
                    <button type="button" class="gdocs-menu-btn" data-tm-menu="file" aria-haspopup="true" aria-expanded="false">File</button>
                    <button type="button" class="gdocs-menu-btn" data-tm-menu="edit" aria-haspopup="true" aria-expanded="false">Edit</button>
                    <button type="button" class="gdocs-menu-btn" data-tm-menu="view" aria-haspopup="true" aria-expanded="false">View</button>
                    <button type="button" class="gdocs-menu-btn" data-tm-menu="insert" aria-haspopup="true" aria-expanded="false">Insert</button>
                    <button type="button" class="gdocs-menu-btn" data-tm-menu="format" aria-haspopup="true" aria-expanded="false">Format</button>
                    <button type="button" class="gdocs-menu-btn" data-tm-menu="tools" aria-haspopup="true" aria-expanded="false">Tools</button>
                    <button type="button" class="gdocs-menu-btn" data-tm-menu="table" aria-haspopup="true" aria-expanded="false">Table</button>
                    <button type="button" class="gdocs-menu-btn" data-tm-menu="help" aria-haspopup="true" aria-expanded="false">Help</button>
                </nav>
            </div>
        </div>

        <div class="gdocs-actions">
            <button
                type="button"
                id="btnToggleAutoScroll"
                class="gdocs-btn"
                title="Toggle Auto-Scroll"
                aria-label="Toggle Auto-Scroll">
                <i class="fa-solid fa-angles-down text-sm"></i>
            </button>

            <div class="gdocs-zoom" role="group" aria-label="Editor zoom">
                <button
                    type="button"
                    id="editor-zoom-minus"
                    class="gdocs-zoom-btn"
                    title="Zoom out"
                    aria-label="Zoom out">
                    <i class="fa-solid fa-minus"></i>
                </button>

                <span id="editor-zoom-value" class="gdocs-zoom-value">100%</span>

                <button
                    type="button"
                    id="editor-zoom-plus"
                    class="gdocs-zoom-btn"
                    title="Zoom in"
                    aria-label="Zoom in">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>

            <button
                type="button"
                id="btnToggleTheme"
                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg text-slate-500 transition-all duration-150 hover:bg-slate-100 hover:text-blue-600 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-blue-400"
                title="Toggle Light/Dark Theme"
                aria-label="Toggle Light/Dark Theme">
                <i class="fa-solid fa-circle-half-stroke text-lg"></i>
            </button>

            <button type="button" id="saveBtn" class="" title="{{ isset($form) ? 'Saved' : 'Not Saved' }}" aria-label="Saved">
                <i class="bi bi-cloud{{ isset($form) ? '-check-fill text-lime-500' : ' text-emerald-500'}}"></i>
            </button>
        </div>
    </header>
