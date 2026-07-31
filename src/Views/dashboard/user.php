<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon stat-primary">
                <i class="fa-solid fa-file-signature" aria-hidden="true"></i>
            </div>
            <div class="stat-card-info">
                <div class="stat-card-label"><?= __('Available Forms') ?></div>
                <div class="stat-card-value"><?= $formsCount ?? 0 ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon stat-info">
                <i class="fa-solid fa-file-pen" aria-hidden="true"></i>
            </div>
            <div class="stat-card-info">
                <div class="stat-card-label"><?= __('Draft Submissions') ?></div>
                <div class="stat-card-value"><?= $draftsCount ?? 0 ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon stat-success">
                <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
            </div>
            <div class="stat-card-info">
                <div class="stat-card-label"><?= __('Submitted Forms') ?></div>
                <div class="stat-card-value"><?= $submittedCount ?? 0 ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon stat-warning">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            </div>
            <div class="stat-card-info">
                <div class="stat-card-label"><?= __('Returned for Correction') ?></div>
                <div class="stat-card-value"><?= $returnedCount ?? 0 ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0"><i class="fa-solid fa-list-check me-2 text-primary"></i> <?= __('Available Forms for Submission') ?></h5>
            </div>
            <?php if (empty($availableForms)): ?>
                <p class="text-muted mb-0"><?= __('No forms available at this time.') ?></p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($availableForms as $form): ?>
                        <div class="col-md-4">
                            <div class="border rounded p-3 d-flex flex-column justify-content-between h-100 bg-surface">
                                <div>
                                    <h6 class="font-bold text-soft mb-1"><?= \App\Core\View::escape($form['title']) ?></h6>
                                    <p class="text-muted small mb-3"><?= \App\Core\View::escape($form['description'] ?? __('No description')) ?></p>
                                </div>
                                <a href="/forms/<?= htmlspecialchars($form['slug']) ?>" class="btn btn-premium btn-sm w-100">
                                    <?= __('Fill Form') ?> <i class="fa-solid fa-pen-to-square ms-1"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="card-title mb-0"><i class="fa-solid fa-clock-rotate-left me-2 text-primary" aria-hidden="true"></i> <?= __('My Recent Form Submissions') ?></h2>
        <a href="/my-submissions" class="btn btn-premium btn-sm"><?= __('View All Submissions') ?></a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th><?= __('Form') ?></th>
                    <th><?= __('Submission Date') ?></th>
                    <th><?= __('Status') ?></th>
                    <th class="text-end"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mySubmissions)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4"><?= __('You have not made any submissions yet.') ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach (array_slice($mySubmissions, 0, 5) as $sub): ?>
                        <tr>
                            <td><strong><?= \App\Core\View::escape($sub['form_title']) ?></strong></td>
                            <td><?= $sub['submitted_at'] ? date('d/m/Y H:i', strtotime($sub['submitted_at'])) : date('d/m/Y H:i', strtotime($sub['created_at'])) ?></td>
                            <td>
                                <?php
                                $badgeClass = 'badge-status-draft';
                                $statusLabel = strtoupper(__($sub['status']));
                                if ($sub['status'] === 'approved') { $badgeClass = 'badge-status-approved'; }
                                elseif ($sub['status'] === 'rejected') { $badgeClass = 'badge-status-rejected'; }
                                elseif ($sub['status'] === 'under_review') { $badgeClass = 'badge-status-pending'; }
                                elseif ($sub['status'] === 'returned') { $badgeClass = 'badge-status-pending'; }
                                ?>
                                <span class="<?= $badgeClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="/my-submissions/<?= $sub['uuid'] ?>" class="btn btn-premium btn-sm">
                                        <i class="fa-solid fa-eye me-1"></i> <?= __('View') ?>
                                    </a>
                                    <?php if ($sub['status'] === 'draft' || $sub['status'] === 'returned'): ?>
                                        <a href="/forms/<?= htmlspecialchars($sub['form_slug'] ?? '') ?>" class="btn btn-warning btn-sm">
                                            <i class="fa-solid fa-edit me-1"></i> <?= __('Edit') ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
