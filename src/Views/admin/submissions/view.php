<div class="mb-4">
    <a href="/admin/submissions" class="btn btn-outline-secondary btn-sm mb-3"><i class="fa-solid fa-arrow-left"></i> Πίσω στις Υποβολές</a>
    <h3 class="font-heading text-white">Αξιολόγηση Υποβολής #<?= $submission['id'] ?></h3>
    <small class="text-muted">Υποβλήθηκε από: <strong><?= htmlspecialchars($submission['submitter_name']) ?></strong> | Φόρμα: <?= htmlspecialchars($submission['form_title']) ?></small>
</div>

<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger"><?= \App\Core\View::escape($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-7">
        <div class="glass-panel p-4 mb-4">
            <h5 class="font-heading mb-4 text-white">Απαντήσεις</h5>

            <?php foreach ($schema['sections'] as $sec): ?>
                <div class="mb-4 border-bottom border-glass pb-3">
                    <h6 class="text-info font-heading mb-3"><?= \App\Core\View::escape($sec['title']) ?></h6>
                    
                    <?php if (isset($sec['fields'])): ?>
                        <?php foreach ($sec['fields'] as $f): ?>
                            <?php if (in_array($f['type'], ['heading', 'divider'])) continue; ?>
                            
                            <div class="mb-3">
                                <label class="text-muted small d-block"><?= \App\Core\View::escape($f['label']) ?></label>
                                <div class="text-white fw-bold" style="overflow-wrap: anywhere; word-break: break-word; white-space: pre-wrap; max-width: 100%;">
                                    <?php 
                                    $ans = $answers[$f['key']] ?? null;
                                    if ($f['type'] === 'file' && !empty($ans)):
                                        $db = \App\Core\Database::getInstance();
                                        $fileRow = $db->prepare("SELECT id, original_name FROM submission_files WHERE submission_id = ? AND field_key = ?");
                                        $fileRow->execute([$submission['id'], $f['key']]);
                                        $fData = $fileRow->fetch();
                                    ?>
                                        <?php if ($fData): ?>
                                            <a href="/my-submissions/<?= $submission['uuid'] ?>/files/<?= $fData['id'] ?>/download" class="btn btn-outline-primary btn-sm mt-1">
                                                <i class="fa-solid fa-file-arrow-down me-1"></i> Κατέβασμα: <?= htmlspecialchars($fData['original_name']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Δεν ανέβηκε αρχείο</span>
                                        <?php endif; ?>
                                    <?php elseif ($f['type'] === 'password'): ?>
                                        <span class="text-muted">[PROTECTED PASSWORD]</span>
                                    <?php elseif ($f['type'] === 'address' && is_array($ans)): ?>
                                        <div class="small">
                                            <?= \App\Core\View::escape($ans['line1'] ?? '') ?><br>
                                            <?= \App\Core\View::escape($ans['line2'] ?? '') ?><br>
                                            <?= \App\Core\View::escape($ans['city'] ?? '') ?>, <?= \App\Core\View::escape($ans['postal_code'] ?? '') ?>
                                        </div>
                                    <?php elseif ($f['type'] === 'rating'): ?>
                                        <span><?= \App\Core\View::escape($ans) ?> / <?= $f['ratingMax'] ?? 5 ?> ★</span>
                                    <?php elseif ($f['type'] === 'nps'): ?>
                                        <span><?= \App\Core\View::escape($ans) ?> / 10 (Net Promoter Score)</span>
                                    <?php elseif ($f['type'] === 'repeater' && is_array($ans)): ?>
                                        <div class="border border-glass rounded p-2">
                                            <?php foreach ($ans as $idx => $row): ?>
                                                <div class="small mb-1">
                                                    <strong>#<?= $idx + 1 ?>:</strong> <?= htmlspecialchars(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php elseif ($f['type'] === 'likert' && is_array($ans)): ?>
                                        <div class="small">
                                            <?php foreach ($ans as $q => $score): ?>
                                                <div><strong><?= htmlspecialchars($q) ?>:</strong> <?= htmlspecialchars($score) ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php elseif ($f['type'] === 'consent'): ?>
                                        <span><?= $ans ? 'Ναι (Συγκατάθεση)' : 'Όχι' ?></span>
                                    <?php else: ?>
                                        <?= is_array($ans) ? \App\Core\View::escape(json_encode($ans, JSON_UNESCAPED_UNICODE)) : \App\Core\View::escape($ans ?? '—') ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Review Controls Column -->
    <div class="col-md-5">
        <!-- Current status card -->
        <div class="glass-panel p-4 mb-4">
            <h5 class="font-heading mb-3 text-white">Κατάσταση: <?= strtoupper($submission['status']) ?></h5>
            
            <?php if ($submission['status'] === 'submitted'): ?>
                <form action="/admin/submissions/<?= $submission['uuid'] ?>/start-review" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="btn btn-primary w-100">Έναρξη Αξιολόγησης (Start Review) <i class="fa-solid fa-play ms-1"></i></button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($submission['status'] === 'under_review'): ?>
            <div class="glass-panel p-4 mb-4">
                <h5 class="font-heading mb-4 text-white">Απόφαση Αξιολόγησης</h5>
                <form id="reviewActionForm" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    
                    <div class="mb-3">
                        <label for="review_notes" class="form-label">Σχόλια / Παρατηρήσεις</label>
                        <textarea class="form-control" id="review_notes" name="review_notes" rows="4" placeholder="Σημειώσεις..."></textarea>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <button type="submit" onclick="submitReview(event, '/admin/submissions/<?= $submission['uuid'] ?>/approve')" class="btn btn-success"><i class="fa-solid fa-check me-2"></i> Έγκριση (Approve)</button>
                        <button type="submit" onclick="submitReview(event, '/admin/submissions/<?= $submission['uuid'] ?>/reject')" class="btn btn-danger"><i class="fa-solid fa-xmark me-2"></i> Απόρριψη (Reject)</button>
                        <button type="submit" onclick="submitReview(event, '/admin/submissions/<?= $submission['uuid'] ?>/return')" class="btn btn-warning"><i class="fa-solid fa-undo me-2"></i> Επιστροφή για Διορθώσεις (Return)</button>
                        <button type="submit" onclick="submitReview(event, '/admin/submissions/<?= $submission['uuid'] ?>/return-to-draft')" class="btn btn-outline-warning"><i class="fa-solid fa-rotate-left me-2"></i> Επιστροφή σε Πρόχειρο (Draft)</button>
                    </div>

                </form>
            </div>
        <?php endif; ?>

        <!-- History card -->
        <div class="glass-panel p-4">
            <h6 class="text-white mb-3 border-bottom border-glass pb-2">Ιστορικό Κατάστασης</h6>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($history as $h): ?>
                    <div class="small">
                        <div class="d-flex justify-content-between text-muted">
                            <span>v<?= strtoupper($h['old_status']) ?> &rarr; v<?= strtoupper($h['new_status']) ?></span>
                            <span><?= date('d/m H:i', strtotime($h['created_at'])) ?></span>
                        </div>
                        <?php if ($h['notes']): ?>
                            <div class="text-white mt-1 italic">"<?= htmlspecialchars($h['notes']) ?>"</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function submitReview(event, actionUrl) {
    const form = document.getElementById('reviewActionForm');
    const notes = document.getElementById('review_notes').value.trim();
    
    // Require notes for reject or return
    if ((actionUrl.includes('reject') || actionUrl.includes('return')) && notes === '') {
        alert('Τα σχόλια είναι υποχρεωτικά για απόρριψη ή επιστροφή.');
        event.preventDefault();
        return;
    }

    form.action = actionUrl;
}
</script>

