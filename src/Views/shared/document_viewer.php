<?php
// Shared Enterprise Document Viewer layout structure
?>
<div class="row mb-4 align-items-center document-header-toolbar bg-dark p-3 rounded border border-secondary" style="margin-top: -15px;">
    <div class="col-md-6 d-flex align-items-center gap-3">
        <a href="<?= $backUrl ?? '/workflow/tasks' ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> <?= __('Back') ?>
        </a>
        <div>
            <h4 class="h5 m-0 text-white font-heading"><?= \App\Core\View::escape($docTitle ?? __('Document')) ?></h4>
            <small class="text-muted"><?= \App\Core\View::escape($docNumber ?? '') ?></small>
        </div>
    </div>
    <div class="col-md-6 text-end d-flex align-items-center justify-content-end gap-2">
        <?php if (!empty($docStatus)): ?>
            <?php
            $statusLabel = $docStatus;
            $statusBadgeClass = 'bg-secondary';
            if ($docStatus === 'active' || $docStatus === 'in_review') {
                $statusLabel = __('In Review');
                $statusBadgeClass = 'bg-warning text-dark';
            } elseif ($docStatus === 'approved' || $docStatus === 'finalized') {
                $statusLabel = __('Completed');
                $statusBadgeClass = 'bg-success';
            } elseif ($docStatus === 'returned' || $docStatus === 'returned_for_correction') {
                $statusLabel = __('Returned');
                $statusBadgeClass = 'bg-warning text-dark';
            } elseif ($docStatus === 'rejected') {
                $statusLabel = __('Rejected');
                $statusBadgeClass = 'bg-danger';
            } elseif ($docStatus === 'cancelled') {
                $statusLabel = __('Cancelled');
                $statusBadgeClass = 'bg-danger';
            }
            ?>
            <span class="badge <?= $statusBadgeClass ?> me-2"><i class="fa-solid fa-info-circle me-1"></i> <?= $statusLabel ?></span>
        <?php endif; ?>

        <a href="<?= $pdfUrl ?>" target="_blank" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-expand me-1"></i> <?= __('Open in new tab') ?>
        </a>
        <a href="<?= $pdfUrl ?>/download" class="btn btn-sm btn-outline-info">
            <i class="fa-solid fa-download me-1"></i> <?= __('Download') ?>
        </a>
    </div>
</div>

<div class="row">
    <!-- Main PDF Viewer container occupying maximized screen layout -->
    <div class="col-md-12">
        <div class="card p-2 bg-dark border-0 rounded shadow-lg overflow-hidden" style="height: calc(100vh - 180px); min-height: 500px;">
            <iframe src="<?= $pdfUrl ?>" width="100%" height="100%" style="border: none; background: var(--color-surface);" class="rounded"></iframe>
        </div>
    </div>
</div>
