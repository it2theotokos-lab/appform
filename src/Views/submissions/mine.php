<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="header-page-title mb-0"><?= __('My Form Submissions') ?></h1>
</div>

<?php if ($success = \App\Core\Session::flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i>
        <?= \App\Core\View::escape($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php
$filtersConfig = [
    ['name' => 'search', 'label' => __('Search'), 'type' => 'text', 'placeholder' => __('Form Title...'), 'col' => 'col-md-6'],
    ['name' => 'status', 'label' => __('Status'), 'type' => 'select', 'options' => [
        '' => __('All'),
        'draft' => __('Draft'),
        'submitted' => __('Submitted'),
        'under_review' => __('In Review'),
        'returned' => __('Returned for Correction'),
        'approved' => __('Approved'),
        'rejected' => __('Rejected')
    ], 'col' => 'col-md-4']
];

include __DIR__ . '/../shared/filter_bar.php';
?>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= __('Form') ?></th>
                    <th><?= __('Created Date') ?></th>
                    <th><?= __('Submission Date') ?></th>
                    <th><?= __('Status') ?></th>
                    <th class="text-end"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4"><?= __('No form submissions found.') ?></td></tr>
                <?php else: ?>
                    <?php foreach ($submissions as $sub): ?>
                        <tr>
                            <td>#<?= $sub['id'] ?></td>
                            <td><strong><?= \App\Core\View::escape($sub['form_title']) ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($sub['created_at'])) ?></td>
                            <td><?= $sub['submitted_at'] ? date('d/m/Y H:i', strtotime($sub['submitted_at'])) : '-' ?></td>
                            <td>
                                <?php
                                $badgeClass = 'badge-status-draft';
                                $statusLabel = strtoupper($sub['status']);
                                if ($sub['status'] === 'approved') { $badgeClass = 'badge-status-approved'; $statusLabel = __('APPROVED'); }
                                elseif ($sub['status'] === 'rejected') { $badgeClass = 'badge-status-rejected'; $statusLabel = __('REJECTED'); }
                                elseif ($sub['status'] === 'under_review') { $badgeClass = 'badge-status-pending'; $statusLabel = __('IN REVIEW'); }
                                elseif ($sub['status'] === 'returned') { $badgeClass = 'badge-status-pending'; $statusLabel = __('RETURNED'); }
                                elseif ($sub['status'] === 'submitted') { $badgeClass = 'badge-status-pending'; $statusLabel = __('SUBMITTED'); }
                                elseif ($sub['status'] === 'draft') { $badgeClass = 'badge-status-draft'; $statusLabel = __('DRAFT'); }
                                ?>
                                <span class="<?= $badgeClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2 flex-wrap">
                                    <a href="/my-submissions/<?= $sub['uuid'] ?>" class="btn btn-premium btn-sm" title="<?= __('View') ?>">
                                        <i class="fa-solid fa-eye me-1" aria-hidden="true"></i> <?= __('View') ?>
                                    </a>

                                    <?php if ($sub['status'] === 'draft' || $sub['status'] === 'returned'): ?>
                                        <a href="/forms/<?= htmlspecialchars($sub['form_slug']) ?>" class="btn btn-warning btn-sm" title="<?= __('Edit') ?>">
                                            <i class="fa-solid fa-edit me-1" aria-hidden="true"></i> <?= __('Edit') ?>
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
