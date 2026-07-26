<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h1 class="h3 m-0 text-white">
            <i class="fa-solid fa-crop me-2 text-primary" aria-hidden="true"></i> 
            Σχεδιαστής Πεδίων: <?= \App\Core\View::escape($template['title']) ?>
            <?php if ($template['status'] === 'published'): ?>
                <span class="badge bg-success ms-2 small">Published (Read Only)</span>
            <?php endif; ?>
        </h1>
    </div>
    <div class="col-md-4 text-end">
        <a href="/admin/document-templates/<?= (int)$template['id'] ?>" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i> Επιστροφή στα Στοιχεία
        </a>
    </div>
</div>

<div class="row">
    <!-- Left Sidebar: Fields Toolbox & Navigation -->
    <div class="col-md-3">
        <div class="card p-3 mb-3">
            <h6 class="text-white mb-3">Εργαλεία Πεδίων</h6>
            <?php if ($template['status'] !== 'published'): ?>
                <p class="text-muted small">Κάντε κλικ ή σύρετε ένα πεδίο για να το τοποθετήσετε στη σελίδα.</p>
                <div class="d-flex flex-column gap-2 mb-3">
                    <button class="btn btn-secondary text-start field-tool-btn" data-type="text">
                        <i class="fa-solid fa-font me-2 text-primary" aria-hidden="true"></i> Κείμενο (Text)
                    </button>
                    <button class="btn btn-secondary text-start field-tool-btn" data-type="number">
                        <i class="fa-solid fa-hashtag me-2 text-primary" aria-hidden="true"></i> Αριθμός (Number)
                    </button>
                    <button class="btn btn-secondary text-start field-tool-btn" data-type="date">
                        <i class="fa-solid fa-calendar me-2 text-primary" aria-hidden="true"></i> Ημερομηνία (Date)
                    </button>
                    <button class="btn btn-secondary text-start field-tool-btn" data-type="signature">
                        <i class="fa-solid fa-signature me-2 text-primary" aria-hidden="true"></i> Υπογραφή (Signature)
                    </button>
                    <button class="btn btn-secondary text-start field-tool-btn" data-type="consent">
                        <i class="fa-solid fa-square-check me-2 text-primary" aria-hidden="true"></i> Συγκατάθεση (Consent)
                    </button>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mb-0 small">
                    Το πρότυπο είναι Published (Read Only).
                </div>
            <?php endif; ?>
        </div>

        <!-- Thumbnails page navigator -->
        <div class="card p-3 mb-3">
            <h6 class="text-white mb-3">Σελίδες</h6>
            <div id="pages-nav-container" class="d-flex flex-column gap-2" style="max-height: 150px; overflow-y: auto;">
                <!-- Page thumbnails buttons -->
            </div>
        </div>

        <!-- Sortable Field List Panel -->
        <div class="card p-3 mb-3">
            <h6 class="text-white d-flex align-items-center justify-content-between mb-2">
                <span>Λίστα Πεδίων Φόρμας</span>
                <button type="button" id="auto-order-pdf-btn" class="btn btn-xs btn-outline-info" style="font-size:0.65rem; padding: 2px 6px;">Auto PDF</button>
            </h6>
            <p class="text-muted" style="font-size:0.7rem; line-height: 1.2;">Σύρετε τα πεδία για να αλλάξετε τη σειρά εμφάνισης στη φόρμα συμπλήρωσης.</p>
            <div id="designer-fields-list-container" class="d-flex flex-column gap-1" style="max-height: 250px; overflow-y: auto; background: rgba(0,0,0,0.2); padding: 5px; border-radius: 4px;">
                <!-- Populate dynamically -->
            </div>
        </div>

        <!-- Toolbar controls -->
        <div class="card p-3">
            <h6 class="text-white mb-3">Διαχείριση Σχεδιασμού</h6>
            <div class="d-flex gap-2 mb-3">
                <button type="button" id="undo-btn" class="btn btn-secondary btn-sm flex-fill" title="Undo"><i class="fa-solid fa-undo"></i> Undo</button>
                <button type="button" id="redo-btn" class="btn btn-secondary btn-sm flex-fill" title="Redo"><i class="fa-solid fa-redo"></i> Redo</button>
            </div>
            
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="grid-toggle-switch" checked>
                    <label class="form-check-label text-muted small" for="grid-toggle-switch">Εμφάνιση Πλέγματος (Grid)</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="snap-toggle-switch" checked>
                    <label class="form-check-label text-muted small" for="snap-toggle-switch">Snap to Grid</label>
                </div>
            </div>

            <form action="/admin/document-templates/<?= (int)$template['id'] ?>/designer" method="POST">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" id="fields-schema-input" name="fields_schema" value="">
                
                <?php if ($template['status'] !== 'published'): ?>
                    <button type="submit" id="save-schema-btn" class="btn btn-premium w-100 mb-2">
                        <i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i> Αποθήκευση
                    </button>
                    <button type="button" id="clear-canvas-btn" class="btn btn-outline-danger w-100 app-confirm-action" data-confirm-title="Εκκαθάριση Σχεδιαστή" data-confirm-text="Είστε βέβαιοι ότι θέλετε να διαγράψετε όλα τα τοποθετημένα πεδία από το έγγραφο;">
                        <i class="fa-solid fa-trash me-1" aria-hidden="true"></i> Εκκαθάριση
                    </button>
                <?php else: ?>
                    <button type="button" disabled class="btn btn-secondary w-100">
                        Read Only Mode
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Center Canvas Preview Area -->
    <div class="col-md-6">
        <div class="card p-3 mb-4 position-relative">
            <!-- Loading Spinner -->
            <div id="pdf-loading-spinner" class="position-absolute top-0 start-0 w-100 h-100 bg-dark d-flex align-items-center justify-content-center" style="z-index: 10; opacity: 0.85;">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-white mt-2 small">Φόρτωση PDF...</div>
                </div>
            </div>

            <!-- Canvas navigation toolbar -->
            <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom border-secondary">
                <div class="d-flex align-items-center gap-2">
                    <button id="prev-page-btn" class="btn btn-sm btn-secondary">
                        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <span class="text-white small">Σελίδα <span id="current-page-num">1</span> από <span id="total-pages-span">1</span></span>
                    <button id="next-page-btn" class="btn btn-sm btn-secondary">
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>
                
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="preview-mode-switch">
                    <label class="form-check-label text-white small" for="preview-mode-switch">Visual Preview (Sample Values)</label>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button id="zoom-out-btn" class="btn btn-sm btn-secondary"><i class="fa-solid fa-minus" aria-hidden="true"></i></button>
                    <span id="zoom-percentage" class="text-white small">100%</span>
                    <button id="zoom-in-btn" class="btn btn-sm btn-secondary"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
                </div>
            </div>

            <!-- PDF Page Wrapper with draggable fields -->
            <div id="designer-canvas-container" class="position-relative text-center" style="overflow: auto; max-height: 800px; background: #2b2b2b; padding: 20px; min-height: 500px; border-radius: 8px;">
                <div id="designer-page-wrapper" class="position-relative d-inline-block shadow-lg" style="background: var(--color-surface);">
                    <canvas id="pdf-render-canvas" style="display: block;"></canvas>
                    <div id="grid-overlay" class="position-absolute top-0 start-0 w-100 h-100" style="pointer-events: none; background-size: 20px 20px; background-image: linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px);"></div>
                    <div id="fields-overlay-container" class="position-absolute top-0 start-0 w-100 h-100" style="pointer-events: auto;">
                        <!-- Draggable fields are appended here dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Sidebar: Properties & Validation Panel -->
    <div class="col-md-3">
        <div id="properties-panel" class="card p-3">
            <h6 class="text-white mb-3">Ιδιότητες Πεδίου</h6>
            <div id="no-selected-field-msg" class="text-muted small">
                Επιλέξτε ένα τοποθετημένο πεδίο στο canvas για να επεξεργαστείτε τις ιδιότητές του.
            </div>
            <div id="selected-field-form" class="d-none">
                <!-- Validation errors warning alert -->
                <div id="prop-error-alert" class="alert alert-danger p-2 small d-none">
                    Το Key υπάρχει ήδη σε άλλο πεδίο!
                </div>

                <div id="prop-margin-warning" class="alert alert-warning p-2 small d-none">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Προσοχή: Το πεδίο βρίσκεται εκτός ορίων εκτύπωσης!
                </div>

                <div class="mb-2">
                    <label class="form-label text-muted small">Ετικέτα (Label)</label>
                    <input type="text" id="prop-label-input" class="form-control form-control-sm bg-dark text-white border-secondary">
                </div>
                <div class="mb-2">
                    <label class="form-label text-muted small">Αναγνωριστικό Key (Slug)</label>
                    <input type="text" id="prop-key-input" class="form-control form-control-sm bg-dark text-white border-secondary">
                </div>
                <div class="mb-2">
                    <label class="form-label text-muted small">Μετακίνηση σε Σελίδα</label>
                    <select id="prop-page-select" class="form-select form-select-sm bg-dark text-white border-secondary">
                        <!-- Options dynamically generated -->
                    </select>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" id="prop-required-input" class="form-check-input">
                    <label class="form-check-label text-muted small" for="prop-required-input">Απαιτούμενο Πεδίο</label>
                </div>

                <!-- Repository binding block -->
                <div class="mb-2">
                    <label class="form-label text-muted small">Σύνδεση με Repository</label>
                    <select id="prop-repo-select" class="form-select form-select-sm bg-dark text-white border-secondary">
                        <option value="">(Κανένα)</option>
                        <option value="departments">Departments</option>
                        <option value="request-types">Request Types</option>
                        <option value="priority-levels">Priority Levels</option>
                    </select>
                </div>

                <!-- Validation constraints panel -->
                <div class="border-top border-secondary pt-2 mt-2">
                    <h6 class="text-white mb-2 small font-weight-bold">Κανόνες Επικύρωσης (Validation)</h6>
                    <div class="mb-2">
                        <label class="form-label text-muted small">Min Value / Length</label>
                        <input type="text" id="prop-val-min" class="form-control form-control-sm bg-dark text-white border-secondary">
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted small">Max Value / Length</label>
                        <input type="text" id="prop-val-max" class="form-control form-control-sm bg-dark text-white border-secondary">
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted small">Regex Pattern</label>
                        <input type="text" id="prop-val-regex" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="e.g. /^[A-Z]+$/">
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted small">Default Value</label>
                        <input type="text" id="prop-val-default" class="form-control form-control-sm bg-dark text-white border-secondary">
                    </div>
                </div>

                <!-- Display Order property -->
                <div class="mb-3">
                    <label class="form-label text-muted small">Σειρά Εμφάνισης στη Φόρμα (display_order)</label>
                    <input type="number" id="prop-display-order-input" class="form-control form-control-sm bg-dark text-white border-secondary" min="0" max="100000">
                    <div class="text-muted" style="font-size: 0.65rem; line-height: 1.1; margin-top: 4px;">
                        Καθορίζει τη σειρά του πεδίου στη φόρμα συμπλήρωσης. Δεν επηρεάζει τη θέση του πάνω στο PDF.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small">Συντεταγμένες</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <span class="text-muted small">X: </span><span id="prop-x-val" class="text-white small">0</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small">Y: </span><span id="prop-y-val" class="text-white small">0</span>
                        </div>
                    </div>
                </div>

                <?php if ($template['status'] !== 'published'): ?>
                    <button type="button" id="duplicate-field-btn" class="btn btn-sm btn-outline-info w-100 mb-2">
                        <i class="fa-solid fa-copy me-1" aria-hidden="true"></i> Duplicate (Ctrl+D)
                    </button>
                    <button type="button" id="delete-selected-field-btn" class="btn btn-sm btn-danger w-100">
                        <i class="fa-solid fa-trash me-1" aria-hidden="true"></i> Διαγραφή Πεδίου
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Styles for placed visual coordinate boxes -->
<style>
    .placed-field-element {
        position: absolute;
        border: 2px dashed #00c6ff;
        background: rgba(0, 198, 255, 0.15);
        color: white;
        font-size: 10px;
        padding: 4px;
        border-radius: 4px;
        cursor: move;
        user-select: none;
        box-sizing: border-box;
        transition: border 0.1s ease, box-shadow 0.1s ease;
    }
    .placed-field-element.active {
        border: 2.5px solid #ff007f;
        background: rgba(255, 0, 127, 0.25);
        box-shadow: 0 0 10px rgba(255, 0, 127, 0.5);
    }
    .resize-handle {
        position: absolute;
        width: 10px;
        height: 10px;
        background: #ff007f;
        bottom: -3px;
        right: -3px;
        cursor: se-resize;
        border-radius: 50%;
    }
