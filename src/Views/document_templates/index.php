<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="h3 m-0 text-white"><i class="fa-solid fa-file-pdf me-2 text-primary" aria-hidden="true"></i> Πρότυπα Εγγράφων</h1>
        <p class="text-muted m-0">Διαχειριστείτε τα πρότυπα εγγράφων PDF/DOCX για ψηφιοποίηση εγγράφων.</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/document-templates/create" class="btn btn-premium">
            <i class="fa-solid fa-plus me-1" aria-hidden="true"></i> Δημιουργία Προτύπου
        </a>
    </div>
</div>

<?php if ($success = \App\Core\Session::flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i>
        <?= \App\Core\View::escape($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Τίτλος</th>
                    <th>Slug</th>
                    <th>Τύπος Αρχείου</th>
                    <th>Έκδοση</th>
                    <th>Κατάσταση</th>
                    <th>Δημιουργήθηκε Από</th>
                    <th class="text-end">Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($templates)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-folder-open fs-2 d-block mb-2" aria-hidden="true"></i>
                            Δεν βρέθηκαν πρότυπα εγγράφων.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($templates as $t): ?>
                        <tr>
                            <td><strong><?= \App\Core\View::escape($t['title']) ?></strong></td>
                            <td><code><?= \App\Core\View::escape($t['slug']) ?></code></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?= strtoupper($t['source_type']) ?>
                                </span>
                            </td>
                            <td>v<?= htmlspecialchars($t['version_number'] ?? '1') ?></td>
                            <td>
                                <span class="badge bg-warning text-dark">
                                    <?= strtoupper($t['status']) ?>
                                </span>
                            </td>
                            <td><?= \App\Core\View::escape($t['creator_name']) ?></td>
                             <td class="text-end">
                                 <a href="/admin/document-templates/<?= (int)$t['id'] ?>" class="btn btn-secondary btn-sm" title="Λεπτομέρειες / Προβολή">
                                     <i class="fa-solid fa-eye" aria-hidden="true"></i> Λεπτομέρειες
                                 </a>
                             </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
