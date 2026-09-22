<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="header-page-title mb-0">Διαχείριση Υποβολών</h1>
    <?php
    $canViewAll = $canViewAll ?? false;
    $canViewSubordinates = $canViewSubordinates ?? false;
    $activeScope = $activeScope ?? 'own';
    $queryParams = $_GET;
    ?>
    <div class="d-flex gap-2 flex-wrap justify-content-end">
    <div class="btn-group" role="group" aria-label="Εύρος Υποβολών">
        <a href="?<?= http_build_query(array_merge($queryParams, ['scope' => 'own'])) ?>" class="btn btn-sm <?= $activeScope === 'own' ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <i class="fa-solid fa-user me-1"></i> Οι Υποβολές μου
        </a>
        <?php if ($canViewSubordinates || $canViewAll): ?>
            <a href="?<?= http_build_query(array_merge($queryParams, ['scope' => 'subordinates'])) ?>" class="btn btn-sm <?= $activeScope === 'subordinates' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="fa-solid fa-users me-1"></i> Υποβολές Υφισταμένων
            </a>
        <?php endif; ?>
        <?php if ($canViewAll): ?>
            <a href="?<?= http_build_query(array_merge($queryParams, ['scope' => 'all'])) ?>" class="btn btn-sm <?= $activeScope === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="fa-solid fa-globe me-1"></i> Όλες οι Υποβολές
            </a>
        <?php endif; ?>
    </div>
    <?php if (\App\Core\Auth::role() === 'administrator' || \App\Core\Auth::hasPermission('submissions.review')): ?>
        <?php $exportQuery = http_build_query($queryParams); ?>
        <div class="dropdown">
            <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-file-export me-1"></i> Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="/admin/submissions/export/excel<?= $exportQuery !== '' ? '?' . htmlspecialchars($exportQuery) : '' ?>">
                        <i class="fa-solid fa-file-excel me-2 text-success"></i>Excel (.xlsx)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="/admin/submissions/export/csv<?= $exportQuery !== '' ? '?' . htmlspecialchars($exportQuery) : '' ?>">
                        <i class="fa-solid fa-file-csv me-2 text-info"></i>CSV
                    </a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
    </div>
</div>

