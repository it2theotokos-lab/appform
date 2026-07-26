<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h1 class="h3 m-0 text-white">
            <i class="fa-solid fa-folder-open me-2 text-primary" aria-hidden="true"></i> 
            Οι Υποβολές μου
        </h1>
        <p class="text-muted small mb-0">Δείτε τα έγγραφα που έχετε υποβάλει επιτυχώς στο σύστημα.</p>
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
                            <th>Ημ. Υποβολής</th>
                            <th>Κατάσταση</th>
                            <th class="text-end">Ενέργειες</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($instances)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-square-check fa-2x mb-3 text-secondary" aria-hidden="true"></i>
                                    <div class="h6 text-white-50">Δεν βρέθηκαν υποβεβλημένα έγγραφα</div>
                                    <p class="mb-0 small text-muted">Όταν υποβάλετε οριστικά ένα σχέδιο, θα εμφανιστεί εδώ.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($instances as $inst): ?>
                                <tr>
                                    <td><strong><?= \App\Core\View::escape($inst['document_number']) ?></strong></td>
                                    <td><?= \App\Core\View::escape($inst['title']) ?></td>
                                    <td><?= \App\Core\View::escape($inst['template_title']) ?></td>
                                    <td><span class="badge bg-secondary">v<?= (int)$inst['version_number'] ?></span></td>
                                    <td><?= $inst['submitted_at'] ?></td>
                                    <td>
                                        <span class="badge bg-success">Υποβλήθηκε</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="/documents/<?= (int)$inst['id'] ?>" class="btn btn-sm btn-secondary">
                                            <i class="fa-solid fa-eye me-1" aria-hidden="true"></i> Προβολή
                                        </a>
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
