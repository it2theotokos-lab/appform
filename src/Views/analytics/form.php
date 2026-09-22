<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="/admin/analytics" class="btn btn-outline-secondary btn-sm mb-2"><i class="fa-solid fa-arrow-left"></i> Πίσω στα Analytics</a>
        <h3 class="font-heading text-white mb-0"><?= \App\Core\View::escape($title) ?></h3>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/analytics/forms/<?= $form['id'] ?>/pdf" target="_blank" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-file-pdf me-1"></i> Εξαγωγή PDF</a>
        <a href="/admin/exports/csv/<?= $form['id'] ?>" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-file-csv me-1"></i> Εξαγωγή CSV</a>
        <a href="/admin/exports/excel/<?= $form['id'] ?>" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-file-excel me-1"></i> Εξαγωγή Excel</a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="glass-panel p-3 text-center">
            <small class="text-muted d-block text-uppercase">Συνολικές Υποβολές</small>
            <span class="fs-2 fw-bold text-white"><?= $stats['total'] ?></span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="glass-panel p-3 text-center">
            <small class="text-muted d-block text-uppercase">Εγκεκριμένες (Approved)</small>
            <span class="fs-2 fw-bold text-success"><?= $stats['approved'] ?></span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="glass-panel p-3 text-center">
            <small class="text-muted d-block text-uppercase">Υπό Αξιολόγηση</small>
            <span class="fs-2 fw-bold text-warning"><?= $stats['under_review'] ?></span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="glass-panel p-3 text-center">
            <small class="text-muted d-block text-uppercase">Προσχέδια (Drafts)</small>
            <span class="fs-2 fw-bold text-info"><?= $stats['draft'] ?></span>
        </div>
    </div>
</div>

<!-- Extra Status Breakdown Row -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="glass-panel p-3 text-center">
            <small class="text-muted d-block text-uppercase">Επεστραμμένες (Returned)</small>
            <span class="fs-2 fw-bold text-warning"><?= $stats['returned'] ?></span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-panel p-3 text-center">
            <small class="text-muted d-block text-uppercase">Απορριφθείσες (Rejected)</small>
            <span class="fs-2 fw-bold text-danger"><?= $stats['rejected'] ?></span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-panel p-3 text-center">
            <small class="text-muted d-block text-uppercase">Υποβληθείσες (Submitted)</small>
    </div>
</div>

<!-- Capacity Progress Panel -->
<?php if (!empty($form['maximum_submissions'])): 
    $completedCount = $stats['submitted'] + $stats['under_review'] + $stats['returned'] + $stats['approved'] + $stats['rejected'];
    $targetCount = (int)$form['maximum_submissions'];
    $remainingCount = max(0, $targetCount - $completedCount);
    $rate = $targetCount > 0 ? round(($completedCount / $targetCount) * 100, 2) : 0;
?>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="glass-panel p-4">
            <h5 class="font-heading text-white mb-3"><i class="fa-solid fa-gauge-high me-2"></i> Ποσοστό Συμμετοχής & Στόχος Δείγματος</h5>
            <div class="row text-center mb-3">
                <div class="col-md-3">
                    <small class="text-muted d-block text-uppercase">Ολοκληρωμένες Υποβολές</small>
                    <span class="fs-3 fw-bold text-white"><?= $completedCount ?></span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block text-uppercase">Στόχος (Target)</small>
                    <span class="fs-3 fw-bold text-info"><?= $targetCount ?></span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block text-uppercase">Υπολειπόμενες</small>
                    <span class="fs-3 fw-bold text-warning"><?= $remainingCount ?></span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block text-uppercase">Ποσοστό Συμμετοχής</small>
                    <span class="fs-3 fw-bold text-success"><?= $rate ?>%</span>
                </div>
            </div>
            <div class="progress" style="height: 12px; background: rgba(255,255,255,0.1); border-radius: 6px;">
                <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width: <?= min(100, $rate) ?>%; border-radius: 6px;"></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Fields Analysis Section -->
<h5 class="font-heading text-white mb-3"><i class="fa-solid fa-chart-pie me-2"></i> Κατανομή Επιλογών ανά Πεδίο</h5>

