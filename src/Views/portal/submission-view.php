<div class="mb-4">
    <a href="/my-submissions" class="btn btn-outline-secondary btn-sm mb-3"><i class="fa-solid fa-arrow-left"></i> Πίσω στις Υποβολές μου</a>
    <h3 class="font-heading text-white"><?= __('Submission') ?> #<?= $submission['id'] ?></h3>
    <small class="text-muted"><?= __('Form') ?>: <?= htmlspecialchars($submission['form_title']) ?> (v<?= $submission['form_version_id'] ?>)</small>
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


<div class="row g-4">
    <div class="col-md-8">
        <div class="glass-panel p-4 mb-4">
            <h5 class="font-heading mb-4 text-white"><?= __('Form Answers') ?></h5>

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
                                        // Retrieve specific file entity to link download
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

    <!-- Right Column: Status & History -->
    <div class="col-md-4">
        <div class="glass-panel p-4 mb-4">
            <h5 class="font-heading mb-4 text-white"><?= __('Submission Status') ?></h5>
            
            <div class="mb-3">
                <span class="badge bg-primary px-3 py-2 fs-6">
                    <?= htmlspecialchars(__($submission['status'])) ?>
                </span>
            </div>

            <?php if (
                in_array($submission['status'], ['draft', 'returned'], true)
                && (int)$submission['user_id'] === (int)\App\Core\Auth::id()
                && !empty($submission['form_slug'])
            ): ?>
                <a href="/forms/<?= rawurlencode($submission['form_slug']) ?>" class="btn btn-warning w-100 mb-3">
                    <i class="fa-solid fa-pen-to-square me-2"></i> Επεξεργασία και Υποβολή Προσχεδίου
                </a>
            <?php endif; ?>

            <?php if ($submission['review_notes']): ?>
                <div class="mb-4 p-3 bg-dark bg-opacity-50 rounded border border-glass">
                    <small class="text-muted d-block mb-1"><?= __('Review Notes') ?>:</small>
                    <p class="mb-0 text-white"><?= \App\Core\View::escape($submission['review_notes']) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($dailySubmissionEnabled) && in_array($submission['status'], ['submitted', 'correction_requested', 'under_review', 'approved', 'rejected'], true)): ?>
                <div class="mb-4 p-3 rounded border border-warning bg-warning bg-opacity-10">
                    <h6 class="text-warning"><i class="fa-solid fa-pen-to-square me-2"></i>Αίτημα διόρθωσης</h6>
                    <?php if (($correctionRequest['status'] ?? '') === 'pending'): ?>
                        <p class="small text-white mb-0">Το αίτημα έχει σταλεί και αναμένει απόφαση reviewer.</p>
                    <?php else: ?>
                        <form action="/my-submissions/<?= rawurlencode($submission['uuid']) ?>/correction-request" method="POST">
                            <?= \App\Core\Csrf::field() ?>
                            <textarea name="reason" class="form-control mb-2" rows="3" required maxlength="2000" placeholder="Τι χρειάζεται να διορθωθεί;"></textarea>
                            <button type="submit" class="btn btn-warning btn-sm w-100">Αποστολή αιτήματος στον reviewer</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <h6 class="text-white mt-4 mb-3 border-bottom border-glass pb-2"><?= __('Status History') ?></h6>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($history as $h): ?>
                    <?php
                    $historyActor = trim((string)($h['user_fullname'] ?? ''));
                    if ($historyActor === '') $historyActor = trim((string)($h['user_name'] ?? ''));
                    if ($historyActor === '') $historyActor = __('System');
                    ?>
                    <div class="small">
                        <div class="d-flex justify-content-between text-muted">
                            <span><?= htmlspecialchars(__($h['old_status'])) ?> &rarr; <?= htmlspecialchars(__($h['new_status'])) ?></span>
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
