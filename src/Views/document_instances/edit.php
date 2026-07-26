<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h1 class="h3 m-0 text-white">
            <i class="fa-solid fa-file-pen me-2 text-primary" aria-hidden="true"></i> 
            Συμπλήρωση Εγγράφου: <?= \App\Core\View::escape($instance['document_number']) ?>
        </h1>
        <p class="text-muted small mb-0">Συμπληρώστε τα πεδία της φόρμας και δείτε τις αλλαγές live στο PDF preview στα δεξιά.</p>
    </div>
    <div class="col-md-4 text-end">
        <div class="d-inline-block text-white small me-3" id="autosave-status-container">
            <i class="fa-solid fa-cloud-arrow-up text-success me-1" aria-hidden="true"></i>
            <span id="autosave-status-text">Αποθηκεύτηκε αυτόματα</span>
        </div>
        <a href="/documents/drafts" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i> Επιστροφή
        </a>
    </div>
</div>

<!-- Template Version Status Header Card -->
<div class="card p-3 mb-3 border-secondary bg-dark">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 text-white small">
        <div>
            <i class="fa-solid fa-code-branch text-primary me-2"></i>
            Έκδοση εγγράφου: <strong>v<?= (int)$instance['version_number'] ?></strong>
            <span class="text-muted mx-2">|</span>
            Τελευταία διαθέσιμη έκδοση: <strong>v<?= (int)$latestVersionNumber ?></strong>
        </div>
        <div>
            <?php if ((int)$instance['version_number'] === (int)$latestVersionNumber): ?>
                <span class="text-success"><i class="fa-solid fa-circle-check me-1"></i> Χρησιμοποιείτε την τελευταία έκδοση.</span>
            <?php else: ?>
                <span class="text-warning me-3"><i class="fa-solid fa-triangle-exclamation me-1"></i> Υπάρχει νεότερη έκδοση του προτύπου.</span>
                <form action="/documents/<?= (int)$instance['id'] ?>/migrate-template-version" method="POST" class="js-confirm-action d-inline" data-confirm-title="Ενημέρωση στη Νεότερη Έκδοση" data-confirm-text="Το πρόχειρο θα ενημερωθεί στη νεότερη έκδοση του προτύπου. Οι υπάρχουσες τιμές με ίδιο αναγνωριστικό πεδίου θα διατηρηθούν. Νέα πεδία θα προστεθούν κενά.">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="btn btn-xs btn-premium" style="font-size: 0.75rem; padding: 2px 8px;">
                        <i class="fa-solid fa-arrows-rotate me-1"></i> Ενημέρωση στη Νεότερη Έκδοση
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left dynamic inputs form -->
    <div class="col-md-5">
        <div class="card p-4 mb-4">
            <form id="document-instance-form" action="/documents/<?= (int)$instance['id'] ?>/save" method="POST">
                <?= \App\Core\Csrf::field() ?>
                
                <div class="mb-4">
                    <label class="form-label text-white">Τίτλος Εγγράφου</label>
                    <input type="text" name="title" id="document-title-input" class="form-control bg-dark text-white border-secondary" value="<?= \App\Core\View::escape($instance['title']) ?>">
                </div>

                <hr class="border-secondary my-4">

                <h5 class="text-white mb-3"><i class="fa-solid fa-list-check text-primary me-2"></i> Πεδία Φόρμας</h5>

                <?php
                $fields = json_decode($instance['fields_schema_json'], true) ?: [];
                // Sort by display_order ASC, then original order, then key
                $originalOrderMap = array_flip(array_keys($fields));
                usort($fields, function($a, $b) use ($originalOrderMap) {
                    $orderA = isset($a['display_order']) ? (int)$a['display_order'] : 100000;
                    $orderB = isset($b['display_order']) ? (int)$b['display_order'] : 100000;
                    if ($orderA !== $orderB) {
                        return $orderA - $orderB;
                    }
                    $idxA = $originalOrderMap[$a['key'] ?? ''] ?? 0;
                    $idxB = $originalOrderMap[$b['key'] ?? ''] ?? 0;
                    if ($idxA !== $idxB) {
                        return $idxA - $idxB;
                    }
                    return strcmp($a['key'] ?? '', $b['key'] ?? '');
                });

                foreach ($fields as $field):
                    $key = $field['key'];
                    $type = $field['type'];
                    $label = $field['label'];
                    $required = !empty($field['required']);
                    $currentVal = $values[$key] ?? $field['validation']['default'] ?? '';
                    $repoSlug = $field['repo_slug'] ?? '';
                ?>
                    <div class="mb-3 field-input-group" data-key="<?= \App\Core\View::escape($key) ?>" data-type="<?= \App\Core\View::escape($type) ?>">
                        <label class="form-label text-white small font-weight-bold">
                            <?= \App\Core\View::escape($label) ?>
                            <?php if ($required): ?>
                                <span class="text-danger">*</span>
                            <?php endif; ?>
                        </label>

                        <?php if ($type === 'consent'): ?>
                            <div class="form-check">
                                <input type="checkbox" name="values[<?= \App\Core\View::escape($key) ?>]" value="1" class="form-check-input dynamic-field-input" <?= $currentVal === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label text-muted small">Συμφωνώ και αποδέχομαι</label>
                            </div>
                        <?php elseif ($type === 'signature'): ?>
                            <?php 
                            $sig = \App\Models\DocumentSignature::findByField($instance['id'], $key);
                            ?>
                            <div class="border border-secondary rounded p-3 bg-dark text-center signature-capture-box" data-field-key="<?= \App\Core\View::escape($key) ?>" data-page="<?= (int)($field['page'] ?? 1) ?>" style="cursor: pointer;">
                                <?php if ($sig): ?>
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <img src="/documents/<?= (int)$instance['id'] ?>/signature/<?= \App\Core\View::escape($key) ?>" alt="Signature" class="img-fluid bg-white rounded p-1" style="max-height: 80px;" id="signature-img-input-<?= \App\Core\View::escape($key) ?>">
                                        <div class="d-flex gap-2">
                                            <span class="text-success small"><i class="fa-solid fa-circle-check"></i> signed</span>
                                            <button type="button" class="btn btn-xs btn-outline-danger clear-sig-btn" data-field-key="<?= \App\Core\View::escape($key) ?>" style="font-size:0.7rem; padding: 1px 5px;">Clear</button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="values[<?= \App\Core\View::escape($key) ?>]" value="signed" class="dynamic-field-input signature-hidden-input" id="sig-hidden-<?= \App\Core\View::escape($key) ?>">
                                <?php else: ?>
                                    <div class="text-center py-2 text-white-50 border-dashed rounded" style="border: 2px dashed rgba(255,255,255,0.15)">
                                        <i class="fa-solid fa-signature fa-2x mb-2 text-primary" aria-hidden="true"></i>
                                        <div class="small fw-bold">Πατήστε για Υπογραφή</div>
                                    </div>
                                    <input type="hidden" name="values[<?= \App\Core\View::escape($key) ?>]" value="" class="dynamic-field-input signature-hidden-input" id="sig-hidden-<?= \App\Core\View::escape($key) ?>">
                                <?php endif; ?>
                            </div>
                        <?php elseif (!empty($repoSlug) && !empty($repos[$repoSlug])): ?>
                            <!-- Repository Selection Select -->
                            <select name="values[<?= \App\Core\View::escape($key) ?>]" class="form-select bg-dark text-white border-secondary dynamic-field-input">
                                <option value="">(Επιλέξτε)</option>
                                <?php foreach ($repos[$repoSlug] as $item): ?>
                                    <option value="<?= \App\Core\View::escape($item['value']) ?>" <?= $currentVal === $item['value'] ? 'selected' : '' ?>>
                                        <?= \App\Core\View::escape($item['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($type === 'number'): ?>
                            <input type="number" name="values[<?= \App\Core\View::escape($key) ?>]" class="form-control bg-dark text-white border-secondary dynamic-field-input" value="<?= \App\Core\View::escape($currentVal) ?>">
                        <?php elseif ($type === 'date'): ?>
                            <input type="date" name="values[<?= \App\Core\View::escape($key) ?>]" class="form-control bg-dark text-white border-secondary dynamic-field-input" value="<?= \App\Core\View::escape($currentVal) ?>">
                        <?php else: ?>
                            <input type="text" name="values[<?= \App\Core\View::escape($key) ?>]" class="form-control bg-dark text-white border-secondary dynamic-field-input" value="<?= \App\Core\View::escape($currentVal) ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="mt-4 pt-3 border-top border-secondary">
                    <button type="submit" id="save-draft-btn" class="btn btn-premium w-100 mb-2">
                        <i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i> Αποθήκευση Draft
                    </button>
                    <button type="button" id="submit-instance-btn" class="btn btn-success w-100">
                        <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i> Οριστική Υποβολή
                    </button>
                </div>
            </form>

            <form id="submit-hidden-form" action="/documents/<?= (int)$instance['id'] ?>/submit" method="POST" class="d-none">
                <?= \App\Core\Csrf::field() ?>
            </form>
        </div>
    </div>

    <!-- Right PDF Preview panel -->
    <div class="col-md-7">
        <div class="card p-3 mb-4">
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
                <div class="d-flex align-items-center gap-2">
                    <button id="zoom-out-btn" class="btn btn-sm btn-secondary"><i class="fa-solid fa-minus" aria-hidden="true"></i></button>
                    <span id="zoom-percentage" class="text-white small">100%</span>
                    <button id="zoom-in-btn" class="btn btn-sm btn-secondary"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
                </div>
            </div>

            <!-- PDF Page Wrapper with absolute ratios coordinates overlay -->
            <div id="designer-canvas-container" class="position-relative text-center" style="overflow: auto; max-height: 800px; background: #2b2b2b; padding: 20px; min-height: 500px; border-radius: 8px;">
                <div id="designer-page-wrapper" class="position-relative d-inline-block shadow-lg" style="background: var(--color-surface);">
                    <canvas id="pdf-render-canvas" style="display: block;"></canvas>
                    <div id="fields-overlay-container" class="position-absolute top-0 start-0 w-100 h-100" style="pointer-events: none;">
                        <!-- Absolute overlay fields populated dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles for placed visual coordinate boxes -->
<style>
    .placed-field-element {
        position: absolute;
        border: 1px solid rgba(0, 198, 255, 0.4);
        background: rgba(0, 198, 255, 0.08);
        color: #2b2b2b;
        font-family: inherit;
        font-size: 11px;
        padding: 4px;
        border-radius: 2px;
        box-sizing: border-box;
        text-align: left;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
</style>

<script src="/assets/js/pdf.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const url = '<?= $pdfUrl ?>';
        const fields = <?= json_encode(json_decode($instance['fields_schema_json'], true) ?: []) ?>;
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
            autosaveStatusText = document.getElementById('autosave-status-text'),
            autosaveStatusContainer = document.getElementById('autosave-status-container');

        let isDirty = false;

        // Render PDF page
        function renderPage(num) {
            pdfDoc.getPage(num).then(function(page) {
                let viewport = page.getViewport({scale: scale});
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                let renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                page.render(renderContext).promise.then(function() {
                    drawOverlayFields();
                });
            });

            currentPageSpan.textContent = num;
            zoomPercentage.textContent = Math.round(scale * 100) + '%';
        }

        // Draw overlay mapping ratios coordinates relative to viewport scale
        function drawOverlayFields() {
            overlay.innerHTML = '';
            const wrapperWidth = canvas.width;
            const wrapperHeight = canvas.height;

            fields.forEach(field => {
                if (parseInt(field.page) !== pageNum) return;

                let div = document.createElement('div');
                div.className = 'placed-field-element';
                
                const leftPx = field.x_ratio * wrapperWidth;
                const topPx = field.y_ratio * wrapperHeight;
                const widthPx = field.width_ratio * wrapperWidth;
                const heightPx = field.height_ratio * wrapperHeight;

                div.style.left = leftPx + 'px';
                div.style.top = topPx + 'px';
                div.style.width = widthPx + 'px';
                div.style.height = heightPx + 'px';

                // Fetch dynamic value from form input element
                let val = '';
                const inputEl = document.querySelector(`[name="values[${field.key}]"]`);
                if (inputEl) {
                    if (inputEl.type === 'checkbox') {
                        val = inputEl.checked ? '✔' : '';
                    } else {
                        val = inputEl.value;
                    }
                }

                if (field.type === 'signature') {
                    // Check if signature hidden input has signed flag
                    const isSigned = val === 'signed';
                    if (isSigned) {
                        div.innerHTML = `<img src="/documents/<?= (int)$instance['id'] ?>/signature/${field.key}?t=${Date.now()}" style="width:100%; height:100%; object-fit:contain; background: transparent;">`;
                    } else {
                        div.innerHTML = `<span style="font-family: cursive; color: #ff007f;">[Εκκρεμεί Υπογραφή]</span>`;
                    }
                } else {
                    div.textContent = val || '';
                }

                overlay.appendChild(div);
            });
        }

        // Load document
        pdfjsLib.getDocument({
            url: url,
            withCredentials: true
        }).promise.then(function(pdfDoc_) {
            pdfDoc = pdfDoc_;
            totalPagesSpan.textContent = pdfDoc.numPages;
            renderPage(pageNum);
        });

        // Live inputs updates listener
        document.querySelectorAll('.dynamic-field-input').forEach(input => {
            input.addEventListener('input', function() {
                isDirty = true;
                drawOverlayFields();
            });
            input.addEventListener('change', function() {
                isDirty = true;
                drawOverlayFields();
            });
        });

        // HTML5 Canvas Modal Signature Drawing Engine initialization
        let sigModal = document.getElementById('app-signature-modal');
        let sigCanvas = document.getElementById('signature-modal-canvas');
        let sigCtx = sigCanvas.getContext('2d');
        let activeSigKey = null;
        let activeSigPage = 1;

        let drawing = false;
        let strokes = [];
        let currentStroke = [];

        // Pen brush parameters
        sigCtx.lineWidth = 3;
        sigCtx.lineCap = 'round';
        sigCtx.lineJoin = 'round';
        sigCtx.strokeStyle = '#000000';

        function resizeCanvas() {
            const rect = sigCanvas.parentElement.getBoundingClientRect();
            sigCanvas.width = rect.width;
            sigCanvas.height = rect.height;
            redrawCanvas();
        }

        function redrawCanvas() {
            sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
            sigCtx.lineWidth = 3;
            sigCtx.lineCap = 'round';
            sigCtx.lineJoin = 'round';
            sigCtx.strokeStyle = '#000000';

            strokes.forEach(stroke => {
                if (stroke.length < 1) return;
                sigCtx.beginPath();
                sigCtx.moveTo(stroke[0].x, stroke[0].y);
                for (let i = 1; i < stroke.length; i++) {
                    sigCtx.lineTo(stroke[i].x, stroke[i].y);
                }
                sigCtx.stroke();
            });
        }

        // Open Signature Modal Click
        document.querySelectorAll('.signature-capture-box').forEach(box => {
            box.addEventListener('click', function(e) {
                // Bypass if click on clear button
                if (e.target.classList.contains('clear-sig-btn')) return;

                activeSigKey = this.dataset.fieldKey;
                activeSigPage = parseInt(this.dataset.page || 1);

                strokes = [];
                currentStroke = [];
                
                sigModal.classList.add('is-visible');
                sigModal.setAttribute('aria-hidden', 'false');
                
                setTimeout(resizeCanvas, 100);
            });
        });

        // Clear sig canvas button
        document.getElementById('sig-canvas-clear').addEventListener('click', function() {
            strokes = [];
            currentStroke = [];
            redrawCanvas();
        });

        // Undo last stroke
        document.getElementById('sig-canvas-undo').addEventListener('click', function() {
            if (strokes.length > 0) {
                strokes.pop();
                redrawCanvas();
            }
        });

        // Cancel modal
        document.getElementById('app-signature-cancel').addEventListener('click', function() {
            sigModal.classList.remove('is-visible');
            sigModal.setAttribute('aria-hidden', 'true');
        });

        // Save and Accept Signature PNG
        const acceptButton = document.getElementById('signature-accept-btn');
        if (!acceptButton) {
            console.error('Signature accept button not found');
        }
        acceptButton?.addEventListener('click', function() {
            if (strokes.length === 0) {
                alert('Παρακαλώ σχεδιάστε την υπογραφή σας πριν πατήσετε Αποδοχή.');
                return;
            }

            // Disable button loading state
            acceptButton.disabled = true;
            acceptButton.textContent = 'Αποθήκευση...';

            const dataUrl = sigCanvas.toDataURL('image/png');

            // Send base64 to server
            let formData = new FormData();
            formData.append('field_key', activeSigKey);
            formData.append('page', activeSigPage);
            formData.append('signature_data', dataUrl);
            formData.append('_token', '<?= \App\Core\Csrf::token() ?>');

            fetch(`/documents/<?= (int)$instance['id'] ?>/signature`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                // Restore button states
                acceptButton.disabled = false;
                acceptButton.textContent = 'Αποδοχή';

                if (data.success) {
                    sigModal.classList.remove('is-visible');
                    sigModal.setAttribute('aria-hidden', 'true');

                    // Update UI indicator in form left
                    const box = document.querySelector(`.signature-capture-box[data-field-key="${activeSigKey}"]`);
                    if (box) {
                        box.innerHTML = `
                            <div class="d-flex flex-column align-items-center gap-2">
                                <img src="${data.signature_url}?t=${Date.now()}" alt="Signature" class="img-fluid bg-white rounded p-1" style="max-height: 80px;" id="signature-img-input-${activeSigKey}">
                                <div class="d-flex gap-2">
                                    <span class="text-success small"><i class="fa-solid fa-circle-check"></i> signed</span>
                                    <button type="button" class="btn btn-xs btn-outline-danger clear-sig-btn" data-field-key="${activeSigKey}" style="font-size:0.7rem; padding: 1px 5px;">Clear</button>
                                </div>
                            </div>
                            <input type="hidden" name="values[${activeSigKey}]" value="signed" class="dynamic-field-input signature-hidden-input" id="sig-hidden-${activeSigKey}">
                        `;
                        // Rebind clear listener
                        bindClearListener(box.querySelector('.clear-sig-btn'));
                    }

                    // Update dynamic-field-input change tracking
                    isDirty = true;
                    drawOverlayFields();
                } else {
                    alert(data.message || 'Αποτυχία αποθήκευσης υπογραφής.');
                }
            })
            .catch(() => {
                acceptButton.disabled = false;
                acceptButton.textContent = 'Αποδοχή';
                alert('Σφάλμα δικτύου κατά την αποθήκευση της υπογραφής.');
            });
        });

        function bindClearListener(btn) {
            if (!btn) return;
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const key = this.dataset.fieldKey;

                let formData = new FormData();
                formData.append('csrf_token', '<?= \App\Core\Csrf::token() ?>');

                fetch(`/documents/<?= (int)$instance['id'] ?>/signature/${key}`, {
                    method: 'DELETE',
                    body: new URLSearchParams(formData),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const box = document.querySelector(`.signature-capture-box[data-field-key="${key}"]`);
                        if (box) {
                            box.innerHTML = `
                                <div class="text-center py-2 text-white-50 border-dashed rounded" style="border: 2px dashed rgba(255,255,255,0.15)">
                                    <i class="fa-solid fa-signature fa-2x mb-2 text-primary" aria-hidden="true"></i>
                                    <div class="small fw-bold">Πατήστε για Υπογραφή</div>
                                </div>
                                <input type="hidden" name="values[${key}]" value="" class="dynamic-field-input signature-hidden-input" id="sig-hidden-${key}">
                            `;
                        }
                        isDirty = true;
                        drawOverlayFields();
                    } else {
                        alert('Αποτυχία εκκαθάρισης υπογραφής.');
                    }
                });
            });
        }

        // Bind existing clear buttons on init
        document.querySelectorAll('.clear-sig-btn').forEach(btn => bindClearListener(btn));

        // Drawing events handlers for mouse/touch/pointer devices
        function getCoords(e) {
            const rect = sigCanvas.getBoundingClientRect();
            if (e.touches && e.touches.length > 0) {
                return {
                    x: e.touches[0].clientX - rect.left,
                    y: e.touches[0].clientY - rect.top
                };
            }
            return {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top
            };
        }

        function startDrawing(e) {
            e.preventDefault();
            drawing = true;
            currentStroke = [getCoords(e)];
            strokes.push(currentStroke);
            redrawCanvas();
        }

        function draw(e) {
            if (!drawing) return;
            e.preventDefault();
            currentStroke.push(getCoords(e));
            redrawCanvas();
        }

        function stopDrawing(e) {
            if (drawing) {
                drawing = false;
                redrawCanvas();
            }
        }

        // Mouse events
        sigCanvas.addEventListener('mousedown', startDrawing);
        sigCanvas.addEventListener('mousemove', draw);
        sigCanvas.addEventListener('mouseup', stopDrawing);
        sigCanvas.addEventListener('mouseleave', stopDrawing);

        // Touch events
        sigCanvas.addEventListener('touchstart', startDrawing);
        sigCanvas.addEventListener('touchmove', draw);
        sigCanvas.addEventListener('touchend', stopDrawing);

        // Pointer events for Pen Tablets pressure
        sigCanvas.addEventListener('pointerdown', startDrawing);
        sigCanvas.addEventListener('pointermove', draw);
        sigCanvas.addEventListener('pointerup', stopDrawing);

        // Submit confirm actions
        document.getElementById('submit-instance-btn').addEventListener('click', function() {
            const modal = document.getElementById('app-confirm-modal');
            if (modal) {
                const title = document.getElementById('app-confirm-title');
                const message = document.getElementById('app-confirm-message');
                const cancelButton = document.getElementById('app-confirm-cancel');
                const acceptButton = document.getElementById('app-confirm-accept');

                title.textContent = 'Οριστική Υποβολή';
                message.textContent = 'Είστε βέβαιοι ότι θέλετε να υποβάλετε οριστικά το έγγραφο; Μετά την υποβολή, δεν θα μπορείτε να το τροποποιήσετε.';
                acceptButton.textContent = 'Υποβολή';
                acceptButton.className = 'btn btn-sm btn-success';

                modal.classList.add('is-visible');
                modal.setAttribute('aria-hidden', 'false');

                const onCancel = function() {
                    modal.classList.remove('is-visible');
                    modal.setAttribute('aria-hidden', 'true');
                    cleanup();
                };
                const onAccept = function() {
                    isDirty = false;
                    document.getElementById('submit-hidden-form').submit();
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
        });

        // Autosave logic (Every 45s if dirty)
        setInterval(function() {
            if (!isDirty) return;

            autosaveStatusText.textContent = 'Αποθήκευση...';
            const formData = new FormData(document.getElementById('document-instance-form'));

            fetch('/documents/<?= (int)$instance['id'] ?>/autosave', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    isDirty = false;
                    autosaveStatusText.textContent = 'Αποθηκεύτηκε αυτόματα';
                } else {
                    autosaveStatusText.textContent = 'Η αυτόματη αποθήκευση απέτυχε';
                }
            })
            .catch(() => {
                autosaveStatusText.textContent = 'Η αυτόματη αποθήκευση απέτυχε';
            });
        }, 45000);

        // Warn before leaving unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = 'Έχετε μη αποθηκευμένες αλλαγές.';
            }
        });

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
    });
</script>
