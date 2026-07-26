<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h1 class="h3 m-0 text-white">
            <i class="fa-solid fa-eye me-2 text-primary" aria-hidden="true"></i> 
            Προεπισκόπηση: <?= \App\Core\View::escape($template['title']) ?>
        </h1>
    </div>
    <div class="col-md-4 text-end">
        <a href="/admin/document-templates/<?= (int)$template['id'] ?>" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i> Επιστροφή στα Στοιχεία
        </a>
    </div>
</div>

<div class="row">
    <!-- Left Navigation / Page Thumbnails -->
    <div class="col-md-3">
        <div class="card p-3 mb-4" style="max-height: 700px; overflow-y: auto;">
            <h6 class="text-white mb-3">Πλοήγηση Σελίδων</h6>
            <div id="pages-nav-container" class="d-flex flex-column gap-2">
                <!-- Dynamically populated buttons -->
            </div>
        </div>
    </div>

    <!-- Center Canvas Preview and Toolbar -->
    <div class="col-md-9">
        <div class="card p-3 mb-4">
            <!-- Toolbar -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-3 border-bottom border-secondary">
                <div class="d-flex align-items-center gap-2">
                    <button id="prev-page-btn" class="btn btn-sm btn-secondary">
                        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <div class="d-flex align-items-center gap-1 text-white">
                        <span>Σελίδα</span>
                        <input type="number" id="page-num-input" class="form-control form-control-sm text-center bg-dark text-white border-secondary" style="width: 60px;" min="1" value="1">
                        <span>από <span id="total-pages-span">1</span></span>
                    </div>
                    <button id="next-page-btn" class="btn btn-sm btn-secondary">
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button id="zoom-out-btn" class="btn btn-sm btn-secondary" title="Zoom Out">
                        <i class="fa-solid fa-magnifying-glass-minus" aria-hidden="true"></i>
                    </button>
                    <span id="zoom-percentage" class="text-white small" style="min-width: 50px; text-align: center;">100%</span>
                    <button id="zoom-in-btn" class="btn btn-sm btn-secondary" title="Zoom In">
                        <i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i>
                    </button>
                    <button id="fit-width-btn" class="btn btn-sm btn-secondary small" title="Fit to Width">
                        Πλάτος
                    </button>
                    <button id="fit-page-btn" class="btn btn-sm btn-secondary small" title="Fit to Page">
                        Σελίδα
                    </button>
                </div>
            </div>

            <!-- PDF Render Canvas Area -->
            <div id="pdf-loading-indicator" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Φόρτωση...</span>
                </div>
                <p class="text-muted mt-2">Φόρτωση εγγράφου PDF...</p>
            </div>

            <div id="pdf-error-container" class="alert alert-danger d-none mb-0" role="alert">
                <i class="fa-solid fa-circle-xmark me-2" aria-hidden="true"></i>
                <span id="pdf-error-message">Αδυναμία φόρτωσης του αρχείου PDF.</span>
            </div>

            <div id="pdf-canvas-wrapper" class="text-center d-none" style="overflow: auto; max-height: 800px; background: #2b2b2b; padding: 20px; border-radius: 8px;">
                <canvas id="pdf-render-canvas" class="img-fluid shadow-lg" style="background: var(--color-surface);"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Scripts section containing local PDF.js integration -->
