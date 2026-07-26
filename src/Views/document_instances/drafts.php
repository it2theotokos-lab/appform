<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h1 class="h3 m-0 text-white">
            <i class="fa-solid fa-file-pen me-2 text-primary" aria-hidden="true"></i> 
            Τα Πρόχειρά μου
        </h1>
        <p class="text-muted small mb-0">Δείτε και επεξεργαστείτε τα έγγραφα που έχετε αποθηκεύσει ως σχέδια (drafts).</p>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Κωδικός (Number)</th>
                            <th>Τίτλος</th>
                            <th>Πρότυπο</th>
                            <th>Έκδοση</th>
                            <th>Ημ. Δημιουργίας</th>
                            <th>Ημ. Τροποποίησης</th>
                            <th class="text-end">Ενέργειες</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($instances)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-2x mb-3 text-secondary" aria-hidden="true"></i>
                                    <div class="h6 text-white-50">Δεν βρέθηκαν πρόχειρα έγγραφα</div>
                                    <p class="mb-0 small text-muted">Μπορείτε να δημιουργήσετε ένα νέο έγγραφο από το μενού "Νέο Έγγραφο".</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($instances as $inst): ?>
                                <tr>
                                    <td><strong><?= \App\Core\View::escape($inst['document_number']) ?></strong></td>
                                    <td><?= \App\Core\View::escape($inst['title']) ?></td>
                                    <td><?= \App\Core\View::escape($inst['template_title']) ?></td>
                                    <td><span class="badge bg-secondary">v<?= (int)$inst['version_number'] ?></span></td>
                                    <td><?= $inst['created_at'] ?></td>
                                    <td><?= $inst['updated_at'] ?></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="/documents/<?= (int)$inst['id'] ?>/edit" class="btn btn-sm btn-premium">
                                                <i class="fa-solid fa-edit me-1" aria-hidden="true"></i> Επεξεργασία
                                            </a>
                                            <a href="/documents/<?= (int)$inst['id'] ?>/preview" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-info text-white">
                                                <i class="fa-solid fa-file-pdf me-1" aria-hidden="true"></i> Προεπισκόπηση
                                            </a>
                                            <form action="/documents/<?= (int)$inst['id'] ?>/cancel" method="POST" class="js-confirm-action d-inline" data-confirm-title="Ακύρωση Προχείρου" data-confirm-text="Θέλετε να ακυρώσετε αυτό το πρόχειρο έγγραφο; Η ενέργεια θα καταγραφεί στο ιστορικό.">
                                                <?= \App\Core\Csrf::field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-ban me-1" aria-hidden="true"></i> Ακύρωση
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
