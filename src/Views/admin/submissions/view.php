<div class="mb-4">
    <a href="/admin/submissions" class="btn btn-outline-secondary btn-sm mb-3"><i class="fa-solid fa-arrow-left"></i> <?= __('Back') ?></a>
    <h3 class="font-heading text-white"><?= __('Review Submission') ?> #<?= $submission['id'] ?></h3>
    <small class="text-muted"><?= __('Submitted by') ?>: <strong><?= htmlspecialchars($submission['submitter_name']) ?></strong> | <?= __('Form') ?>: <?= htmlspecialchars($submission['form_title']) ?></small>
</div>

<?php 
$hasDesign = \App\Models\PdfDesign::getByFormId((int)$submission['form_id']);
if ($hasDesign): 
?>
    <div class="mb-4 d-flex gap-2 flex-wrap">
        <a href="/documents/submissions/<?= (int)$submission['id'] ?>/pdf" target="_blank" class="btn btn-outline-danger btn-sm">
            <i class="fa-solid fa-eye me-1"></i> <?= __('View PDF') ?>
        </a>
        <a href="/documents/submissions/<?= (int)$submission['id'] ?>/pdf?mode=download" class="btn btn-danger btn-sm">
            <i class="fa-solid fa-file-arrow-down me-1"></i> <?= __('Download PDF') ?>
        </a>
    </div>
<?php endif; ?>