</style>

<!-- PDF.js script integration -->
<script src="/assets/js/pdf.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const url = '<?= $pdfUrl ?>';
        const isPublished = <?= json_encode($template['status'] === 'published') ?>;
        pdfjsLib.GlobalWorkerOptions.workerSrc = '/assets/js/pdf.worker.min.js';

        let pdfDoc = null,
            pageNum = 1,
            scale = 1.0,
            canvas = document.getElementById('pdf-render-canvas'),
            ctx = canvas.getContext('2d'),
            overlay = document.getElementById('fields-overlay-container'),
            currentPageSpan = document.getElementById('current-page-num'),
            totalPagesSpan = document.getElementById('total-pages-span'),
            zoomPercentage = document.getElementById('zoom-percentage'),
            fieldsSchemaInput = document.getElementById('fields-schema-input'),
            propsPanelNoSelect = document.getElementById('no-selected-field-msg'),
            propsPanelForm = document.getElementById('selected-field-form'),
            propKeyInput = document.getElementById('prop-key-input'),
            propLabelInput = document.getElementById('prop-label-input'),
            propRequiredInput = document.getElementById('prop-required-input'),
            propPageSelect = document.getElementById('prop-page-select'),
            propRepoSelect = document.getElementById('prop-repo-select'),
            propValMin = document.getElementById('prop-val-min'),
            propValMax = document.getElementById('prop-val-max'),
            propValRegex = document.getElementById('prop-val-regex'),
            propValDefault = document.getElementById('prop-val-default'),
            propXVal = document.getElementById('prop-x-val'),
            propYVal = document.getElementById('prop-y-val'),
            propErrorAlert = document.getElementById('prop-error-alert'),
            propMarginWarning = document.getElementById('prop-margin-warning'),
            gridToggleSwitch = document.getElementById('grid-toggle-switch'),
            snapToggleSwitch = document.getElementById('snap-toggle-switch'),
            gridOverlay = document.getElementById('grid-overlay'),
            previewModeSwitch = document.getElementById('preview-mode-switch'),
            pagesNavContainer = document.getElementById('pages-nav-container'),
            pdfLoadingSpinner = document.getElementById('pdf-loading-spinner');

        // Fields state array
        let fields = [];
        let selectedFieldId = null;

        // Undo/Redo stacks
        let historyStack = [];
        let redoStack = [];

        // Auto-generation Greek to English translation maps
        const translateMap = {
            'όνομα': 'first_name',
            'επώνυμο': 'last_name',
            'κείμενο': 'text_content',
            'ημερομηνία': 'date',
            'υπογραφή': 'signature'
        };

        // Load existing schema if exists
        try {
            const initialSchema = <?= json_encode(
                $template['fields_schema_json'] ?? '[]',
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ) ?>;
            const parsed = typeof initialSchema === 'string' ? JSON.parse(initialSchema) : initialSchema;
            fields = Array.isArray(parsed) ? parsed : [];
            
            // Normalize legacy fields missing display_order property
            fields.forEach((f, idx) => {
                if (f.display_order === undefined) {
                    f.display_order = (idx + 1) * 10;
                }
            });
        } catch(e) {
            console.error("Schema normalization error: ", e);
            fields = [];
        }

        function saveHistoryState() {
            historyStack.push(JSON.stringify(fields));
            redoStack = []; // Clear redo stack on new actions
        }

        // Apply undo/redo action
        document.getElementById('undo-btn').addEventListener('click', function() {
            if (historyStack.length > 0) {
                redoStack.push(JSON.stringify(fields));
                fields = JSON.parse(historyStack.pop());
                drawPlacedFields();
                updateSchemaInput();
            }
        });

        document.getElementById('redo-btn').addEventListener('click', function() {
            if (redoStack.length > 0) {
                historyStack.push(JSON.stringify(fields));
                fields = JSON.parse(redoStack.pop());
                drawPlacedFields();
                updateSchemaInput();
            }
        });

        // Grid parameters toggle
        gridToggleSwitch.addEventListener('change', function() {
            gridOverlay.style.display = this.checked ? 'block' : 'none';
        });

        // Render PDF page
        function renderPage(num) {
            pdfLoadingSpinner.classList.remove('d-none');
            pdfDoc.getPage(num).then(function(page) {
                let viewport = page.getViewport({scale: scale});
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                let renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                page.render(renderContext).promise.then(function() {
                    pdfLoadingSpinner.classList.add('d-none');
                    drawPlacedFields();
                });
            }).catch(function() {
                pdfLoadingSpinner.classList.add('d-none');
            });

            currentPageSpan.textContent = num;
            zoomPercentage.textContent = Math.round(scale * 100) + '%';

            // Update pages list active highlight class
            document.querySelectorAll('.page-thumb-btn').forEach(btn => {
                if (parseInt(btn.dataset.page) === num) {
                    btn.classList.replace('btn-outline-secondary', 'btn-primary');
                } else {
                    btn.classList.replace('btn-primary', 'btn-outline-secondary');
                }
            });
        }

        // Add a new field placement with normalized ratio coordinates
        function addField(type) {
            if (isPublished) return;
            saveHistoryState();
            const id = 'field_' + Date.now();
            
            // Auto generation mapping logic based on standard forms types
            let generatedLabel = type.charAt(0).toUpperCase() + type.slice(1);
            let generatedKey = type + '_' + Math.floor(Math.random() * 1000);

            const newField = {
                id: id,
                type: type,
                key: generatedKey,
                label: generatedLabel,
                required: false,
                page: pageNum,
                x_ratio: 0.15,
                y_ratio: 0.15,
                width_ratio: 0.25,
                height_ratio: 0.05,
                repo_slug: '',
                validation: {
                    min: '',
                    max: '',
                    regex: '',
                    default: ''
                }
            };
            fields.push(newField);
            
            // Audit action client-side logging
            console.log("field.created: " + generatedKey);

            drawPlacedFields();
            selectField(id);
            updateSchemaInput();
        }

        // Select active field
        function selectField(id) {
            selectedFieldId = id;
            document.querySelectorAll('.placed-field-element').forEach(el => {
                if (el.dataset.id === id) {
                    el.classList.add('active');
                } else {
                    el.classList.remove('active');
                }
            });

            const field = fields.find(f => f.id === id);
            if (field) {
                propsPanelNoSelect.classList.add('d-none');
                propsPanelForm.classList.remove('d-none');
                propKeyInput.value = field.key;
                propLabelInput.value = field.label;
                propRequiredInput.checked = field.required;
                propPageSelect.value = field.page;
                propRepoSelect.value = field.repo_slug || '';
                
                // Set validation values
                propValMin.value = field.validation?.min || '';
                propValMax.value = field.validation?.max || '';
                propValRegex.value = field.validation?.regex || '';
                propValDefault.value = field.validation?.default || '';
                
                // Set display order order value
                document.getElementById('prop-display-order-input').value = field.display_order !== undefined ? field.display_order : '';

                // Display normalized coords converted to pixels for context
                propXVal.textContent = Math.round(field.x_ratio * 100) + '%';
                propYVal.textContent = Math.round(field.y_ratio * 100) + '%';

                checkFieldBoundsWarning(field);
                validateUniqueKeys();
            } else {
                propsPanelNoSelect.classList.remove('d-none');
                propsPanelForm.classList.add('d-none');
            }
        }

        // Warn if field is partially outside printable page area margins
        function checkFieldBoundsWarning(field) {
            const margin = 0.05; // 5% print margin safety warning boundary
            const isOutside = (field.x_ratio < margin || field.y_ratio < margin || (field.x_ratio + field.width_ratio) > (1 - margin) || (field.y_ratio + field.height_ratio) > (1 - margin));
            if (isOutside) {
                propMarginWarning.classList.remove('d-none');
            } else {
                propMarginWarning.classList.add('d-none');
            }
        }

        // Validate that keys are unique
        function validateUniqueKeys() {
            let keys = fields.map(f => f.key);
            let hasDuplicate = keys.some((val, i) => keys.indexOf(val) !== i);
            if (hasDuplicate) {
                propErrorAlert.classList.remove('d-none');
                document.getElementById('save-schema-btn').disabled = true;
            } else {
                propErrorAlert.classList.add('d-none');
                document.getElementById('save-schema-btn').disabled = false;
            }
        }

        // Draw placed fields overlay using ratios relative to current canvas scale sizes
        function drawPlacedFields() {
            // Find existing fields array mapping to current page
            const pageFields = fields.filter(f => parseInt(f.page) === pageNum);
            
            // Recreate overlay items if list size doesn't match to avoid redrawing canvas entirely
            overlay.innerHTML = '';
            
            if (fields.length === 0) {
                overlay.innerHTML = '<div class="text-muted small p-3 text-center" style="pointer-events:none;">Σύρετε ή προσθέστε πεδία εδώ...</div>';
            }

            const wrapperWidth = canvas.width;
            const wrapperHeight = canvas.height;

            pageFields.forEach(field => {
                let div = document.createElement('div');
                div.className = 'placed-field-element';
                if (field.id === selectedFieldId) {
                    div.classList.add('active');
                }
                div.dataset.id = field.id;

                const leftPx = field.x_ratio * wrapperWidth;
                const topPx = field.y_ratio * wrapperHeight;
                const widthPx = field.width_ratio * wrapperWidth;
                const heightPx = field.height_ratio * wrapperHeight;

                div.style.left = leftPx + 'px';
                div.style.top = topPx + 'px';
                div.style.width = widthPx + 'px';
                div.style.height = heightPx + 'px';

                // Handle visual preview mode with sample signatures/text values
                if (previewModeSwitch.checked) {
                    if (field.type === 'signature') {
                        div.innerHTML = `<span style="font-family: cursive; color: #ff007f;">[PLACEHOLDER SIGNATURE]</span>`;
                    } else if (field.type === 'consent') {
                        div.innerHTML = `<input type="checkbox" checked disabled> Mapped Consent`;
                    } else {
                        div.innerHTML = field.validation?.default || 'Sample value';
                    }
                } else {
                    div.innerHTML = `<strong>${field.label}</strong><br>[${field.key}]`;
                }

                if (!isPublished) {
                    // Drag action
                    let isDragging = false;
                    let startX, startY, origXRatio, origYRatio;

                    div.addEventListener('mousedown', function(e) {
                        if (e.target.classList.contains('resize-handle')) return;
                        e.stopPropagation();
                        selectField(field.id);
                        isDragging = true;
                        startX = e.clientX;
                        startY = e.clientY;
                        origXRatio = field.x_ratio;
                        origYRatio = field.y_ratio;
                    });

                    document.addEventListener('mousemove', function(e) {
                        if (!isDragging) return;
                        let dx = e.clientX - startX;
                        let dy = e.clientY - startY;

                        let newX = origXRatio + (dx / wrapperWidth);
                        let newY = origYRatio + (dy / wrapperHeight);

                        // Snap to grid checks
                        if (snapToggleSwitch.checked) {
                            const gridX = 20 / wrapperWidth;
                            const gridY = 20 / wrapperHeight;
                            newX = Math.round(newX / gridX) * gridX;
                            newY = Math.round(newY / gridY) * gridY;
                        }

                        if (newX < 0) newX = 0;
                        if (newY < 0) newY = 0;
                        if (newX + field.width_ratio > 1) newX = 1 - field.width_ratio;
                        if (newY + field.height_ratio > 1) newY = 1 - field.height_ratio;

                        field.x_ratio = newX;
                        field.y_ratio = newY;

                        div.style.left = (field.x_ratio * wrapperWidth) + 'px';
                        div.style.top = (field.y_ratio * wrapperHeight) + 'px';

                        propXVal.textContent = Math.round(field.x_ratio * 100) + '%';
                        propYVal.textContent = Math.round(field.y_ratio * 100) + '%';
                        
                        checkFieldBoundsWarning(field);
                    });

                    document.addEventListener('mouseup', function() {
                        if (isDragging) {
                            isDragging = false;
                            updateSchemaInput();
                            console.log("field.moved: " + field.key);
                        }
                    });

                    // Resize handle placement
                    let handle = document.createElement('div');
                    handle.className = 'resize-handle';
                    div.appendChild(handle);

                    let isResizing = false;
                    let startW, startH, origWRatio, origHRatio;

                    handle.addEventListener('mousedown', function(e) {
                        e.stopPropagation();
                        isResizing = true;
                        startX = e.clientX;
                        startY = e.clientY;
                        origWRatio = field.width_ratio;
                        origHRatio = field.height_ratio;
                    });

                    document.addEventListener('mousemove', function(e) {
                        if (!isResizing) return;
                        let dx = e.clientX - startX;
                        let dy = e.clientY - startY;

                        let newW = origWRatio + (dx / wrapperWidth);
                        let newH = origHRatio + (dy / wrapperHeight);

                        if (snapToggleSwitch.checked) {
                            const gridW = 20 / wrapperWidth;
                            const gridH = 20 / wrapperHeight;
                            newW = Math.round(newW / gridW) * gridW;
                            newH = Math.round(newH / gridH) * gridH;
                        }

                        if (newW < 0.05) newW = 0.05;
                        if (newH < 0.02) newH = 0.02;
                        if (field.x_ratio + newW > 1) newW = 1 - field.x_ratio;
                        if (field.y_ratio + newH > 1) newH = 1 - field.y_ratio;

                        field.width_ratio = newW;
                        field.height_ratio = newH;

                        div.style.width = (field.width_ratio * wrapperWidth) + 'px';
                        div.style.height = (field.height_ratio * wrapperHeight) + 'px';
                    });

                    document.addEventListener('mouseup', function() {
                        if (isResizing) {
                            isResizing = false;
                            updateSchemaInput();
                            console.log("field.resized: " + field.key);
                        }
                    });
                }

                overlay.appendChild(div);
            });
        }

        function updateSchemaInput() {
            fieldsSchemaInput.value = JSON.stringify(fields);
        }

        // Duplicate selected field element
        function duplicateField() {
            if (isPublished || !selectedFieldId) return;
            saveHistoryState();
            const field = fields.find(f => f.id === selectedFieldId);
            if (field) {
                const id = 'field_' + Date.now();
                const dupKey = field.key + '_dup';
                const dup = {
                    ...field,
                    id: id,
                    key: dupKey,
                    label: field.label + ' (Copy)',
                    x_ratio: Math.min(0.8, field.x_ratio + 0.05),
                    y_ratio: Math.min(0.8, field.y_ratio + 0.05)
                };
                fields.push(dup);
                
                console.log("field.duplicated: " + dupKey);

                drawPlacedFields();
                selectField(id);
                updateSchemaInput();
            }
        }

        document.getElementById('duplicate-field-btn')?.addEventListener('click', duplicateField);

        // Keyboard hotkeys
        document.addEventListener('keydown', function(e) {
            if (isPublished || !selectedFieldId) return;

            // Ctrl+D to duplicate
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                duplicateField();
            }

            // Keyboard navigation arrow keys movements (1px / Shift+10px ratio offsets)
            let field = fields.find(f => f.id === selectedFieldId);
            if (field) {
                const step = e.shiftKey ? (10 / canvas.width) : (1 / canvas.width);
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    field.y_ratio = Math.max(0, field.y_ratio - step);
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    field.y_ratio = Math.min(1 - field.height_ratio, field.y_ratio + step);
                } else if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    field.x_ratio = Math.max(0, field.x_ratio - step);
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    field.x_ratio = Math.min(1 - field.width_ratio, field.x_ratio + step);
                }
                drawPlacedFields();
                updateSchemaInput();
            }
        });

        // Load document
        pdfjsLib.getDocument({
            url: url,
            withCredentials: true
        }).promise.then(function(pdfDoc_) {
            pdfDoc = pdfDoc_;
            totalPagesSpan.textContent = pdfDoc.numPages;
            
            // Build pages select navigation and thumbnails panel
            propPageSelect.innerHTML = '';
            pagesNavContainer.innerHTML = '';
            for (let i = 1; i <= pdfDoc.numPages; i++) {
                let opt = document.createElement('option');
                opt.value = i;
                opt.textContent = 'Σελίδα ' + i;
                propPageSelect.appendChild(opt);

                let btn = document.createElement('button');
                btn.className = 'btn btn-sm btn-outline-secondary text-start page-thumb-btn';
                btn.dataset.page = i;
                btn.innerHTML = `<i class="fa-solid fa-file-pdf me-2" aria-hidden="true"></i> Σελίδα ${i}`;
                btn.addEventListener('click', function() {
                    pageNum = i;
                    renderPage(pageNum);
                });
                pagesNavContainer.appendChild(btn);
            }

            renderPage(pageNum);
            updateSchemaInput();
        }).catch(function(err) {
            console.error("PDF loading error: ", err);
            // Hide spinner and show error
            pdfLoadingSpinner.classList.add('d-none');
            // Disable field placement
            document.querySelectorAll('.field-tool-btn').forEach(btn => btn.disabled = true);
            const container = document.getElementById('designer-canvas-container');
            container.innerHTML = `
                <div class="alert alert-danger m-3 p-4">
                    <h5><i class="fa-solid fa-triangle-exclamation me-2"></i> Σφάλμα Φόρτωσης PDF</h5>
                    <p class="small text-white-50">${err.message || err}</p>
                    <div class="mt-3">
                        <a href="/admin/document-templates/${<?= (int)$template['id'] ?>}" class="btn btn-secondary btn-sm me-2">Επιστροφή</a>
                        <button type="button" onclick="window.location.reload();" class="btn btn-warning btn-sm">Δοκιμάστε Ξανά</button>
                    </div>
                </div>
            `;
        });

        // Field Toolbox Click Handler
        document.querySelectorAll('.field-tool-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                addField(this.dataset.type);
            });
        });

        // Move select page event
        propPageSelect.addEventListener('change', function() {
            let field = fields.find(f => f.id === selectedFieldId);
            if (field) {
                saveHistoryState();
                field.page = parseInt(this.value);
                drawPlacedFields();
                updateSchemaInput();
            }
        });

        // Repo binding changes
        propRepoSelect.addEventListener('change', function() {
            let field = fields.find(f => f.id === selectedFieldId);
            if (field) {
                field.repo_slug = this.value;
                updateSchemaInput();
            }
        });

        // Validation inputs mapping
        [propValMin, propValMax, propValRegex, propValDefault].forEach(input => {
            input.addEventListener('input', function() {
                let field = fields.find(f => f.id === selectedFieldId);
                if (field) {
                    if (!field.validation) field.validation = {};
                    field.validation.min = propValMin.value;
                    field.validation.max = propValMax.value;
                    field.validation.regex = propValRegex.value;
                    field.validation.default = propValDefault.value;
                    updateSchemaInput();
                }
            });
        });

        // Delete field handler with custom confirm modal integration
        document.getElementById('delete-selected-field-btn')?.addEventListener('click', function() {
            if (selectedFieldId) {
                const modal = document.getElementById('app-confirm-modal');
                if (modal) {
                    const title = document.getElementById('app-confirm-title');
                    const message = document.getElementById('app-confirm-message');
                    const cancelButton = document.getElementById('app-confirm-cancel');
                    const acceptButton = document.getElementById('app-confirm-accept');

                    title.textContent = 'Διαγραφή Πεδίου';
                    message.textContent = 'Είστε βέβαιοι ότι θέλετε να διαγράψετε αυτό το πεδίο από το σχεδιαστή;';
                    acceptButton.textContent = 'Διαγραφή';
                    acceptButton.className = 'btn btn-sm btn-danger';

                    modal.classList.add('is-visible');
                    modal.setAttribute('aria-hidden', 'false');

                    const onCancel = function() {
                        modal.classList.remove('is-visible');
                        modal.setAttribute('aria-hidden', 'true');
                        cleanup();
                    };
                    const onAccept = function() {
                        saveHistoryState();
                        const targetField = fields.find(f => f.id === selectedFieldId);
                        if (targetField) {
                            console.log("field.deleted: " + targetField.key);
                        }

                        fields = fields.filter(f => f.id !== selectedFieldId);
                        selectedFieldId = null;
                        drawPlacedFields();
                        propsPanelNoSelect.classList.remove('d-none');
                        propsPanelForm.classList.add('d-none');
                        updateSchemaInput();
                        renderFieldListPanel();
                        modal.classList.remove('is-visible');
                        modal.setAttribute('aria-hidden', 'true');
                        cleanup();
                    };
                    const cleanup = function() {
                        cancelButton.removeEventListener('click', onCancel);
                        acceptButton.removeEventListener('click', onAccept);
                    };

                    cancelButton.addEventListener('click', onCancel);
                    acceptButton.addEventListener('click', onAccept);
                }
            }
        });

        // Property key change validation and translation mapping
        propKeyInput.addEventListener('input', function() {
            let field = fields.find(f => f.id === selectedFieldId);
            if (field) {
                field.key = this.value;
                drawPlacedFields();
                updateSchemaInput();
                validateUniqueKeys();
                renderFieldListPanel();
            }
        });
 
        // Auto name generator logic
        propLabelInput.addEventListener('input', function() {
            let field = fields.find(f => f.id === selectedFieldId);
            if (field) {
                field.label = this.value;
                
                // Translate standard labels automatically
                const labelLower = this.value.toLowerCase().trim();
                if (translateMap[labelLower]) {
                    field.key = translateMap[labelLower];
                    propKeyInput.value = field.key;
                }

                drawPlacedFields();
                updateSchemaInput();
                validateUniqueKeys();
                renderFieldListPanel();
            }
        });

        propRequiredInput.addEventListener('change', function() {
            let field = fields.find(f => f.id === selectedFieldId);
            if (field) {
                field.required = this.checked;
                updateSchemaInput();
                renderFieldListPanel();
            }
        });

        // Display order numeric property update handler
        const propDisplayOrderInput = document.getElementById('prop-display-order-input');
        propDisplayOrderInput.addEventListener('input', function() {
            let field = fields.find(f => f.id === selectedFieldId);
            if (field) {
                field.display_order = parseInt(this.value) || 0;
                updateSchemaInput();
                renderFieldListPanel();
            }
        });

        // Populate and render Field List Panel UI elements
        const fieldsListContainer = document.getElementById('designer-fields-list-container');
        function renderFieldListPanel() {
            fieldsListContainer.innerHTML = '';
            
            // Normalize any fields missing display_order default sequences of 10s increments
            fields.forEach((f, idx) => {
                if (f.display_order === undefined) {
                    f.display_order = (idx + 1) * 10;
                }
            });

            // Sort fields by display_order ASC, stable array index fallback
            const sorted = [...fields].sort((a, b) => {
                let diff = (a.display_order || 0) - (b.display_order || 0);
                if (diff !== 0) return diff;
                return fields.indexOf(a) - fields.indexOf(b);
            });

            if (sorted.length === 0) {
                fieldsListContainer.innerHTML = '<div class="text-muted small text-center p-2">Δεν υπάρχουν πεδία.</div>';
                return;
            }

            sorted.forEach(f => {
                let div = document.createElement('div');
                div.className = `d-flex align-items-center justify-content-between p-2 rounded mb-1 text-white small ${f.id === selectedFieldId ? 'bg-primary' : 'bg-dark'}`;
                div.style.cursor = 'pointer';
                div.style.border = '1px solid rgba(255,255,255,0.1)';
                
                // Add HTML5 Draggable parameters
                div.draggable = !isPublished;
                
                div.innerHTML = `
                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                        ${!isPublished ? '<span class="text-muted drag-handle-icon" style="cursor: grab;">☰</span>' : ''}
                        <div class="text-truncate" style="max-width: 140px;">
                            <strong>${f.label}</strong> <span class="text-muted">(${f.key})</span>
                        </div>
                    </div>
                    <span class="badge bg-secondary">Order: ${f.display_order}</span>
                `;

                // Select target PDF element on click
                div.addEventListener('click', function(e) {
                    if (e.target.classList.contains('drag-handle-icon')) return;
                    selectField(f.id);
                    if (parseInt(f.page) !== pageNum) {
                        pageNum = parseInt(f.page);
                        renderPage(pageNum);
                    }
                });

                // HTML5 Drag & Drop events listeners registrations
                if (!isPublished) {
                    div.addEventListener('dragstart', function(e) {
                        e.stopPropagation();
                        e.dataTransfer.setData('text/plain', f.id);
                        div.style.opacity = '0.5';
                    });
                    
                    div.addEventListener('dragend', function() {
                        div.style.opacity = '1';
                        // Refresh elements highlight
                        renderFieldListPanel();
                    });

                    div.addEventListener('dragover', function(e) {
                        e.preventDefault();
                    });

                    div.addEventListener('drop', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const draggedId = e.dataTransfer.getData('text/plain');
                        if (draggedId && draggedId !== f.id) {
                            saveHistoryState();
                            const draggedIdx = fields.findIndex(item => item.id === draggedId);
                            const targetIdx = fields.findIndex(item => item.id === f.id);
                            if (draggedIdx !== -1 && targetIdx !== -1) {
                                // Re-sequence display_order arrays
                                const draggedItem = fields[draggedIdx];
                                fields.splice(draggedIdx, 1);
                                fields.splice(targetIdx, 0, draggedItem);
                                
                                // Reset sequential values increments of 10s
                                fields.forEach((item, idx) => {
                                    item.display_order = (idx + 1) * 10;
                                });

                                drawPlacedFields();
                                updateSchemaInput();
                                renderFieldListPanel();
                                if (selectedFieldId) selectField(selectedFieldId);
                            }
                        }
                    });
                }

                fieldsListContainer.appendChild(div);
            });
        }

        // Auto sorting trigger
        document.getElementById('auto-order-pdf-btn').addEventListener('click', function() {
            const modal = document.getElementById('app-confirm-modal');
            if (!modal) return;
            
            const title = document.getElementById('app-confirm-title');
            const message = document.getElementById('app-confirm-message');
            const cancelButton = document.getElementById('app-confirm-cancel');
            const acceptButton = document.getElementById('app-confirm-accept');

            title.textContent = 'Αυτόματη σειρά από τη θέση στο PDF';
            message.textContent = 'Είστε βέβαιοι ότι θέλετε να επαναφέρετε τη σειρά εμφάνισης των πεδίων με βάση τη θέση τους στο PDF (Σελίδα -> Y -> X);';
            acceptButton.textContent = 'Επιβεβαίωση';
            acceptButton.className = 'btn btn-sm btn-info';

            modal.classList.add('is-visible');
            modal.setAttribute('aria-hidden', 'false');

            const onCancel = function() {
                modal.classList.remove('is-visible');
                modal.setAttribute('aria-hidden', 'true');
                cleanup();
            };
            const onAccept = function() {
                saveHistoryState();
                
                // Sort by page -> y_ratio -> x_ratio
                fields.sort((a, b) => {
                    let pageDiff = parseInt(a.page) - parseInt(b.page);
                    if (pageDiff !== 0) return pageDiff;
                    let yDiff = parseFloat(a.y_ratio) - parseFloat(b.y_ratio);
                    if (yDiff !== 0) return yDiff;
                    return parseFloat(a.x_ratio) - parseFloat(b.x_ratio);
                });

                // Set increments sequence of 10s
                fields.forEach((item, idx) => {
                    item.display_order = (idx + 1) * 10;
                });

                drawPlacedFields();
                updateSchemaInput();
                renderFieldListPanel();
                if (selectedFieldId) selectField(selectedFieldId);
                
                modal.classList.remove('is-visible');
                modal.setAttribute('aria-hidden', 'true');
                cleanup();
            };
            const cleanup = function() {
                cancelButton.removeEventListener('click', onCancel);
                acceptButton.removeEventListener('click', onAccept);
            };

            cancelButton.addEventListener('click', onCancel);
            acceptButton.addEventListener('click', onAccept);
        });

        // Trigger dynamic fields list rendering on init
        setTimeout(renderFieldListPanel, 200);

        // Toolbar Events
        document.getElementById('prev-page-btn').addEventListener('click', function() {
            if (pageNum <= 1) return;
            pageNum--;
            renderPage(pageNum);
        });

        document.getElementById('next-page-btn').addEventListener('click', function() {
            if (pageNum >= pdfDoc.numPages) return;
            pageNum++;
            renderPage(pageNum);
        });

        document.getElementById('zoom-in-btn').addEventListener('click', function() {
            scale += 0.2;
            renderPage(pageNum);
        });

        document.getElementById('zoom-out-btn').addEventListener('click', function() {
            if (scale <= 0.4) return;
            scale -= 0.2;
            renderPage(pageNum);
        });

        previewModeSwitch.addEventListener('change', function() {
            drawPlacedFields();
        });

        document.getElementById('clear-canvas-btn')?.addEventListener('click', function() {
            fields = [];
            selectedFieldId = null;
            drawPlacedFields();
            propsPanelNoSelect.classList.remove('d-none');
            propsPanelForm.classList.add('d-none');
            updateSchemaInput();
            renderFieldListPanel();
        });
    });
</script>