<script src="/assets/js/pdf.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const url = '<?= $pdfUrl ?>';
        
        // Configure local worker
        pdfjsLib.GlobalWorkerOptions.workerSrc = '/assets/js/pdf.worker.min.js';

        let pdfDoc = null,
            pageNum = 1,
            pageRendering = false,
            pageNumPending = null,
            scale = 1.2,
            canvas = document.getElementById('pdf-render-canvas'),
            ctx = canvas.getContext('2d'),
            wrapper = document.getElementById('pdf-canvas-wrapper'),
            loading = document.getElementById('pdf-loading-indicator'),
            errorContainer = document.getElementById('pdf-error-container'),
            errorMsg = document.getElementById('pdf-error-message'),
            pageNumInput = document.getElementById('page-num-input'),
            totalPagesSpan = document.getElementById('total-pages-span'),
            zoomPercentage = document.getElementById('zoom-percentage'),
            navContainer = document.getElementById('pages-nav-container');

        function showLoading(show) {
            if (show) {
                loading.classList.remove('d-none');
                wrapper.classList.add('d-none');
                errorContainer.classList.add('d-none');
            } else {
                loading.classList.add('d-none');
                wrapper.classList.remove('d-none');
            }
        }

        function showError(msg) {
            loading.classList.add('d-none');
            wrapper.classList.add('d-none');
            errorContainer.classList.remove('d-none');
            errorMsg.textContent = msg;
        }

        // Render PDF page
        function renderPage(num) {
            pageRendering = true;
            pdfDoc.getPage(num).then(function(page) {
                let viewport = page.getViewport({scale: scale});
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                // Render PDF page into canvas context
                let renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                let renderTask = page.render(renderContext);

                renderTask.promise.then(function() {
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });

                // Update active navigation state
                document.querySelectorAll('.page-nav-btn').forEach(btn => {
                    if (parseInt(btn.dataset.page) === num) {
                        btn.classList.replace('btn-outline-secondary', 'btn-primary');
                    } else {
                        btn.classList.replace('btn-primary', 'btn-outline-secondary');
                    }
                });
            });

            pageNumInput.value = num;
            zoomPercentage.textContent = Math.round(scale * 100) + '%';
        }

        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }

        // Setup page navigation list
        function setupNavigation() {
            navContainer.innerHTML = '';
            for (let i = 1; i <= pdfDoc.numPages; i++) {
                let btn = document.createElement('button');
                btn.className = 'btn btn-sm btn-outline-secondary text-start page-nav-btn';
                btn.dataset.page = i;
                btn.innerHTML = `<i class="fa-solid fa-file-pdf me-2" aria-hidden="true"></i> Σελίδα ${i}`;
                btn.addEventListener('click', function() {
                    pageNum = i;
                    queueRenderPage(pageNum);
                });
                navContainer.appendChild(btn);
            }
        }

        // Load document
        pdfjsLib.getDocument({
            url: url,
            withCredentials: true
        }).promise.then(function(pdfDoc_) {
            pdfDoc = pdfDoc_;
            totalPagesSpan.textContent = pdfDoc.numPages;
            pageNumInput.max = pdfDoc.numPages;

            setupNavigation();
            showLoading(false);
            renderPage(pageNum);
        }).catch(function(error) {
            showError('Σφάλμα φόρτωσης PDF: ' + error.message);
        });

        // Event Listeners
        document.getElementById('prev-page-btn').addEventListener('click', function() {
            if (pageNum <= 1) return;
            pageNum--;
            queueRenderPage(pageNum);
        });

        document.getElementById('next-page-btn').addEventListener('click', function() {
            if (pageNum >= pdfDoc.numPages) return;
            pageNum++;
            queueRenderPage(pageNum);
        });

        pageNumInput.addEventListener('change', function() {
            let val = parseInt(this.value);
            if (val >= 1 && val <= pdfDoc.numPages) {
                pageNum = val;
                queueRenderPage(pageNum);
            }
        });

        document.getElementById('zoom-in-btn').addEventListener('click', function() {
            scale += 0.2;
            queueRenderPage(pageNum);
        });

        document.getElementById('zoom-out-btn').addEventListener('click', function() {
            if (scale <= 0.4) return;
            scale -= 0.2;
            queueRenderPage(pageNum);
        });

        document.getElementById('fit-width-btn').addEventListener('click', function() {
            scale = 1.5;
            queueRenderPage(pageNum);
        });

        document.getElementById('fit-page-btn').addEventListener('click', function() {
            scale = 1.0;
            queueRenderPage(pageNum);
        });
    });
</script>
