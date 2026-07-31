<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon stat-primary">
                <i class="fa-solid fa-file-invoice" aria-hidden="true"></i>
            </div>
            <div class="stat-card-info">
                <div class="stat-card-label"><?= __('Total Forms') ?></div>
                <div class="stat-card-value"><?= $formsCount ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon stat-info">
                <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i>
            </div>
            <div class="stat-card-info">
                <div class="stat-card-label"><?= __('User Submissions') ?></div>
                <div class="stat-card-value"><?= $subsCount ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon stat-success">
                <i class="fa-solid fa-users" aria-hidden="true"></i>
            </div>
            <div class="stat-card-info">
                <div class="stat-card-label"><?= __('Registered Users') ?></div>
                <div class="stat-card-value"><?= $usersCount ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="card-title mb-0"><i class="fa-solid fa-clock-rotate-left me-2 text-primary" aria-hidden="true"></i> <?= __('Recent Submissions') ?></h2>
        <a href="/admin/submissions" class="btn btn-premium btn-sm"><?= __('View All') ?></a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= __('Form') ?></th>
                    <th><?= __('User') ?></th>
                    <th><?= __('Date') ?></th>
                    <th><?= __('Status') ?></th>
                    <th class="text-end"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($latestSubmissions)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4"><?= __('No recent submissions found.') ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($latestSubmissions as $sub): ?>
                        <tr>
                            <td>#<?= $sub['id'] ?></td>
                            <td><strong><?= htmlspecialchars($sub['form_title']) ?></strong></td>
                            <td><span class="badge-status-draft" style="color:var(--color-primary);"><?= htmlspecialchars($sub['username']) ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($sub['created_at'])) ?></td>
                            <td>
                                <?php
                                $badgeClass = 'badge-status-draft';
                                if ($sub['status'] === 'approved') $badgeClass = 'badge-status-approved';
                                elseif ($sub['status'] === 'rejected') $badgeClass = 'badge-status-rejected';
                                elseif ($sub['status'] === 'under_review') $badgeClass = 'badge-status-pending';
                                ?>
                                <span class="<?= $badgeClass ?>"><?= strtoupper(__($sub['status'])) ?></span>
                            </td>
                            <td class="text-end">
                                <a href="/admin/submissions/<?= $sub['id'] ?>" class="btn btn-premium btn-sm">
                                    <i class="fa-solid fa-eye me-1" aria-hidden="true"></i> <?= __('View') ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