<?php if (empty($form['survey_analytics'])): ?>
    <div class="alert alert-info text-white-50 border-glass bg-transparent p-4 mb-4">
        <i class="fa-solid fa-circle-info me-2"></i> Τα Survey Analytics και η εμφάνιση γραφημάτων απαντήσεων έχουν απενεργοποιηθεί για αυτή τη φόρμα.
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <?php foreach ($fieldsAnalysis as $key => $analysis): ?>
        <div class="col-md-6">
            <div class="glass-panel p-4 h-100">
                <h6 class="text-white font-heading mb-3 border-bottom border-glass pb-2">
                    <?= htmlspecialchars($analysis['label'] ?? $key) ?> (<?= strtoupper($analysis['type']) ?>)
                </h6>

                <?php if ($analysis['type'] === 'number'): ?>
                    <div class="row text-center mt-3">
                        <div class="col-6 mb-3">
                            <small class="text-muted d-block">Average</small>
                            <span class="fs-4 text-white fw-bold"><?= $analysis['avg'] ?></span>
                        </div>
                        <div class="col-6 mb-3">
                            <small class="text-muted d-block">Sum</small>
                            <span class="fs-4 text-white fw-bold"><?= $analysis['sum'] ?></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Min</small>
                            <span class="fs-4 text-white fw-bold"><?= $analysis['min'] ?></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Max</small>
                            <span class="fs-4 text-white fw-bold"><?= $analysis['max'] ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Επιλογή</th>
                                    <th>Υποβολές</th>
                                    <th>Ποσοστό</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analysis['distribution'] as $item): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($item['label']) ?></strong></td>
                                        <td><?= $item['count'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px; background: rgba(255,255,255,0.1);">
                                                    <div class="progress-bar bg-primary" style="width: <?= $item['percentage'] ?>%;"></div>
                                                </div>
                                                <small class="text-white"><?= $item['percentage'] ?>%</small>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($form['survey_analytics'])): ?>
                        <div class="chart-container" style="position: relative; height:200px; width:100%;">
                            <canvas id="chart-<?= htmlspecialchars($key) ?>"></canvas>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Recent Submissions Table & Summary Statistics -->
<div class="row g-4 mt-2">
    <!-- Recent submissions table -->
    <div class="col-md-7">
        <div class="glass-panel p-4">
            <h5 class="font-heading text-white mb-4"><i class="fa-solid fa-list me-2"></i> Πρόσφατες Υποβολές</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle text-white mb-0">
                    <thead>
                        <tr class="text-muted">
                            <th>ID</th>
                            <th>Υποβλήθηκε από</th>
                            <th>Κατάσταση</th>
                            <th>Ημερομηνία</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentSubmissions)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Δεν υπάρχουν υποβολές ακόμη.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentSubmissions as $sub): ?>
                                <tr>
                                    <td><strong>#<?= $sub['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($sub['submitter_name'] ?: 'Εξωτερικός Χρήστης') ?></td>
                                    <td>
                                        <?php
                                        $badge = 'badge-status-draft';
                                        if ($sub['status'] === 'approved') $badge = 'badge-status-approved';
                                        elseif ($sub['status'] === 'rejected') $badge = 'badge-status-rejected';
                                        elseif ($sub['status'] === 'under_review') $badge = 'badge-status-pending';
                                        elseif ($sub['status'] === 'returned') $badge = 'badge-status-pending';
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= strtoupper($sub['status']) ?></span>
                                    </td>
                                    <td>
                                        <small><?= $sub['submitted_at'] ? date('d/m/Y H:i', strtotime($sub['submitted_at'])) : date('d/m/Y H:i', strtotime($sub['created_at'])) . ' (DRAFT)' ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Workflow Progress Statistics -->
    <div class="col-md-5">
        <div class="glass-panel p-4">
            <h5 class="font-heading text-white mb-4"><i class="fa-solid fa-tasks me-2"></i> Στατιστικά Ροής Εργασιών</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle text-white mb-0">
                    <thead>
                        <tr class="text-muted">
                            <th>Βήμα Μετάβασης</th>
                            <th>Πλήθος</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historyStats)): ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted py-3">Δεν υπάρχουν καταγεγραμμένες μεταβάσεις.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historyStats as $statusKey => $cnt): ?>
                                <tr>
                                    <td><strong>&rarr; <?= strtoupper($statusKey) ?></strong></td>
                                    <td><?= $cnt ?> μεταβάσεις</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($form['survey_analytics'])): ?>
<!-- Load Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const fieldsData = <?= json_encode($fieldsAnalysis) ?>;
    
    Object.keys(fieldsData).forEach(key => {
        const field = fieldsData[key];
        if (field.type === 'number') return;
        
        const canvas = document.getElementById(`chart-${key}`);
        if (!canvas) return;
        
        const labels = [];
        const counts = [];
        
        if (field.distribution) {
            field.distribution.forEach(item => {
                labels.push(item.label);
                counts.push(item.count);
            });
        }
        
        // Define premium custom chart configuration styles
        const chartType = (labels.length <= 3) ? 'pie' : 'bar';
        
        new Chart(canvas.getContext('2d'), {
            type: chartType,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Υποβολές',
                    data: counts,
                    backgroundColor: [
                        'rgba(0, 86, 179, 0.7)',
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(23, 162, 184, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(111, 66, 193, 0.7)'
                    ],
                    borderColor: 'rgba(255, 255, 255, 0.15)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: (chartType === 'pie'),
                        labels: {
                            color: '#fff',
                            font: { size: 10 }
                        }
                    }
                },
                scales: chartType === 'bar' ? {
                    y: {
                        ticks: { color: '#fff', stepSize: 1 },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    },
                    x: {
                        ticks: { color: '#fff' },
                        grid: { display: false }
                    }
                } : {}
            }
        });
    });
});
</script>
<?php endif; ?>
