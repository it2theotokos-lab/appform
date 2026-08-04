<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="h3 m-0 text-soft"><i class="fa-solid fa-diagram-project me-2 text-primary"></i> <?= __('Workflow Definitions') ?></h1>
        <p class="text-muted m-0"><?= __('Manage workflow definitions for approvals and signatures.') ?></p>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/workflows/create" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> <?= __('Create Workflow') ?>
        </a>
    </div>
</div>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th><?= __('Name') ?></th>
                    <th><?= __('Description') ?></th>
                    <th><?= __('Document Template') ?></th>
                    <th><?= __('Status') ?></th>
                    <th><?= __('Created by') ?></th>
                    <th><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($workflows)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4"><?= __('No workflow definitions found.') ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($workflows as $wf): ?>
                        <tr>
                            <td class="align-middle font-weight-bold text-soft"><?= \App\Core\View::escape($wf['name']) ?></td>
                            <td class="align-middle text-muted"><?= \App\Core\View::escape($wf['description'] ?? '-') ?></td>
                            <td class="align-middle text-soft"><?= \App\Core\View::escape($wf['template_title'] ?? __('None')) ?></td>
                            <td class="align-middle">
                                <?php if ($wf['is_active']): ?>
                                    <span class="badge bg-success"><?= __('Active') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= __('Inactive') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle text-muted"><?= \App\Core\View::escape($wf['creator_name']) ?></td>
                            <td class="align-middle">
                                <a href="/admin/workflows/<?= (int)$wf['id'] ?>/edit" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="fa-solid fa-edit me-1"></i> <?= __('Design PDF') ?>
                                </a>
                                <?php if ($wf['is_active']): ?>
                                    <form action="/admin/workflows/<?= (int)$wf['id'] ?>/archive" method="POST" class="d-inline js-confirm-action" data-confirm-title="<?= __('Deactivate') ?>" data-confirm-text="<?= __('Do you want to change this user\'s status?') ?>">
                                        <?= \App\Core\Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fa-solid fa-archive me-1"></i> <?= __('Deactivate') ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="/admin/workflows/<?= (int)$wf['id'] ?>/publish" method="POST" class="d-inline js-confirm-action" data-confirm-title="<?= __('Activate') ?>" data-confirm-text="<?= __('Do you want to change this user\'s status?') ?>">
                                        <?= \App\Core\Csrf::field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="fa-solid fa-circle-check me-1"></i> <?= __('Activate') ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