<?php if ($success = \App\Core\Session::flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i>
        <?= \App\Core\View::escape($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
        <?= \App\Core\View::escape($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php
$formsOptions = ['' => 'Όλες οι Φόρμες'];
if (!empty($forms)) {
    foreach ($forms as $f) {
        $formsOptions[$f['id']] = $f['title'];
    }
}

$filtersConfig = [
    ['name' => 'search', 'label' => 'Αναζήτηση', 'type' => 'text', 'placeholder' => 'Τίτλος, χρήστης...', 'col' => 'col-md-3'],
    ['name' => 'form_id', 'label' => 'Φόρμα', 'type' => 'select', 'options' => $formsOptions, 'col' => 'col-md-2'],
    ['name' => 'status', 'label' => 'Κατάσταση', 'type' => 'select', 'options' => [
        '' => 'Όλες',
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'under_review' => 'In Review',
        'returned' => 'Returned for Correction',
        'approved' => 'Approved',
        'rejected' => 'Rejected'
    ], 'col' => 'col-md-2'],
    ['name' => 'date_from', 'label' => 'Από', 'type' => 'date', 'col' => 'col-md-2'],
    ['name' => 'date_to', 'label' => 'Έως', 'type' => 'date', 'col' => 'col-md-2']
];

include __DIR__ . '/../../shared/filter_bar.php';
?>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Φόρμα</th>
                    <th>Υποβλήθηκε από</th>
                    <th>Ημερομηνία</th>
                    <th>Κατάσταση</th>
                    <th class="text-end">Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Δεν υπάρχουν υποβολές χρηστών.</td></tr>
                <?php else: ?>
                    <?php foreach ($submissions as $sub): ?>
                        <tr>
                            <td>#<?= $sub['id'] ?></td>
                            <td><strong><?= \App\Core\View::escape($sub['form_title']) ?></strong></td>
                            <td><span class="badge-status-draft" style="color:var(--color-primary); font-weight: 500;">@<?= \App\Core\View::escape($sub['username']) ?></span></td>
                            <td><?= $sub['submitted_at'] ? date('d/m/Y H:i', strtotime($sub['submitted_at'])) : 'Προσχέδιο' ?></td>
                            <td>
                                <?php
                                $badgeClass = 'badge-status-draft';
                                if ($sub['status'] === 'approved') $badgeClass = 'badge-status-approved';
                                elseif ($sub['status'] === 'rejected') $badgeClass = 'badge-status-rejected';
                                elseif ($sub['status'] === 'under_review') $badgeClass = 'badge-status-pending';
                                ?>
                                <span class="<?= $badgeClass ?>"><?= strtoupper($sub['status']) ?></span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2 flex-wrap">
                                    <a href="/admin/submissions/<?= $sub['uuid'] ?>" class="btn btn-premium btn-sm" title="Προβολή / Αξιολόγηση">
                                        <i class="fa-solid fa-eye me-1" aria-hidden="true"></i> Προβολή
                                    </a>

                                    <?php if (
                                        $sub['status'] === 'draft'
                                        && (int)$sub['user_id'] === (int)\App\Core\Auth::id()
                                        && !empty($sub['form_slug'])
                                    ): ?>
                                        <a href="/forms/<?= rawurlencode($sub['form_slug']) ?>" class="btn btn-warning btn-sm" title="Επεξεργασία και Υποβολή Προσχεδίου">
                                            <i class="fa-solid fa-pen-to-square me-1" aria-hidden="true"></i> Επεξεργασία
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($sub['status'] !== 'draft'): ?>
                                        <form action="/admin/submissions/<?= $sub['uuid'] ?>/return-to-draft" method="POST" class="js-confirm-action d-inline" data-confirm-title="Επιστροφή σε Πρόχειρο" data-confirm-message="Η υποβολή θα επιστραφεί στον χρήστη ως Πρόχειρο (Draft) και θα μπορεί να την τροποποιήσει. Θέλετε να συνεχίσετε;" data-confirm-button="Επιστροφή" data-confirm-variant="warning">
                                            <?= \App\Core\Csrf::field() ?>
                                            <input type="hidden" name="review_notes" value="Επιστροφή σε πρόχειρο από διαχειριστή">
                                            <button type="submit" class="btn btn-warning btn-sm" title="Επιστροφή σε Πρόχειρο">
                                                <i class="fa-solid fa-rotate-left me-1" aria-hidden="true"></i> Σε Πρόχειρο
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($sub['status'] === 'submitted' || $sub['status'] === 'under_review'): ?>
                                        <form action="/admin/submissions/<?= $sub['uuid'] ?>/return" method="POST" class="js-confirm-action d-inline" data-confirm-title="Επιστροφή για Διόρθωση" data-confirm-message="Θέλετε να επιστρέψετε την υποβολή στον χρήστη για διορθώσεις;" data-confirm-button="Επιστροφή" data-confirm-variant="warning">
                                            <?= \App\Core\Csrf::field() ?>
                                            <input type="hidden" name="review_notes" value="Επιστροφή για διορθώσεις">
                                            <button type="submit" class="btn btn-outline-warning btn-sm" title="Επιστροφή για Διόρθωση">
                                                <i class="fa-solid fa-arrows-rotate me-1" aria-hidden="true"></i> Διόρθωση
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (\App\Core\Auth::role() === 'administrator' || \App\Core\Auth::hasPermission('submissions.delete')): ?>
                                        <form action="/admin/submissions/<?= $sub['uuid'] ?>/delete" method="POST" class="js-confirm-action d-inline" data-confirm-title="Διαγραφή Υποβολής" data-confirm-message="Η διαγραφή της υποβολής είναι οριστική. Θέλετε να συνεχίσετε;" data-confirm-button="Διαγραφή" data-confirm-variant="danger">
                                            <?= \App\Core\Csrf::field() ?>
                                            <button type="submit" class="btn btn-danger btn-sm" title="Διαγραφή">
                                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
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
