<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h1 class="h3 m-0 text-soft"><i class="fa-solid fa-square-check me-2 text-primary"></i> <?= __('Pending Approval Actions') ?></h1>
        <p class="text-muted m-0"><?= __('Select a task to review, approve or sign a document.') ?></p>
    </div>
</div>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th><?= __('Document Number') ?></th>
                    <th><?= __('Title') ?></th>
                    <th><?= __('Document Template') ?></th>
                    <th><?= __('Workflow Step') ?></th>
                    <th><?= __('Document Creator') ?></th>
                    <th><?= __('Task Status') ?></th>
                    <th><?= __('Assigned Date') ?></th>
                    <th><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4"><?= __('No pending approval tasks found.') ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td class="align-middle font-weight-bold text-soft"><?= \App\Core\View::escape($task['document_number']) ?></td>
                            <td class="align-middle text-soft"><?= \App\Core\View::escape($task['doc_title']) ?></td>
                            <td class="align-middle text-muted"><?= \App\Core\View::escape($task['template_title']) ?></td>
                            <td class="align-middle">
                                <?php
                                $stepName = $task['step_name'];
                                if ($stepName === 'Manager Review') {
                                    $stepName = __('Manager Review');
                                } elseif ($stepName === 'Director Signature') {
                                    $stepName = __('Director Signature');
                                }
                                ?>
                                <span class="badge bg-secondary"><?= \App\Core\View::escape($stepName) ?></span>
                            </td>
                            <td class="align-middle text-muted"><?= \App\Core\View::escape($task['creator_name']) ?></td>
                            <td class="align-middle">
                                <?php if ($task['status'] === 'active'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-spinner fa-spin me-1"></i> <?= __('Pending') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-dark"><?= \App\Core\View::escape($task['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle text-muted"><?= $task['created_at'] ?></td>
                            <td class="align-middle">
                                <a href="/workflow/tasks/<?= (int)$task['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fa-solid fa-square-check me-1"></i> <?= __('Review') ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