<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger"><?= \App\Core\View::escape($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-7">
        <div class="glass-panel p-4 mb-4">
            <h5 class="font-heading mb-4 text-white"><?= __('Submission Values') ?></h5>

            <?php foreach ($schema['sections'] as $sec): ?>
                <?php
                $submittedFields = array_values(array_filter($sec['fields'] ?? [], function ($field) use ($answers) {
                    if (in_array($field['type'] ?? '', ['heading', 'divider', 'page_break', 'html'], true)) return false;
                    $key = $field['key'] ?? '';
                    return $key !== ''
                        && array_key_exists($key, $answers)
                        && \App\Services\NotificationTemplateService::hasMeaningfulValue($answers[$key]);
                }));
                if (empty($submittedFields)) continue;
                ?>
                <div class="mb-4 border-bottom border-glass pb-3">
                    <h6 class="text-info font-heading mb-3"><?= \App\Core\View::escape($sec['title']) ?></h6>
                    
                    <?php if (isset($sec['fields'])): ?>
                        <?php foreach ($submittedFields as $f): ?>
                            
                            <div class="mb-3">
                                <label class="text-muted small d-block"><?= \App\Core\View::escape($f['label']) ?></label>
                                <div class="text-white fw-bold" style="overflow-wrap: anywhere; word-break: break-word; white-space: pre-line; max-width: 100%;">
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
                                                <i class="fa-solid fa-file-arrow-down me-1"></i> <?= __('Download') ?>: <?= htmlspecialchars($fData['original_name']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted"><?= __('No file uploaded') ?></span>
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
                                        <span><?= $ans ? __('Yes (Consent given)') : __('No') ?></span>
                                    <?php else: ?>
                                        <?= \App\Core\View::escape(\App\Services\NotificationTemplateService::formatSubmittedValue($ans, $f)) ?>
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
            <h5 class="font-heading mb-3 text-white"><?= __('Status') ?>: <?= __($submission['status']) ?></h5>

            <?php if (
                $submission['status'] === 'draft'
                && (int)$submission['user_id'] === (int)\App\Core\Auth::id()
                && !empty($submission['form_slug'])
            ): ?>
                <a href="/forms/<?= rawurlencode($submission['form_slug']) ?>" class="btn btn-warning w-100">
                    <i class="fa-solid fa-pen-to-square me-2"></i> Επεξεργασία και Υποβολή Προσχεδίου
                </a>
                <div class="text-muted small mt-2">
                    Το προσχέδιο θα ανοίξει με τα αποθηκευμένα στοιχεία του.
                </div>
            <?php endif; ?>
            
            <?php if ($submission['status'] === 'submitted'): ?>
                <form action="/admin/submissions/<?= $submission['uuid'] ?>/start-review" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="btn btn-primary w-100"><?= __('Start Review') ?> <i class="fa-solid fa-play ms-1"></i></button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (($correctionRequest['status'] ?? '') === 'pending'): ?>
            <div class="glass-panel p-4 mb-4 border border-warning">
                <h5 class="font-heading text-warning mb-3"><i class="fa-solid fa-user-pen me-2"></i>Αίτημα διόρθωσης χρήστη</h5>
                <div class="small text-muted mb-2">Από: <?= \App\Core\View::escape($correctionRequest['requester_name'] ?: $submission['submitter_name']) ?></div>
                <div class="text-white p-3 bg-dark bg-opacity-25 rounded mb-3" style="white-space: pre-wrap;"><?= \App\Core\View::escape($correctionRequest['reason']) ?></div>
                <form method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <textarea name="reviewer_notes" class="form-control mb-3" rows="3" maxlength="2000" placeholder="Σχόλιο προς τον χρήστη (προαιρετικό)"></textarea>
                    <div class="d-flex gap-2">
                        <button formaction="/admin/submissions/<?= rawurlencode($submission['uuid']) ?>/correction-request/<?= (int)$correctionRequest['id'] ?>/approve" class="btn btn-success flex-fill" type="submit">
                            <i class="fa-solid fa-check me-1"></i> Έγκριση και άνοιγμα
                        </button>
                        <button formaction="/admin/submissions/<?= rawurlencode($submission['uuid']) ?>/correction-request/<?= (int)$correctionRequest['id'] ?>/reject" class="btn btn-danger flex-fill" type="submit">
                            <i class="fa-solid fa-xmark me-1"></i> Απόρριψη
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($submission['status'] === 'under_review'): ?>
            <div class="glass-panel p-4 mb-4">
                <h5 class="font-heading mb-4 text-white"><?= __('Make Decision') ?></h5>
                <form id="reviewActionForm" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    
                    <div class="mb-3">
                        <label for="review_notes" class="form-label"><?= __('Comment / Notes') ?></label>
                        <textarea class="form-control" id="review_notes" name="review_notes" rows="4" placeholder="<?= __('Notes') ?>..."></textarea>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <button type="submit" onclick="submitReview(event, '/admin/submissions/<?= $submission['uuid'] ?>/approve')" class="btn btn-success"><i class="fa-solid fa-check me-2"></i> <?= __('Approve') ?></button>
                        <button type="submit" onclick="submitReview(event, '/admin/submissions/<?= $submission['uuid'] ?>/reject')" class="btn btn-danger"><i class="fa-solid fa-xmark me-2"></i> <?= __('Reject') ?></button>
                        <button type="submit" onclick="submitReview(event, '/admin/submissions/<?= $submission['uuid'] ?>/return')" class="btn btn-warning"><i class="fa-solid fa-undo me-2"></i> <?= __('Return for Corrections') ?></button>
                        <button type="submit" onclick="submitReview(event, '/admin/submissions/<?= $submission['uuid'] ?>/return-to-draft')" class="btn btn-outline-warning"><i class="fa-solid fa-rotate-left me-2"></i> <?= __('Return') ?> (Draft)</button>
                    </div>

                </form>
            </div>
        <?php endif; ?>

        <!-- History card -->
        <div class="glass-panel p-4">
            <h6 class="text-white mb-3 border-bottom border-glass pb-2"><?= __('Approval History') ?></h6>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($history as $h): ?>
                    <?php
                    $historyActor = trim((string)($h['user_fullname'] ?? ''));
                    if ($historyActor === '') $historyActor = trim((string)($h['user_name'] ?? ''));
                    if ($historyActor === '') $historyActor = __('System');
                    ?>
                    <div class="small">
                        <div class="d-flex justify-content-between text-muted">
                            <span>v<?= strtoupper($h['old_status']) ?> &rarr; v<?= strtoupper($h['new_status']) ?></span>
                            <span><?= date('d/m H:i', strtotime($h['created_at'])) ?></span>
                        </div>
                        <div class="text-info mt-1">
                            <i class="fa-solid fa-user-check me-1" aria-hidden="true"></i>
                            <?= __('By') ?>: <?= \App\Core\View::escape($historyActor) ?>
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
        alert('<?= __('Enter comments (Required for Rejection/Return)') ?>');
        event.preventDefault();
        return;
    }

    form.action = actionUrl;
}
</script>
