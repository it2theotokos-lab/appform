<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="h3 m-0 text-white"><i class="fa-solid fa-file-invoice me-2 text-primary" aria-hidden="true"></i> Στοιχεία Προτύπου</h1>
        <p class="text-muted m-0">Λεπτομέρειες και επεξεργασία του προτύπου εγγράφου.</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/document-templates" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i> Επιστροφή
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card p-4 mb-4">
            <h5 class="text-white mb-3"><i class="fa-solid fa-circle-info me-2 text-primary" aria-hidden="true"></i> Γενικές Πληροφορίες</h5>
            <table class="table text-muted mb-0">
                <tbody>
                    <tr>
                        <td style="width: 250px;">Τίτλος:</td>
                        <td class="text-white font-weight-bold"><?= \App\Core\View::escape($template['title']) ?></td>
                    </tr>
                    <tr>
                        <td>Slug:</td>
                        <td class="text-white"><?= \App\Core\View::escape($template['slug']) ?></td>
                    </tr>
                    <tr>
                        <td>Περιγραφή:</td>
                        <td class="text-white"><?= \App\Core\View::escape($template['description'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>Τύπος Αρχείου:</td>
                        <td class="text-white">
                            <span class="badge bg-secondary"><?= strtoupper($template['source_type']) ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td>Όνομα Αρχικού Αρχείου:</td>
                        <td class="text-white"><?= \App\Core\View::escape(basename($template['original_file_path'])) ?></td>
                    </tr>
                    <tr>
                        <td>Κατάσταση Μετατροπής (DOCX):</td>
                        <td class="text-white">
                            <?php if ($template['source_type'] === 'docx'): ?>
                                <?php if ($template['converted_pdf_path']): ?>
                                    <span class="badge bg-success"><i class="fa-solid fa-check me-1" aria-hidden="true"></i> Επιτυχής</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i> Αποτυχία</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-dark">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Σελίδες:</td>
                        <td class="text-white"><?= (int)($template['page_count'] ?? 1) ?></td>
                    </tr>
                    <tr>
                        <td>Δημιουργήθηκε από:</td>
                        <td class="text-white"><?= \App\Core\View::escape($template['creator_name']) ?></td>
                    </tr>
                    <tr>
                        <td>Ημερομηνία Δημιουργίας:</td>
                        <td class="text-white"><?= $template['created_at'] ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card p-4">
            <h5 class="text-white mb-3"><i class="fa-solid fa-history me-2 text-primary" aria-hidden="true"></i> Ιστορικό Ενεργειών (Audit Logs)</h5>
            <div class="table-responsive">
                <table class="table text-muted mb-0">
                    <thead>
                        <tr class="text-white">
                            <th>Ημερομηνία</th>
                            <th>Χρήστης</th>
                            <th>Ενέργεια</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($audits)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Δεν βρέθηκε ιστορικό ενεργειών.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($audits as $audit): ?>
                                <tr>
                                    <td><?= $audit['created_at'] ?></td>
                                    <td><?= \App\Core\View::escape($audit['user_name']) ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= \App\Core\View::escape($audit['action']) ?></span>
                                    </td>
                                    <td><?= \App\Core\View::escape($audit['ip_address']) ?></td>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card p-4">
            <h5 class="text-white mb-3"><i class="fa-solid fa-gears me-2 text-primary" aria-hidden="true"></i> Ενέργειες</h5>
            
            <a href="/admin/document-templates/<?= (int)$template['id'] ?>/preview" class="btn btn-secondary w-100 mb-3">
                <i class="fa-solid fa-eye me-1" aria-hidden="true"></i> Άνοιγμα Προεπισκόπησης
            </a>

            <a href="/admin/document-templates/<?= (int)$template['id'] ?>/designer" class="btn btn-premium w-100 mb-3">
                <i class="fa-solid fa-crop me-1" aria-hidden="true"></i> Σχεδιαστής Πεδίων
            </a>

            <?php if ($template['status'] === 'draft'): ?>
                <form action="/admin/document-templates/<?= (int)$template['id'] ?>/publish" method="POST" class="mb-3 js-confirm-action" data-confirm-title="Δημοσίευση Προτύπου" data-confirm-text="Θέλετε να δημοσιεύσετε αυτό το πρότυπο; Μετά τη δημοσίευση θα είναι Read-Only.">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fa-solid fa-cloud-arrow-up me-1" aria-hidden="true"></i> Δημοσίευση (Publish)
                    </button>
                </form>
            <?php elseif ($template['status'] === 'published'): ?>
                <form action="/admin/document-templates/<?= (int)$template['id'] ?>/create-draft-version" method="POST" class="mb-3 js-confirm-action" data-confirm-title="Δημιουργία Draft Έκδοσης" data-confirm-text="Θέλετε να δημιουργήσετε ένα νέο Draft αντίγραφο αυτού του δημοσιευμένου προτύπου για επεξεργασία;">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="fa-solid fa-code-branch me-1" aria-hidden="true"></i> Δημιουργία Draft Έκδοσης
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($template['source_type'] === 'docx' && !$template['converted_pdf_path']): ?>
                <form action="/admin/document-templates/<?= (int)$template['id'] ?>/retry-conversion" method="POST" class="mb-3 js-confirm-action" data-confirm-title="Retry Conversion" data-confirm-text="Θέλετε να πραγματοποιήσετε ξανά τη μετατροπή του DOCX σε PDF;">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="fa-solid fa-rotate me-1" aria-hidden="true"></i> Προσπάθεια Μετατροπής Ξανά
                    </button>
                </form>
            <?php endif; ?>

            <a href="/admin/document-templates/<?= (int)$template['id'] ?>/file/original" target="_blank" rel="noopener noreferrer" class="btn btn-secondary w-100 mb-3">
                <i class="fa-solid fa-download me-1" aria-hidden="true"></i> Λήψη Αρχικού Αρχείου
            </a>

            <?php if ($template['converted_pdf_path']): ?>
                <a href="/admin/document-templates/<?= (int)$template['id'] ?>/file/converted" target="_blank" rel="noopener noreferrer" class="btn btn-secondary w-100 mb-3">
                    <i class="fa-solid fa-file-pdf me-1" aria-hidden="true"></i> Λήψη Converted PDF
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
