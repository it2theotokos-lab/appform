<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="header-page-title mb-0">Οι Υποβολές Φορμών μου</h1>
</div>

<?php if ($success = \App\Core\Session::flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i>
        <?= \App\Core\View::escape($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php
$filtersConfig = [
    ['name' => 'search', 'label' => 'Αναζήτηση', 'type' => 'text', 'placeholder' => 'Τίτλος φόρμας...', 'col' => 'col-md-6'],
    ['name' => 'status', 'label' => 'Κατάσταση', 'type' => 'select', 'options' => [
        '' => 'Όλες',
        'draft' => 'Draft (Προσχέδιο)',
        'submitted' => 'Submitted (Υποβλήθηκε)',
        'under_review' => 'In Review (Σε Αξιολόγηση)',
        'returned' => 'Returned for Correction (Επιστράφηκε)',
        'approved' => 'Approved (Εγκρίθηκε)',
        'rejected' => 'Rejected (Απορρίφθηκε)'
    ], 'col' => 'col-md-4']
];

include __DIR__ . '/../shared/filter_bar.php';
?>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Φόρμα</th>
                    <th>Ημερομηνία Δημιουργίας</th>
                    <th>Ημερομηνία Υποβολής</th>
                    <th>Κατάσταση</th>
                    <th class="text-end">Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Δεν βρέθηκαν υποβολές φορμών.</td></tr>
                <?php else: ?>
                    <?php foreach ($submissions as $sub): ?>
                        <tr>
                            <td>#<?= $sub['id'] ?></td>
                            <td><strong><?= \App\Core\View::escape($sub['form_title']) ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($sub['created_at'])) ?></td>
                            <td><?= $sub['submitted_at'] ? date('d/m/Y H:i', strtotime($sub['submitted_at'])) : '-' ?></td>
                            <td>
                                <?php
                                $badgeClass = 'badge-status-draft';
                                $statusLabel = strtoupper($sub['status']);
                                if ($sub['status'] === 'approved') { $badgeClass = 'badge-status-approved'; $statusLabel = 'APPROVED'; }
                                elseif ($sub['status'] === 'rejected') { $badgeClass = 'badge-status-rejected'; $statusLabel = 'REJECTED'; }
                                elseif ($sub['status'] === 'under_review') { $badgeClass = 'badge-status-pending'; $statusLabel = 'IN REVIEW'; }
                                elseif ($sub['status'] === 'returned') { $badgeClass = 'badge-status-pending'; $statusLabel = 'RETURNED'; }
                                ?>
                                <span class="<?= $badgeClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2 flex-wrap">
                                    <a href="/my-submissions/<?= $sub['uuid'] ?>" class="btn btn-premium btn-sm" title="Προβολή">
                                        <i class="fa-solid fa-eye me-1" aria-hidden="true"></i> Προβολή
                                    </a>

                                    <?php if ($sub['status'] === 'draft' || $sub['status'] === 'returned'): ?>
                                        <a href="/forms/<?= htmlspecialchars($sub['form_slug']) ?>" class="btn btn-warning btn-sm" title="Επεξεργασία">
                                            <i class="fa-solid fa-edit me-1" aria-hidden="true"></i> Επεξεργασία
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
