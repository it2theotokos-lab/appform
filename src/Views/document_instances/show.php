<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h1 class="h3 m-0 text-white">
            <i class="fa-solid fa-file-invoice me-2 text-primary" aria-hidden="true"></i> 
            Στοιχεία Εγγράφου: <?= \App\Core\View::escape($instance['document_number']) ?>
        </h1>
    </div>
    <div class="col-md-4 text-end">
        <a href="/documents/drafts" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i> Επιστροφή στα Έγγραφα
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card p-4 mb-4">
            <h5 class="text-white mb-3"><i class="fa-solid fa-circle-info me-2 text-primary"></i> Πληροφορίες</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <span class="text-muted small">Τίτλος:</span>
                    <div class="text-white font-weight-bold"><?= \App\Core\View::escape($instance['title']) ?></div>
                </div>
                <div class="col-md-6 mb-3">
                    <span class="text-muted small">Πρότυπο:</span>
                    <div class="text-white"><?= \App\Core\View::escape($instance['template_title']) ?> (v<?= (int)$instance['version_number'] ?>)</div>
                    <?php if ((int)$instance['version_number'] !== (int)$latestVersionNumber): ?>
                        <span class="badge bg-warning text-dark mt-1" style="font-size: 0.65rem;">v<?= (int)$latestVersionNumber ?> Available</span>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <span class="text-muted small">Κατάσταση:</span>
                    <div>
                        <?php if ($instance['status'] === 'draft'): ?>
                            <span class="badge bg-warning">Draft</span>
                        <?php elseif ($instance['status'] === 'cancelled'): ?>
                            <span class="badge bg-danger">Cancelled</span>
                        <?php elseif ($instance['status'] === 'returned_for_correction'): ?>
                            <span class="badge bg-warning text-dark">Επιστράφηκε για Διόρθωση</span>
                        <?php elseif ($instance['status'] === 'approved'): ?>
                            <span class="badge bg-success">Approved</span>
                        <?php elseif ($instance['status'] === 'rejected'): ?>
                            <span class="badge bg-danger">Rejected</span>
                        <?php else: ?>
                            <span class="badge bg-info"><?= \App\Core\View::escape($instance['status']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <span class="text-muted small">Ημ. Δημιουργίας:</span>
                    <div class="text-white"><?= $instance['created_at'] ?></div>
                </div>
            </div>
            
            <?php if ($instance['status'] === 'returned_for_correction'): ?>
                <?php
                // Fetch latest return comment from workflow
                $db = \App\Core\Database::getInstance();
                $stmtRetComm = $db->prepare("
                    SELECT comment FROM workflow_comments 
                    WHERE document_instance_id = ? 
                    ORDER BY id DESC LIMIT 1
                ");
                $stmtRetComm->execute([$instance['id']]);
                $returnComment = $stmtRetComm->fetchColumn();
                ?>
                <div class="alert alert-warning mt-3 mb-0">
                    <h6 class="alert-heading font-heading"><i class="fa-solid fa-circle-exclamation me-2"></i>Λόγος Επιστροφής για Διόρθωση:</h6>
                    <p class="mb-0 text-dark"><?= \App\Core\View::escape($returnComment ?: 'Δεν καθορίστηκε σχόλιο.') ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card p-4 mb-4">
            <h5 class="text-white mb-3"><i class="fa-solid fa-list-check me-2 text-primary"></i> Δεδομένα Εγγράφου</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Πεδίο (Label)</th>
                            <th>Τιμή</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $fields = json_decode($instance['fields_schema_json'], true) ?: [];
                        // Sort fields by display_order ASC
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

                        if (empty($fields)):
                        ?>
                            <tr>
                                <td colspan="2" class="text-muted text-center">Δεν υπάρχουν πεδία.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fields as $field): ?>
                                <tr>
                                    <td><strong><?= \App\Core\View::escape($field['label']) ?></strong></td>
                                    <td>
                                        <?php if ($field['type'] === 'signature'): ?>
                                            <?php 
                                            $sig = \App\Models\DocumentSignature::findByField($instance['id'], $field['key']);
                                            if ($sig):
                                            ?>
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="/documents/<?= (int)$instance['id'] ?>/signature/<?= \App\Core\View::escape($field['key']) ?>" alt="Signature" style="max-height: 50px; background: white; padding: 2px; border-radius: 4px;">
                                                    <span class="text-success small"><i class="fa-solid fa-circle-check"></i> Υπογράφηκε στις <?= $sig['signed_at'] ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-warning small">[Εκκρεμεί Υπογραφή]</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?= \App\Core\View::escape($values[$field['key']] ?? '(Κενό)') ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            </div>
        </div>

        <!-- Workflow Timeline History Panel -->
        <?php if (!empty($workflowHistory)): ?>
            <div class="card p-4 mb-4">
                <h5 class="text-white mb-3"><i class="fa-solid fa-route me-2 text-primary"></i> Ροή Εγκρίσεων & Ιστορικό</h5>
                <div class="flow-timeline">
                    <?php foreach ($workflowHistory as $hist): ?>
                        <div class="d-flex align-items-center mb-2 justify-content-between p-2 rounded bg-dark border-secondary">
                            <div>
                                <span class="badge bg-secondary me-2">Βήμα <?= (int)$hist['step_order'] ?></span>
                                <span class="text-white font-weight-bold">
                                    <?php
                                    $stepName = $hist['step_name'];
                                    if ($stepName === 'Manager Review') {
                                        $stepName = 'Έλεγχος Προϊσταμένου';
                                    } elseif ($stepName === 'Director Signature') {
                                        $stepName = 'Υπογραφή Διευθυντή';
                                    }
                                    echo \App\Core\View::escape($stepName);
                                    ?>
                                </span>
                                <?php if ($hist['actor_name']): ?>
                                    <span class="text-muted small"> | Ενέργεια από: <?= \App\Core\View::escape($hist['actor_name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($hist['status'] === 'approved'): ?>
                                    <span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Εγκρίθηκε</span>
                                <?php elseif ($hist['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger"><i class="fa-solid fa-circle-xmark me-1"></i> Απορρίφθηκε</span>
                                <?php elseif ($hist['status'] === 'returned'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-arrow-rotate-left me-1"></i> Επιστράφηκε</span>
                                <?php elseif ($hist['status'] === 'active'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-spinner fa-spin me-1"></i> Εκκρεμεί</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= \App\Core\View::escape($hist['status']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card p-4">
            <h5 class="text-white mb-3"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i> Ιστορικό Ενεργειών</h5>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Ημερομηνία</th>
                            <th>Χρήστης</th>
                            <th>Ενέργεια</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($audits)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">Δεν βρέθηκε ιστορικό.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($audits as $aud): ?>
                                <tr>
                                    <td><?= $aud['created_at'] ?></td>
                                    <td><?= \App\Core\View::escape($aud['user_name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= \App\Core\View::escape($aud['action']) ?></span></td>
                                    <td><?= \App\Core\View::escape($aud['ip_address']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card p-4">
            <h5 class="text-white mb-3"><i class="fa-solid fa-gears me-2 text-primary"></i> Ενέργειες</h5>
            <?php
            $isCreator = ((int)$instance['created_by'] === (int)\App\Core\Auth::id() || (int)$instance['owner_id'] === (int)\App\Core\Auth::id());
            ?>

            <?php if ($isCreator): ?>
                <?php if ($instance['status'] === 'draft'): ?>
                    <a href="/documents/<?= (int)$instance['id'] ?>/edit" class="btn btn-premium w-100 mb-3">
                        <i class="fa-solid fa-edit me-1" aria-hidden="true"></i> Επεξεργασία Σχεδίου
                    </a>
                    <form action="/documents/<?= (int)$instance['id'] ?>/submit" method="POST" class="js-confirm-action mb-3" data-confirm-title="Υποβολή Εγγράφου" data-confirm-text="Θέλετε να υποβάλετε οριστικά το έγγραφο; Μετά την υποβολή, δεν θα μπορείτε να το τροποποιήσετε.">
                        <?= \App\Core\Csrf::field() ?>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fa-solid fa-check me-1" aria-hidden="true"></i> Οριστική Υποβολή
                        </button>
                    </form>
                    <form action="/documents/<?= (int)$instance['id'] ?>/delete" method="POST" class="js-confirm-action mb-3" data-confirm-title="Διαγραφή Σχεδίου" data-confirm-text="Θέλετε να διαγράψετε αυτό το σχέδιο εγγράφου;">
                        <?= \App\Core\Csrf::field() ?>
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fa-solid fa-trash me-1" aria-hidden="true"></i> Διαγραφή
                        </button>
                    </form>
                <?php elseif ($instance['status'] === 'returned_for_correction'): ?>
                    <a href="/documents/<?= (int)$instance['id'] ?>/edit" class="btn btn-premium w-100 mb-3">
                        <i class="fa-solid fa-edit me-1" aria-hidden="true"></i> Επεξεργασία
                    </a>
                    <form action="/documents/<?= (int)$instance['id'] ?>/submit" method="POST" class="js-confirm-action mb-3" data-confirm-title="Επανυποβολή Εγγράφου" data-confirm-text="Θέλετε να επανυποβάλετε το διορθωμένο έγγραφο;">
                        <?= \App\Core\Csrf::field() ?>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fa-solid fa-share me-1" aria-hidden="true"></i> Επανυποβολή
                        </button>
                    </form>
                <?php elseif ($instance['status'] === 'submitted' || $instance['status'] === 'in_review'): ?>
                    <div class="alert alert-secondary py-2 small mb-3">
                        <i class="fa-solid fa-lock me-1"></i> Προβολή μόνο (Σε Αξιολόγηση)
                    </div>
                <?php elseif ($instance['status'] === 'approved' || $instance['status'] === 'finalized'): ?>
                    <div class="alert alert-success py-2 small mb-3">
                        <i class="fa-solid fa-circle-check me-1"></i> Το έγγραφο έχει εγκριθεί
                    </div>
                <?php elseif ($instance['status'] === 'rejected'): ?>
                    <div class="alert alert-danger py-2 small mb-3">
                        <i class="fa-solid fa-circle-xmark me-1"></i> Το έγγραφο έχει απορριφθεί
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <a href="/documents/<?= (int)$instance['id'] ?>/preview" target="_blank" rel="noopener noreferrer" class="btn btn-secondary w-100 mb-3">
                <i class="fa-solid fa-file-pdf me-1" aria-hidden="true"></i> Προεπισκόπηση Συμπληρωμένου Εγγράφου
            </a>

            <a href="/documents/<?= (int)$instance['id'] ?>/preview-pdf/download" class="btn btn-outline-info w-100 mb-3">
                <i class="fa-solid fa-download me-1" aria-hidden="true"></i> Λήψη Προχείρου PDF
            </a>

            <!-- Final PDF Generation Metadata & Download buttons panel -->
            <?php if (!empty($finalFile)): ?>
                <hr class="border-secondary my-3">
                <h6 class="text-white mb-2"><i class="fa-solid fa-file-circle-check text-primary me-2"></i> Τελικό PDF Αρχείο</h6>
                
                <?php if ($finalFile['generation_status'] === 'pending'): ?>
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="fa-solid fa-clock me-1"></i> Δημιουργία τελικού PDF σε αναμονή
                    </div>
                <?php elseif ($finalFile['generation_status'] === 'processing'): ?>
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="fa-solid fa-spinner fa-spin me-1"></i> Δημιουργείται το τελικό PDF...
                    </div>
                <?php elseif ($finalFile['generation_status'] === 'failed'): ?>
                    <div class="alert alert-danger py-2 small mb-3">
                        <i class="fa-solid fa-circle-xmark me-1"></i> Η δημιουργία τελικού PDF απέτυχε
                    </div>
                    <?php if (\App\Core\Auth::hasPermission('document_instances.manage') || \App\Core\Auth::role() === 'administrator'): ?>
                        <div class="card bg-dark border-danger p-2 mb-2" style="font-size:0.75rem;">
                            <span class="text-danger">Σφάλμα:</span>
                            <span class="text-muted"><?= \App\Core\View::escape($finalFile['generation_error']) ?></span>
                        </div>
                        <form action="/documents/<?= (int)$instance['id'] ?>/retry-final-pdf" method="POST" class="js-confirm-action" data-confirm-title="Επαναπροσπάθεια PDF" data-confirm-text="Θέλετε να εκκινήσετε ξανά την παραγωγή του τελικού PDF;">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-warning w-100 mb-3">
                                <i class="fa-solid fa-rotate-right me-1"></i> Επαναπροσπάθεια Δημιουργίας
                            </button>
                        </form>
                    <?php endif; ?>
                <?php elseif ($finalFile['generation_status'] === 'completed'): ?>
                    <div class="alert alert-success py-2 small mb-3">
                        <i class="fa-solid fa-circle-check me-1"></i> Το τελικό PDF είναι έτοιμο (Finalized)
                    </div>
                    <a href="/documents/<?= (int)$instance['id'] ?>/final-pdf" target="_blank" rel="noopener noreferrer" class="btn btn-premium w-100 mb-3">
                        <i class="fa-solid fa-file-export me-1"></i> Άνοιγμα Τελικού PDF
                    </a>
                    <a href="/documents/<?= (int)$instance['id'] ?>/final-pdf/download" class="btn btn-outline-info w-100 mb-3">
                        <i class="fa-solid fa-download me-1"></i> Λήψη Τελικού PDF
                    </a>

                    <!-- Metadata details panel -->
                    <div class="card p-2 bg-dark border-secondary" style="font-size: 0.75rem; line-height: 1.3;">
                        <div class="mb-1"><span class="text-muted">Ημ. Παραγωγής:</span> <span class="text-white"><?= $finalFile['generated_at'] ?></span></div>
                        <div class="mb-1"><span class="text-muted">Μέγεθος:</span> <span class="text-white"><?= number_format($finalFile['file_size'] / 1024, 1) ?> KB</span></div>
                        <div class="mb-1"><span class="text-muted">Σελίδες:</span> <span class="text-white"><?= (int)$finalFile['page_count'] ?></span></div>
                        <div class="mb-1"><span class="text-muted">Δημιουργήθηκε από:</span> <span class="text-white"><?= \App\Core\View::escape($finalFile['generator_name']) ?></span></div>
                        <div class="mb-1 text-truncate">
                            <span class="text-muted">SHA256:</span> 
                            <code class="text-white" title="<?= \App\Core\View::escape($finalFile['sha256_hash']) ?>"><?= substr($finalFile['sha256_hash'], 0, 8) ?>...<?= substr($finalFile['sha256_hash'], -8) ?></code>
                            <button type="button" class="btn btn-xs btn-link text-primary p-0 ms-1" onclick="navigator.clipboard.writeText('<?= \App\Core\View::escape($finalFile['sha256_hash']) ?>'); alert('Αντιγράφηκε: ' + '<?= \App\Core\View::escape($finalFile['sha256_hash']) ?>')">Copy</button>
                        </div>
                    </div>
                    <?php if (\App\Core\Auth::hasPermission('document_instances.manage') || \App\Core\Auth::role() === 'administrator'): ?>
                        <form action="/documents/<?= (int)$instance['id'] ?>/retry-final-pdf" method="POST" class="js-confirm-action mt-2" data-confirm-title="Επαναδημιουργία PDF" data-confirm-text="Προσοχή: Αυτό θα αντικαταστήσει το υπάρχον τελικό PDF αρχείο. Θέλετε να προχωρήσετε;">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="btn btn-xs btn-outline-secondary w-100">
                                <i class="fa-solid fa-arrows-rotate me-1"></i> Αναπαραγωγή PDF (Admin)
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
