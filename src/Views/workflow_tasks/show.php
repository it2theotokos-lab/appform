<!-- Focus Mode toolbar header -->
<div class="row mb-4 align-items-center document-header-toolbar p-3 rounded border" style="margin-top: -15px; background: var(--color-surface); border-color: var(--color-border) !important;">
    <div class="col-md-6 d-flex align-items-center gap-3">
        <a href="/workflow/tasks" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Πίσω στις Εργασίες
        </a>
        <div>
            <h4 class="h5 m-0 text-soft font-heading"><?= \App\Core\View::escape($task['doc_title']) ?></h4>
            <small class="text-muted"><?= \App\Core\View::escape($task['document_number']) ?> | Βήμα: <?= \App\Core\View::escape($task['step_name']) ?></small>
        </div>
    </div>
    <div class="col-md-6 text-end d-flex align-items-center justify-content-end gap-2">
        <span class="badge bg-warning text-dark"><i class="fa-solid fa-spinner fa-spin me-1"></i> Εκκρεμεί</span>
        <a href="<?= $pdfUrl ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-file-pdf me-1"></i> Προεπισκόπηση Εγγράφου
        </a>
    </div>
</div>

<div class="row">
    <!-- Center: Primary metadata detail info block -->
    <div class="col-md-8">
        <div class="card p-4 mb-4">
            <h5 class="text-soft mb-3"><i class="fa-solid fa-circle-info text-primary me-2"></i> Στοιχεία Εγγράφου</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <span class="text-muted small">Τίτλος:</span>
                    <div class="text-soft"><?= \App\Core\View::escape($task['doc_title']) ?></div>
                </div>
                <div class="col-md-6 mb-3">
                    <span class="text-muted small">Πρότυπο:</span>
                    <div class="text-soft"><?= \App\Core\View::escape($task['template_title']) ?></div>
                </div>
                <div class="col-md-6 mb-3">
                    <span class="text-muted small">Δημιουργός:</span>
                    <div class="text-soft"><?= \App\Core\View::escape($task['creator_name']) ?></div>
                </div>
                <div class="col-md-6 mb-3">
                    <span class="text-muted small">Ημ. Υποβολής:</span>
                    <div class="text-soft"><?= $task['created_at'] ?></div>
                </div>
            </div>
            
            <hr class="my-3" style="border-color: var(--color-border) !important;">
            
            <h6 class="text-soft mb-2"><i class="fa-solid fa-list-check text-primary me-2"></i> Συμπληρωμένες Τιμές Φόρμας</h6>
            <div class="p-3 rounded border" style="background: var(--color-bg); border-color: var(--color-border) !important;">
                <?php foreach ($values as $key => $val): ?>
                    <div class="mb-2">
                        <span class="text-muted small"><?= \App\Core\View::escape($key) ?>:</span>
                        <div class="text-soft"><?= \App\Core\View::escape($val) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- History Timeline -->
        <div class="card p-4 mb-4">
            <h5 class="text-soft mb-3"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Ιστορικό Εγκρίσεων</h5>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Βήμα</th>
                            <th>Υπεύθυνος</th>
                            <th>Απόφαση</th>
                            <th>Ημ. Ενέργειας</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td>
                                    <?php
                                    $stepName = $h['step_name'];
                                    if ($stepName === 'Manager Review') {
                                        $stepName = 'Έλεγχος Προϊσταμένου';
                                    } elseif ($stepName === 'Director Signature') {
                                        $stepName = 'Υπογραφή Διευθυντή';
                                    }
                                    echo \App\Core\View::escape($stepName);
                                    ?>
                                </td>
                                <td><?= \App\Core\View::escape($h['actor_name'] ?? 'Σε αναμονή') ?></td>
                                <td>
                                    <?php if ($h['status'] === 'approved'): ?>
                                        <span class="badge bg-success">Εγκρίθηκε</span>
                                    <?php elseif ($h['status'] === 'rejected'): ?>
                                        <span class="badge bg-danger">Απορρίφθηκε</span>
                                    <?php elseif ($h['status'] === 'returned'): ?>
                                        <span class="badge bg-warning text-dark">Επιστράφηκε</span>
                                    <?php elseif ($h['status'] === 'active'): ?>
                                        <span class="badge bg-info">Εκκρεμεί</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= \App\Core\View::escape($h['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $h['acted_at'] ?? '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Side Sidebar panel: Actions Form details & Comments Thread -->
    <div class="col-md-4">
        <div class="card p-4 mb-4">
            <h5 class="text-soft mb-3"><i class="fa-solid fa-signature text-primary me-2"></i> Λήψη Απόφασης</h5>
            
            <form action="/workflow/tasks/<?= (int)$task['id'] ?>/decide" method="POST" id="decision-form">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="decision" id="decision-input">
                <input type="hidden" name="signature_id" id="signature-id-input">

                <?php if ($task['step_type'] === 'signature'): ?>
                    <div class="mb-3">
                        <label class="form-label text-soft small">Υπογραφή Σχεδιαστή (Capture)</label>
                        <div class="p-2 rounded text-center border mb-2 position-relative" style="background: var(--color-bg); border-color: var(--color-border) !important;">
                            <canvas id="sig-canvas" width="300" height="150" style="background:var(--color-surface); border:1px solid var(--color-border); cursor:crosshair;"></canvas>
                            <div class="mt-2">
                                <button type="button" class="btn btn-xs btn-outline-secondary" id="clear-sig-btn">Καθαρισμός</button>
                                <button type="button" class="btn btn-xs btn-outline-info" id="undo-sig-btn">Αναίρεση</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="comment" class="form-label text-soft small">Σχόλιο / Παρατηρήσεις</label>
                    <textarea class="form-control" id="comment" name="comment" rows="3" placeholder="Εισάγετε σχόλια (Υποχρεωτικό για Απόρριψη/Επιστροφή)"></textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" id="btn-approve">
                        <i class="fa-solid fa-circle-check me-1"></i> Έγκριση
                    </button>
                    
                    <?php if ($task['allow_return']): ?>
                        <button type="button" class="btn btn-warning text-dark" id="btn-return">
                            <i class="fa-solid fa-arrow-rotate-left me-1"></i> Επιστροφή για Διορθώσεις
                        </button>
                    <?php endif; ?>

                    <?php if ($task['allow_reject']): ?>
                        <button type="button" class="btn btn-danger" id="btn-reject">
                            <i class="fa-solid fa-circle-xmark me-1"></i> Απόρριψη
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Administrator Controls Panel -->
        <?php if (\App\Core\Auth::role() === 'administrator'): ?>
            <div class="card p-4 mb-4 border border-warning">
                <h5 class="text-warning mb-3"><i class="fa-solid fa-user-shield me-2"></i> Διαχειριστικές Ενέργειες</h5>
                
                <?php if ($task['status'] === 'active'): ?>
                    <button type="button" class="btn btn-outline-warning w-100 mb-2" id="btn-trigger-reassign-modal">
                        <i class="fa-solid fa-user-pen me-1"></i> Επανανάθεση Εργασίας
                    </button>
                <?php endif; ?>

                <button type="button" class="btn btn-outline-danger w-100" id="btn-trigger-cancel-modal">
                    <i class="fa-solid fa-ban me-1"></i> Ακύρωση Workflow
                </button>
            </div>

            <!-- Reassignment Custom Modal -->
            <div id="reassign-modal" class="app-modal d-none" role="dialog" aria-modal="true" style="z-index: 1050; position: fixed; inset: 0; background: rgba(0,0,0,0.7); align-items: center; justify-content: center;">
                <div class="app-modal-dialog border border-warning rounded p-4" style="max-width: 500px; margin: 10% auto; background: var(--color-surface);">
                    <div class="app-modal-header mb-3">
                        <h4 class="text-warning m-0"><i class="fa-solid fa-user-pen me-2"></i> Επανανάθεση Εργασίας</h4>
                    </div>
                    <form action="/workflow/tasks/<?= (int)$task['id'] ?>/reassign" method="POST">
                        <?= \App\Core\Csrf::field() ?>
                        <div class="mb-3">
                            <label for="new_assigned_user_id" class="form-label text-soft small">Επιλογή Νέου Χρήστη</label>
                            <select class="form-select form-select-sm" name="new_assigned_user_id" required>
                                <option value="">-- Επιλέξτε Χρήστη --</option>
                                <?php
                                $db = \App\Core\Database::getInstance();
                                $stmtUsers = $db->query("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name ASC");
                                foreach ($stmtUsers->fetchAll() as $u):
                                ?>
                                    <option value="<?= (int)$u['id'] ?>"><?= \App\Core\View::escape($u['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reassign_reason" class="form-label text-soft small">Αιτιολογία Επανανάθεσης</label>
                            <textarea class="form-control form-control-sm" name="reason" rows="3" required placeholder="Εισάγετε τον λόγο της επανανάθεσης..."></textarea>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" id="btn-close-reassign">Ακύρωση</button>
                            <button type="submit" class="btn btn-warning">Επανανάθεση</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cancellation Custom Modal -->
            <div id="cancel-wf-modal" class="app-modal d-none" role="dialog" aria-modal="true" style="z-index: 1050; position: fixed; inset: 0; background: rgba(0,0,0,0.7); align-items: center; justify-content: center;">
                <div class="app-modal-dialog border border-danger rounded p-4" style="max-width: 500px; margin: 10% auto; background: var(--color-surface);">
                    <div class="app-modal-header mb-3">
                        <h4 class="text-danger m-0"><i class="fa-solid fa-ban me-2"></i> Ακύρωση Workflow</h4>
                    </div>
                    <form action="/workflows/instances/<?= (int)$task['workflow_instance_id'] ?>/cancel" method="POST">
                        <?= \App\Core\Csrf::field() ?>
                        <div class="mb-3">
                            <label for="cancel_reason" class="form-label text-soft small">Αιτιολογία Ακύρωσης (Υποχρεωτική)</label>
                            <textarea class="form-control form-control-sm" name="reason" rows="3" required placeholder="Εισάγετε τον λόγο ακύρωσης του workflow..."></textarea>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" id="btn-close-cancel-wf">Κλείσιμο</button>
                            <button type="submit" class="btn btn-danger">Ακύρωση Workflow</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Comments Thread Panel -->
        <div class="card p-4">
            <h5 class="text-soft mb-3"><i class="fa-solid fa-comments text-primary me-2"></i> Σχόλια & Συζήτηση</h5>
            <div class="comments-list mb-3" style="max-height: 300px; overflow-y: auto;">
                <?php if (empty($comments)): ?>
                    <p class="text-muted small text-center m-0">Δεν υπάρχουν σχόλια.</p>
                <?php else: ?>
                    <?php foreach ($comments as $c): ?>
                        <div class="p-2 rounded mb-2 border" style="background: var(--color-bg); border-color: var(--color-border) !important;">
                            <div class="d-flex justify-content-between mb-1" style="font-size:0.75rem;">
                                <span class="text-primary font-weight-bold"><?= \App\Core\View::escape($c['user_name']) ?></span>
                                <span class="text-muted"><?= $c['created_at'] ?></span>
                            </div>
                            <p class="m-0 text-soft" style="font-size:0.8rem;"><?= nl2br(\App\Core\View::escape($c['comment'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const canvas = document.getElementById('sig-canvas');
    let ctx = null;
    let isDrawing = false;
    let paths = [];
    let currentPath = [];

    if (canvas) {
        ctx = canvas.getContext('2d');
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';

        function getMousePos(e) {
            const rect = canvas.getBoundingClientRect();
            return {
                x: (e.clientX || e.touches[0].clientX) - rect.left,
                y: (e.clientY || e.touches[0].clientY) - rect.top
            };
        }

        function startDrawing(e) {
            isDrawing = true;
            currentPath = [];
            const pos = getMousePos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
            currentPath.push(pos);
        }

        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            const pos = getMousePos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            currentPath.push(pos);
        }

        function stopDrawing() {
            if (isDrawing) {
                isDrawing = false;
                paths.push(currentPath);
            }
        }

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        window.addEventListener('mouseup', stopDrawing);

        canvas.addEventListener('touchstart', startDrawing);
        canvas.addEventListener('touchmove', draw);
        window.addEventListener('touchend', stopDrawing);

        document.getElementById('clear-sig-btn').addEventListener('click', function() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            paths = [];
        });

        document.getElementById('undo-sig-btn').addEventListener('click', function() {
            if (paths.length > 0) {
                paths.pop();
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                paths.forEach(p => {
                    if (p.length === 0) return;
                    ctx.beginPath();
                    ctx.moveTo(p[0].x, p[0].y);
                    for (let i = 1; i < p.length; i++) {
                        ctx.lineTo(p[i].x, p[i].y);
                    }
                    ctx.stroke();
                });
            }
        });
    }

    const decisionInput = document.getElementById('decision-input');
    const signatureIdInput = document.getElementById('signature-id-input');
    const form = document.getElementById('decision-form');
    const commentBox = document.getElementById('comment');

    document.getElementById('btn-approve').addEventListener('click', function() {
        if (canvas) {
            const dataUrl = canvas.toDataURL('image/png');
            const isBlank = paths.length === 0;
            if (isBlank) {
                alert('Παρακαλώ σχεδιάστε την υπογραφή σας.');
                return;
            }
            
            fetch('/documents/<?= (int)$task['document_instance_id'] ?>/signature', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= \App\Core\Csrf::token() ?>'
                },
                body: JSON.stringify({
                    field_key: '<?= \App\Core\View::escape($task['signature_field_key']) ?>',
                    page: 1,
                    signature_data: dataUrl
                })
            }).then(r => r.json()).then(res => {
                if (res.success) {
                    signatureIdInput.value = res.signature_id;
                    submitDecision('approved');
                } else {
                    alert('Σφάλμα αποθήκευσης υπογραφής: ' + res.message);
                }
            }).catch(e => {
                alert('Σφάλμα επικοινωνίας: ' + e.message);
            });
        } else {
            submitDecision('approved');
        }
    });

    if (document.getElementById('btn-return')) {
        document.getElementById('btn-return').addEventListener('click', function() {
            if (commentBox.value.trim() === '') {
                alert('Το σχόλιο είναι υποχρεωτικό για επιστροφή.');
                return;
            }
            submitDecision('returned');
        });
    }

    if (document.getElementById('btn-reject')) {
        document.getElementById('btn-reject').addEventListener('click', function() {
            if (commentBox.value.trim() === '') {
                alert('Το σχόλιο είναι υποχρεωτικό για απόρριψη.');
                return;
            }
            submitDecision('rejected');
        });
    }

    function submitDecision(dec) {
        decisionInput.value = dec;
        form.submit();
    }

    // Modal Triggers
    const reassignModal = document.getElementById('reassign-modal');
    const cancelModal = document.getElementById('cancel-wf-modal');

    if (document.getElementById('btn-trigger-reassign-modal')) {
        document.getElementById('btn-trigger-reassign-modal').addEventListener('click', function() {
            reassignModal.classList.remove('d-none');
            reassignModal.style.display = 'flex';
        });
    }

    if (document.getElementById('btn-close-reassign')) {
        document.getElementById('btn-close-reassign').addEventListener('click', function() {
            reassignModal.classList.add('d-none');
            reassignModal.style.display = 'none';
        });
    }

    if (document.getElementById('btn-trigger-cancel-modal')) {
        document.getElementById('btn-trigger-cancel-modal').addEventListener('click', function() {
            cancelModal.classList.remove('d-none');
            cancelModal.style.display = 'flex';
        });
    }

    if (document.getElementById('btn-close-cancel-wf')) {
        document.getElementById('btn-close-cancel-wf').addEventListener('click', function() {
            cancelModal.classList.add('d-none');
            cancelModal.style.display = 'none';
        });
    }
});
</script>
