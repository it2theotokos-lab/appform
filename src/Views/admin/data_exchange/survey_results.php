<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="card-title mb-1"><i class="fa-solid fa-chart-pie me-2 text-primary" aria-hidden="true"></i> Αναφορά Αποτελεσμάτων Έρευνας</h2>
            <p class="text-muted">Φόρμα: <strong><?= \App\Core\View::escape($form['title']) ?></strong></p>
        </div>
        <a href="/admin/data-exchange" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i> Επιστροφή
        </a>
    </div>

    <!-- Executive Summary Widgets -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="kpi-card" style="--kpi-color: var(--color-primary); --kpi-color-bg: var(--color-primary-light);">
                <div class="kpi-icon"><i class="fa-solid fa-database"></i></div>
                <div class="kpi-body">
                    <div class="kpi-value"><?= $stats['total_submissions'] ?></div>
                    <div class="kpi-label">Συνολικές Υποβολές</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card" style="--kpi-color: var(--color-success); --kpi-color-bg: var(--color-success-bg);">
                <div class="kpi-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="kpi-body">
                    <div class="kpi-value"><?= $stats['total_submissions'] ?></div>
                    <div class="kpi-label">Ολοκληρωμένες</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="kpi-card" style="--kpi-color: var(--color-warning); --kpi-color-bg: var(--color-warning-bg);">
                <div class="kpi-icon"><i class="fa-solid fa-filter"></i></div>
                <div class="kpi-body">
                    <div class="kpi-value"><?= htmlspecialchars($filters['department'] ?: 'Όλα') ?></div>
                    <div class="kpi-label">Φίλτρο Τμήματος</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Question Analysis Section -->
    <h5 class="section-title mb-4 font-semibold text-soft">Ανάλυση Απαντήσεων ανά Ερώτηση</h5>

    <?php foreach ($stats['questions'] as $q): ?>
        <div class="card mb-4 p-4" style="background: var(--color-bg); border-color: var(--color-border);">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h6 class="font-bold text-soft mb-1" style="font-size: 1rem;"><?= \App\Core\View::escape($q['label']) ?></h6>
                    <small class="text-muted">Κλειδί ερώτησης: <code><?= htmlspecialchars($q['key']) ?></code> | Τύπος: <span class="badge bg-secondary"><?= htmlspecialchars($q['type']) ?></span></small>
                </div>
                <div class="text-end">
                    <span class="badge bg-success-bg text-success px-2 py-1">Απαντήσεις: <?= $q['total_responses'] ?></span>
                    <span class="badge bg-danger-bg text-danger px-2 py-1">Παραλείψεις: <?= $q['skipped'] ?></span>
                </div>
            </div>

            <?php if (!empty($q['distribution'])): ?>
                <!-- Visual Chart Preview -->
                <div class="row align-items-center mb-3">
                    <div class="col-md-5 text-center">
                        <?php
                        $chartType = ($q['type'] === 'radio' || $q['type'] === 'select' || $q['type'] === 'yesno') ? 'pie' : 'bar';
                        $chartSvg = \App\Services\ChartImageService::generateSvgDataUri($q, $chartType);
                        ?>
                        <?php if ($chartSvg): ?>
                            <img src="<?= $chartSvg ?>" alt="Chart" class="img-fluid rounded border p-2 bg-white" style="max-height: 200px;">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-7">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr class="table-light">
                                        <th>Απάντηση / Επιλογή</th>
                                        <th style="width: 90px;">Συχνότητα</th>
                                        <th style="width: 110px;">Ποσοστό</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($q['distribution'] as $d): ?>
                                        <tr>
                                            <td><?= \App\Core\View::escape($d['value']) ?></td>
                                            <td><strong><?= $d['count'] ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress flex-grow-1" style="height: 6px; background: rgba(0,0,0,0.05);">
                                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $d['percentage'] ?>%"></div>
                                                    </div>
                                                    <span style="font-size: 0.8rem; font-weight: 600; min-width: 35px; text-align: right;"><?= $d['percentage'] ?>%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php elseif ($q['average'] !== null): ?>
                <!-- Numeric Statistics Summary Card -->
                <div class="row text-center mt-2">
                    <div class="col-md-3">
                        <div class="p-3 border rounded bg-surface">
                            <small class="text-muted d-block mb-1">Μέσος Όρος (Average)</small>
                            <span class="fs-4 font-bold text-soft"><?= $q['average'] ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 border rounded bg-surface">
                            <small class="text-muted d-block mb-1">Διάμεσος (Median)</small>
                            <span class="fs-4 font-bold text-soft"><?= $q['median'] ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 border rounded bg-surface">
                            <small class="text-muted d-block mb-1">Ελάχιστη Τιμή</small>
                            <span class="fs-4 font-bold text-soft"><?= $q['min'] ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 border rounded bg-surface">
                            <small class="text-muted d-block mb-1">Μέγιστη Τιμή</small>
                            <span class="fs-4 font-bold text-soft"><?= $q['max'] ?></span>
                        </div>
                    </div>
                </div>
            <?php elseif (!empty($q['text_responses'])): ?>
                <!-- Textual Appendix -->
                <div class="border rounded bg-surface p-3" style="max-height: 250px; overflow-y: auto;">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($q['text_responses'] as $txt): ?>
                            <li class="list-group-item bg-transparent text-soft py-2 px-0"><i class="fa-solid fa-quote-left me-2 text-muted"></i> <?= \App\Core\View::escape($txt) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p class="text-muted small">Δεν υπάρχουν επαρκή δεδομένα για αυτή την ερώτηση.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
