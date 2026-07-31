<?php
// Flatten form fields from sections structure
$flatFields = [];
$schema = json_decode($form['schema_json'] ?? '[]', true) ?: [];
foreach (($schema['sections'] ?? []) as $sec) {
    foreach (($sec['fields'] ?? []) as $f) {
        // Skip non-data field types
        if (in_array($f['type'] ?? '', ['heading', 'divider', 'html'])) continue;
        if (!empty($f['key'])) {
            $f['name'] = $f['name'] ?? $f['key'];
            $flatFields[] = $f;
        }
    }
}
?>
<!-- Scoped PDF Designer Custom Styles -->
<style>
/* ── PDF Designer Scoped Styles (Light & Dark Theme Safe) ─────────────── */
.pdf-designer-wrapper {
    position: relative;
    z-index: 1;
}

/* 1. Header & Section Contrast Fixes */
.pdf-designer-wrapper .designer-title {
    color: var(--color-text, #1E293B) !important;
}
.pdf-designer-wrapper .designer-subtitle {
    color: var(--color-text-muted, #64748B) !important;
}

/* 2. Glass Panels in Light Theme */
.pdf-designer-wrapper .glass-panel {
    background: var(--color-surface, #FFFFFF) !important;
    border: 1px solid var(--color-border, #E2E8F0) !important;
    box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.05)) !important;
}
.pdf-designer-wrapper .glass-panel h6 {
    color: var(--color-text, #1E293B) !important;
    border-bottom-color: var(--color-border, #E2E8F0) !important;
}

/* 3. Static Elements Palette Buttons (Active, Clickable, High Contrast) */
.pdf-designer-wrapper .static-btn {
    background-color: var(--color-bg, #F8FAFC) !important;
    color: var(--color-text, #1E293B) !important;
    border: 1px solid var(--color-border, #CBD5E1) !important;
    font-weight: 500 !important;
    opacity: 1 !important;
    transition: all 0.15s ease-in-out !important;
}
.pdf-designer-wrapper .static-btn:hover {
    background-color: var(--color-primary-light, #EEF2FF) !important;
    color: var(--color-primary, #4F46E5) !important;
    border-color: var(--color-primary, #4F46E5) !important;
    transform: translateY(-1px);
}
.pdf-designer-wrapper .static-btn i {
    font-size: 1rem;
}

/* 4. Form Fields Palette */
.pdf-designer-wrapper .palette-item {
    background-color: var(--color-bg, #F8FAFC) !important;
    color: var(--color-text, #1E293B) !important;
    border: 1px solid var(--color-border, #CBD5E1) !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    transition: all 0.15s ease-in-out;
}
.pdf-designer-wrapper .palette-item:hover {
    border-color: var(--color-primary, #4F46E5) !important;
    background-color: var(--color-primary-light, #EEF2FF) !important;
}
.pdf-designer-wrapper .palette-item .field-key-text {
    color: var(--color-text-muted, #64748B) !important;
    font-weight: 500;
}

/* 5. Inputs & Controls in Panels */
.pdf-designer-wrapper .form-label {
    color: var(--color-text-soft, #475569) !important;
    font-weight: 600 !important;
}
.pdf-designer-wrapper .form-control,
.pdf-designer-wrapper .form-select {
    background-color: var(--color-surface, #FFFFFF) !important;
    color: var(--color-text, #0F172A) !important;
    border: 1px solid var(--color-border, #CBD5E1) !important;
}
.pdf-designer-wrapper .form-control:focus,
.pdf-designer-wrapper .form-select:focus {
    border-color: var(--color-primary, #4F46E5) !important;
    box-shadow: 0 0 0 3px var(--color-primary-light, rgba(79, 70, 229, 0.15)) !important;
}

/* 6. A4 Canvas & Elements Contrast */
.pdf-designer-wrapper #pdf-a4-canvas {
    background-color: #FFFFFF !important;
    color: #0F172A !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
}

.pdf-designer-wrapper .canvas-element {
    color: #0F172A !important;
    background-color: rgba(255, 255, 255, 0.95);
}
.pdf-designer-wrapper .canvas-element.element-unselected {
    border: 1px dashed #94A3B8 !important;
}
.pdf-designer-wrapper .canvas-element.element-selected {
    border: 2px solid var(--color-primary, #4F46E5) !important;
    box-shadow: 0 0 0 3px var(--color-primary-light, rgba(79, 70, 229, 0.25)) !important;
    z-index: 10;
}
.pdf-designer-wrapper .canvas-element .field-tag {
    background-color: var(--color-primary, #4F46E5) !important;
    color: #FFFFFF !important;
    font-weight: 600;
}
.pdf-designer-wrapper .canvas-element .field-key-display {
    color: var(--color-primary, #4F46E5) !important;
    font-weight: 700;
}

/* 7. Canvas Table Element */
.pdf-designer-wrapper .canvas-element table {
    color: #0F172A !important;
}
.pdf-designer-wrapper .canvas-element table td {
    color: #0F172A !important;
    background-color: transparent !important;
}

/* Dark mode adjustments for PDF designer panels (keeping canvas clean white) */
[data-theme="dark"] .pdf-designer-wrapper .designer-title { color: #F8FAFC !important; }
[data-theme="dark"] .pdf-designer-wrapper .designer-subtitle { color: #94A3B8 !important; }
[data-theme="dark"] .pdf-designer-wrapper .glass-panel {
    background: #1E293B !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .pdf-designer-wrapper .glass-panel h6 {
    color: #F8FAFC !important;
    border-bottom-color: #334155 !important;
}
[data-theme="dark"] .pdf-designer-wrapper .static-btn {
    background-color: #0F172A !important;
    color: #F8FAFC !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .pdf-designer-wrapper .static-btn:hover {
    background-color: rgba(79, 70, 229, 0.2) !important;
    color: #818CF8 !important;
    border-color: #6366F1 !important;
}
[data-theme="dark"] .pdf-designer-wrapper .palette-item {
    background-color: #0F172A !important;
    color: #F8FAFC !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .pdf-designer-wrapper .palette-item .field-key-text {
    color: #94A3B8 !important;
}
[data-theme="dark"] .pdf-designer-wrapper .form-label { color: #CBD5E1 !important; }
[data-theme="dark"] .pdf-designer-wrapper .form-control,
[data-theme="dark"] .pdf-designer-wrapper .form-select {
    background-color: #0F172A !important;
    color: #F8FAFC !important;
    border-color: #334155 !important;
}
</style>

<!-- PDF Form Designer Canvas Editor -->
<div class="content-wrapper pdf-designer-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="font-heading designer-title mb-1">
                <i class="fa-solid fa-pen-ruler text-primary me-2"></i> <?= __('PDF Designer') ?>: <?= htmlspecialchars($form['title']) ?>
            </h2>
            <p class="designer-subtitle small mb-0"><?= __('Drag and drop form fields and static elements onto the A4 canvas.') ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="/admin/pdf-designer" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> <?= __('Back') ?>
            </a>
            <button type="button" class="btn btn-success btn-sm" id="btn-save-pdf-design">
                <i class="fa-solid fa-floppy-disk me-1"></i> <?= __('Save Design') ?>
            </button>
        </div>
    </div>

    <!-- Alert container -->
    <div id="designer-alert-container"></div>

    <div class="row g-3">
        <!-- Sidebar: Available Fields & Toolbox -->
        <div class="col-md-3">
            <!-- Form Fields -->
            <div class="glass-panel p-3 mb-3">
                <h6 class="border-bottom pb-2 mb-3">
                    <i class="fa-solid fa-list-check me-2 text-info"></i><?= __('Form Fields') ?>
                    <span class="badge bg-info ms-1"><?= count($flatFields) ?></span>
                </h6>
                <div class="d-flex flex-column gap-2" id="fields-palette">
                    <?php if (empty($flatFields)): ?>
                        <p class="text-muted small text-center"><?= __('No fields found in this form.') ?></p>
                    <?php endif; ?>
                    <?php foreach ($flatFields as $field): ?>
                        <div class="palette-item p-2 rounded small"
                             style="cursor: grab;"
                             draggable="true"
                             data-type="field"
                             data-field-name="<?= htmlspecialchars($field['key']) ?>"
                             data-label="<?= htmlspecialchars($field['label'] ?? $field['key']) ?>">
                            <i class="fa-solid fa-grip-vertical me-2 text-secondary"></i>
                            <span class="fw-semibold"><?= htmlspecialchars($field['label'] ?? $field['key']) ?></span>
                            <span class="badge bg-primary float-end"><?= htmlspecialchars($field['type'] ?? 'text') ?></span>
                            <div class="field-key-text" style="font-size:0.75em; margin-left:1.4em;"><?= htmlspecialchars($field['key']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Static Elements Toolbox -->
            <div class="glass-panel p-3 mb-3">
                <h6 class="border-bottom pb-2 mb-3">
                    <i class="fa-solid fa-shapes me-2 text-warning"></i><?= __('Static Elements') ?>
                </h6>
                <div class="d-flex flex-column gap-2">
                    <button type="button" class="btn btn-sm text-start static-btn" id="add-static-text">
                        <i class="fa-solid fa-font me-2 text-info"></i><?= __('Static Text') ?>
                    </button>
                    <button type="button" class="btn btn-sm text-start static-btn" id="add-line">
                        <i class="fa-solid fa-minus me-2 text-success"></i><?= __('Horizontal Line') ?>
                    </button>
                    <button type="button" class="btn btn-sm text-start static-btn" id="add-border">
                        <i class="fa-solid fa-square me-2 text-warning"></i><?= __('Border Box') ?>
                    </button>
                    <button type="button" class="btn btn-sm text-start static-btn" id="add-table">
                        <i class="fa-solid fa-table me-2 text-primary"></i><?= __('Table') ?>
                    </button>
                    <button type="button" class="btn btn-sm text-start static-btn" id="add-bullet-list">
                        <i class="fa-solid fa-list-ul me-2 text-danger"></i><?= __('Bullet List') ?>
                    </button>
                    <div class="mt-2">
                        <label class="form-label small mb-1"><?= __('Upload Image') ?></label>
                        <input type="file" id="image-file-input" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>
            </div>

            <!-- Selected Element Properties -->
            <div class="glass-panel p-3" id="properties-panel">
                <h6 class="text-white border-bottom border-secondary pb-2 mb-3">
                    <i class="fa-solid fa-sliders me-2 text-primary"></i><?= __('Element Properties') ?>
                </h6>
                <div id="no-selection-msg" class="text-white-50 small text-center py-3">
                    <?= __('Click an element on canvas to edit properties.') ?>
                </div>
                <div id="properties-form" class="d-none">
                    <!-- Position & Size (always visible) -->
                    <div class="row g-1 mb-2">
                        <div class="col-6">
                            <label class="form-label text-white-50 small mb-1">X (mm)</label>
                            <input type="number" id="prop-x" class="form-control form-control-sm bg-dark text-white border-secondary">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-white-50 small mb-1">Y (mm)</label>
                            <input type="number" id="prop-y" class="form-control form-control-sm bg-dark text-white border-secondary">
                        </div>
                    </div>
                    <div class="row g-1 mb-2">
                        <div class="col-6">
                            <label class="form-label text-white-50 small mb-1"><?= __('Width') ?> (mm)</label>
                            <input type="number" id="prop-w" class="form-control form-control-sm bg-dark text-white border-secondary">
                        </div>
                        <div class="col-6" id="prop-h-group">
                            <label class="form-label text-white-50 small mb-1"><?= __('Height') ?> (mm)</label>
                            <input type="number" id="prop-h" class="form-control form-control-sm bg-dark text-white border-secondary">
                        </div>
                    </div>

                    <!-- Static Text / Field content -->
                    <div class="mb-2" id="prop-text-group">
                        <label class="form-label text-white-50 small mb-1"><?= __('Text') ?></label>
                        <input type="text" id="prop-text" class="form-control form-control-sm bg-dark text-white border-secondary">
                    </div>

                    <!-- Font size (text/field/bullet/table) -->
                    <div class="mb-2" id="prop-fontsize-group">
                        <label class="form-label text-white-50 small mb-1"><?= __('Font Size') ?> (pt)</label>
                        <input type="number" id="prop-font-size" class="form-control form-control-sm bg-dark text-white border-secondary" value="12">
                    </div>

                    <!-- Alignment (text/field/bullet/table) -->
                    <div class="mb-2" id="prop-align-group">
                        <label class="form-label text-white-50 small mb-1"><?= __('Alignment') ?></label>
                        <select id="prop-align" class="form-select form-select-sm bg-dark text-white border-secondary">
                            <option value="left"><?= __('Left') ?></option>
                            <option value="center"><?= __('Center') ?></option>
                            <option value="right"><?= __('Right') ?></option>
                        </select>
                    </div>

                    <!-- Show label (field only) -->
                    <div class="form-check mb-3" id="prop-showlabel-group">
                        <input type="checkbox" id="prop-show-label" class="form-check-input">
                        <label class="form-check-label text-white small" for="prop-show-label"><?= __('Include Field Label') ?></label>
                    </div>

                    <!-- Border width (line/border/table) -->
                    <div class="mb-2" id="prop-borderwidth-group">
                        <label class="form-label text-white-50 small mb-1"><?= __('Border Width') ?> (px)</label>
                        <input type="number" id="prop-border-width" class="form-control form-control-sm bg-dark text-white border-secondary" value="1" min="0">
                    </div>

                    <!-- Table rows/cols -->
                    <div class="row g-1 mb-2" id="prop-table-group">
                        <div class="col-6">
                            <label class="form-label text-white-50 small mb-1"><?= __('Rows') ?></label>
                            <input type="number" id="prop-rows" class="form-control form-control-sm bg-dark text-white border-secondary" value="3" min="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-white-50 small mb-1"><?= __('Columns') ?></label>
                            <input type="number" id="prop-cols" class="form-control form-control-sm bg-dark text-white border-secondary" value="3" min="1">
                        </div>
                    </div>
                    <div class="mb-2" id="prop-cells-group">
                        <label class="form-label text-white-50 small mb-1"><?= __('Cell Content (one per line)') ?></label>
                        <textarea id="prop-cells" class="form-control form-control-sm bg-dark text-white border-secondary" rows="4" placeholder="Row1Col1&#10;Row1Col2&#10;Row2Col1&#10;Row2Col2"></textarea>
                    </div>

                    <!-- Bullet list items -->
                    <div class="mb-2" id="prop-items-group">
                        <label class="form-label text-white-50 small mb-1"><?= __('Items (one per line)') ?></label>
                        <textarea id="prop-items" class="form-control form-control-sm bg-dark text-white border-secondary" rows="4" placeholder="Item 1&#10;Item 2&#10;Item 3"></textarea>
                    </div>

                    <button type="button" class="btn btn-outline-danger btn-sm w-100 mt-2" id="btn-delete-element">
                        <i class="fa-solid fa-trash me-1"></i><?= __('Delete Element') ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main A4 Portrait Canvas View -->
        <div class="col-md-9 d-flex justify-content-center">
            <div class="canvas-wrapper bg-secondary p-3 rounded" style="overflow: auto; max-width: 100%;">
                <!-- A4 Aspect Ratio: 210mm x 297mm -->
                <div id="pdf-a4-canvas"
                     class="bg-white text-dark shadow position-relative"
                     style="width: 210mm; height: 297mm; min-width: 210mm; min-height: 297mm; box-sizing: border-box;">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?= \App\Core\Csrf::token() ?>';
    const formId = <?= (int)$form['id'] ?>;
    const canvas = document.getElementById('pdf-a4-canvas');
    let elements = <?= json_encode($elements ?? []) ?>;
    let selectedIndex = null;

    // 1mm in px (210mm canvas maps to canvas.offsetWidth)
    const mmToPx = () => canvas.offsetWidth / 210;

    // ── Render canvas ────────────────────────────────────────────────────────
    function renderCanvas() {
        const scale = mmToPx();
        canvas.innerHTML = '';
        elements.forEach((el, idx) => {
            const isSelected = (selectedIndex === idx);
            const div = document.createElement('div');
            div.className = `canvas-element position-absolute ${isSelected ? 'element-selected' : 'element-unselected'}`;
            div.style.left   = (el.x * scale) + 'px';
            div.style.top    = (el.y * scale) + 'px';
            div.style.width  = (el.w * scale) + 'px';
            div.style.fontSize = (el.fontSize || 12) + 'pt';
            div.style.textAlign = el.align || 'left';
            div.style.cursor = 'move';
            div.style.overflow = 'hidden';
            div.style.padding = '2px 4px';
            div.style.boxSizing = 'border-box';
            div.style.userSelect = 'none';
            div.dataset.index = idx;

            if (el.h) div.style.height = (el.h * scale) + 'px';

            if (el.type === 'field') {
                const labelStr = el.showLabel ? `<strong>${escHtml(el.label || el.fieldName)}:</strong> ` : '';
                div.innerHTML = `<span class="badge field-tag me-1" style="font-size:0.65em">Field</span> ${labelStr}<span class="field-key-display">[${escHtml(el.fieldName)}]</span>`;
            } else if (el.type === 'static_text') {
                div.innerHTML = escHtml(el.text || 'Static Text');
                div.style.whiteSpace = 'pre-wrap';
            } else if (el.type === 'image') {
                div.innerHTML = `<img src="${el.url}" style="width:100%; height:100%; object-fit:contain;" draggable="false">`;
            } else if (el.type === 'line') {
                div.style.borderTop = `${el.borderWidth || 1}px solid ${el.color || '#000'}`;
                div.style.height = '2px';
                div.style.padding = '0';
            } else if (el.type === 'border') {
                div.style.border = `${el.borderWidth || 1}px solid ${el.color || '#000'}`;
                div.style.backgroundColor = 'transparent';
            } else if (el.type === 'table') {
                const rows = Math.max(1, el.rows || 3);
                const cols = Math.max(1, el.cols || 3);
                const cells = el.cells || [];
                const bStyle = `${el.borderWidth||1}px solid ${el.color||'#000'}`;
                let tbl = `<table style="width:100%;border-collapse:collapse;font-size:${el.fontSize||12}pt">`;
                let ci = 0;
                for (let r = 0; r < rows; r++) {
                    tbl += '<tr>';
                    for (let c = 0; c < cols; c++) {
                        tbl += `<td style="border:${bStyle};padding:2px 4px;text-align:${el.align||'left'}">${escHtml(cells[ci]||'')}</td>`;
                        ci++;
                    }
                    tbl += '</tr>';
                }
                tbl += '</table>';
                div.innerHTML = tbl;
                div.style.padding = '0';
            } else if (el.type === 'bullet_list') {
                const lines = (el.items || '').split('\n').map(l => l.trim()).filter(Boolean);
                div.innerHTML = '<ul style="margin:0;padding-left:1.2em">' + lines.map(l => `<li>${escHtml(l)}</li>`).join('') + '</ul>';
            }

            // Click to select
            div.addEventListener('mousedown', function(e) {
                e.stopPropagation();
            });
            div.addEventListener('click', function(e) {
                e.stopPropagation();
                selectElement(idx);
            });

            makeElementDraggable(div, idx);
            canvas.appendChild(div);
        });
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    // ── Select element → show properties ────────────────────────────────────
    function selectElement(idx) {
        selectedIndex = idx;
        renderCanvas();
        const el = elements[idx];
        if (!el) { clearSelection(); return; }

        document.getElementById('no-selection-msg').classList.add('d-none');
        document.getElementById('properties-form').classList.remove('d-none');

        // Common position/size
        document.getElementById('prop-x').value = Math.round(el.x || 0);
        document.getElementById('prop-y').value = Math.round(el.y || 0);
        document.getElementById('prop-w').value = Math.round(el.w || 80);
        document.getElementById('prop-h').value = Math.round(el.h || 10);
        document.getElementById('prop-text').value = el.text || '';
        document.getElementById('prop-font-size').value = el.fontSize || 12;
        document.getElementById('prop-align').value = el.align || 'left';
        document.getElementById('prop-show-label').checked = !!el.showLabel;
        document.getElementById('prop-border-width').value = el.borderWidth || 1;
        document.getElementById('prop-rows').value = el.rows || 3;
        document.getElementById('prop-cols').value = el.cols || 3;
        // cells: flat array → join by newline
        const cells = Array.isArray(el.cells) ? el.cells : [];
        document.getElementById('prop-cells').value = cells.join('\n');
        document.getElementById('prop-items').value = el.items || '';

        // Toggle visibility per type
        const t = el.type;
        show('prop-text-group',        t === 'static_text');
        show('prop-fontsize-group',    ['field','static_text','table','bullet_list'].includes(t));
        show('prop-align-group',       ['field','static_text','table','bullet_list'].includes(t));
        show('prop-showlabel-group',   t === 'field');
        show('prop-borderwidth-group', ['line','border','table'].includes(t));
        show('prop-h-group',           t !== 'line');
        show('prop-table-group',       t === 'table');
        show('prop-cells-group',       t === 'table');
        show('prop-items-group',       t === 'bullet_list');
    }

    function show(id, visible) {
        const el = document.getElementById(id);
        if (el) el.style.display = visible ? '' : 'none';
    }

    function clearSelection() {
        selectedIndex = null;
        document.getElementById('no-selection-msg').classList.remove('d-none');
        document.getElementById('properties-form').classList.add('d-none');
    }

    canvas.addEventListener('click', function() {
        selectedIndex = null;
        renderCanvas();
        clearSelection();
    });

    // ── Update element from property inputs ──────────────────────────────────
    const propInputIds = ['prop-x','prop-y','prop-w','prop-h','prop-text','prop-font-size','prop-align','prop-show-label','prop-border-width','prop-rows','prop-cols','prop-cells','prop-items'];
    propInputIds.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', updateCurrentElement);
            input.addEventListener('change', updateCurrentElement);
        }
    });

    function updateCurrentElement() {
        if (selectedIndex === null || !elements[selectedIndex]) return;
        const el = elements[selectedIndex];
        el.x         = parseFloat(document.getElementById('prop-x').value) || 0;
        el.y         = parseFloat(document.getElementById('prop-y').value) || 0;
        el.w         = parseFloat(document.getElementById('prop-w').value) || 20;
        el.h         = parseFloat(document.getElementById('prop-h').value) || 10;
        el.text      = document.getElementById('prop-text').value;
        el.fontSize  = parseInt(document.getElementById('prop-font-size').value) || 12;
        el.align     = document.getElementById('prop-align').value;
        el.showLabel = document.getElementById('prop-show-label').checked;
        el.borderWidth = parseInt(document.getElementById('prop-border-width').value) || 1;
        // Table
        el.rows = Math.max(1, parseInt(document.getElementById('prop-rows').value) || 3);
        el.cols = Math.max(1, parseInt(document.getElementById('prop-cols').value) || 3);
        const cellsText = document.getElementById('prop-cells').value;
        el.cells = cellsText.split('\n').map(l => l); // keep empty lines as empty cells
        // Bullet list
        el.items = document.getElementById('prop-items').value;
        renderCanvas();
    }

    // ── Delete ───────────────────────────────────────────────────────────────
    document.getElementById('btn-delete-element').addEventListener('click', function() {
        if (selectedIndex !== null) {
            elements.splice(selectedIndex, 1);
            selectedIndex = null;
            renderCanvas();
            clearSelection();
        }
    });

    // ── Drag element within canvas ───────────────────────────────────────────
    function makeElementDraggable(elmnt, idx) {
        let startX, startY, startLeft, startTop, moved = false;

        elmnt.addEventListener('mousedown', dragStart, { passive: false });

        function dragStart(e) {
            if (e.button !== 0) return;
            e.preventDefault();
            e.stopPropagation();
            moved = false;
            startX   = e.clientX;
            startY   = e.clientY;
            startLeft = elmnt.offsetLeft;
            startTop  = elmnt.offsetTop;
            document.addEventListener('mousemove', dragging);
            document.addEventListener('mouseup', dragEnd);
        }

        function dragging(e) {
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            if (Math.abs(dx) > 2 || Math.abs(dy) > 2) moved = true;
            const scale = mmToPx();
            let newLeft = Math.max(0, startLeft + dx);
            let newTop  = Math.max(0, startTop  + dy);
            elmnt.style.left = newLeft + 'px';
            elmnt.style.top  = newTop  + 'px';
            elements[idx].x  = Math.round(newLeft / scale);
            elements[idx].y  = Math.round(newTop  / scale);
            if (selectedIndex === idx) {
                document.getElementById('prop-x').value = elements[idx].x;
                document.getElementById('prop-y').value = elements[idx].y;
            }
        }

        function dragEnd(e) {
            document.removeEventListener('mousemove', dragging);
            document.removeEventListener('mouseup', dragEnd);
            if (moved) {
                e.stopPropagation();
                selectElement(idx);
            }
        }
    }

    // ── Palette drag-onto-canvas ─────────────────────────────────────────────
    document.querySelectorAll('.palette-item').forEach(item => {
        item.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', JSON.stringify({
                type:      item.dataset.type,
                fieldName: item.dataset.fieldName,
                label:     item.dataset.label
            }));
        });
    });

    canvas.addEventListener('dragover', e => e.preventDefault());
    canvas.addEventListener('drop', function(e) {
        e.preventDefault();
        const rect  = canvas.getBoundingClientRect();
        const scale = mmToPx();
        const dropX = Math.max(0, Math.round((e.clientX - rect.left) / scale));
        const dropY = Math.max(0, Math.round((e.clientY - rect.top)  / scale));
        const dataStr = e.dataTransfer.getData('text/plain');
        if (!dataStr) return;
        const data = JSON.parse(dataStr);
        elements.push({
            type:      data.type,
            fieldName: data.fieldName,
            label:     data.label,
            x: dropX, y: dropY,
            w: 80, h: 12,
            fontSize: 12,
            align: 'left',
            showLabel: true
        });
        selectElement(elements.length - 1);
    });

    // ── Static element add buttons ────────────────────────────────────────────
    document.getElementById('add-static-text').addEventListener('click', function() {
        elements.push({ type:'static_text', text:'Static Text', x:20, y:20, w:80, h:10, fontSize:12, align:'left' });
        selectElement(elements.length - 1);
    });

    document.getElementById('add-line').addEventListener('click', function() {
        elements.push({ type:'line', x:15, y:40, w:180, h:2, borderWidth:1, color:'#000000' });
        selectElement(elements.length - 1);
    });

    document.getElementById('add-border').addEventListener('click', function() {
        elements.push({ type:'border', x:10, y:10, w:190, h:277, borderWidth:2, color:'#000000' });
        selectElement(elements.length - 1);
    });

    document.getElementById('add-table').addEventListener('click', function() {
        elements.push({ type:'table', x:15, y:30, w:180, h:40, rows:3, cols:3, cells:[], borderWidth:1, color:'#000000', fontSize:11, align:'left' });
        selectElement(elements.length - 1);
    });

    document.getElementById('add-bullet-list').addEventListener('click', function() {
        elements.push({ type:'bullet_list', x:20, y:30, w:170, h:40, items:'Item 1\nItem 2\nItem 3', fontSize:12, align:'left' });
        selectElement(elements.length - 1);
    });

    // ── Image Upload ─────────────────────────────────────────────────────────
    document.getElementById('image-file-input').addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('image', file);
        fetch('/admin/pdf-designer/upload-image', { method:'POST', body:formData })
            .then(r => r.json())
            .then(res => {
                if (res.success && res.url) {
                    elements.push({ type:'image', url:res.url, x:20, y:20, w:40, h:40 });
                    selectElement(elements.length - 1);
                } else {
                    alert(res.message || 'Image upload failed.');
                }
            })
            .catch(err => alert('Upload error: ' + err.message));
        this.value = '';
    });

    // ── Save Design ──────────────────────────────────────────────────────────
    document.getElementById('btn-save-pdf-design').addEventListener('click', function() {
        const fd = new FormData();
        fd.append('_token', csrfToken);
        fd.append('design_data', JSON.stringify(elements));
        fetch(`/admin/pdf-designer/editor/${formId}/save`, { method:'POST', body:fd })
            .then(r => r.json())
            .then(res => {
                const container = document.getElementById('designer-alert-container');
                const cls = res.success ? 'success' : 'danger';
                container.innerHTML = `<div class="alert alert-${cls} alert-dismissible fade show">${res.message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
                window.scrollTo(0, 0);
            })
            .catch(err => alert('Save error: ' + err.message));
    });

    // ── Initial render ───────────────────────────────────────────────────────
    renderCanvas();
});
</script>
