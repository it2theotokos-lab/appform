<div class="row g-4 mb-4">
    <div class="col-md-5">
        <div class="card p-4">
            <h2 class="card-title mb-4"><i class="fa-solid fa-square-plus me-2 text-primary" aria-hidden="true"></i> <?= __('Create New Form') ?></h2>

            <?php if ($error = \App\Core\Session::flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
                    <?= \App\Core\View::escape($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="/admin/forms" method="POST">
                <?= \App\Core\Csrf::field() ?>

                <div class="mb-3">
                    <label for="title" class="form-label"><?= __('Form Title') ?></label>
                    <input type="text" class="form-control" id="title" name="title" required placeholder="<?= __('e.g. Equipment Damage Report') ?>">
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label"><?= __('Slug') ?></label>
                    <input type="text" class="form-control" id="slug" name="slug" required placeholder="<?= __('e.g. report-issue') ?>">
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label"><?= __('Description') ?></label>
                    <textarea class="form-control" id="description" name="description" rows="2" placeholder="<?= __('Short description...') ?>"></textarea>
                </div>

                <button type="submit" class="btn btn-premium w-100 py-2"><?= __('Create & Design') ?> <i class="fa-solid fa-hammer ms-2" aria-hidden="true"></i></button>
            </form>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card p-4">
            </div>

            <?php
            $filtersConfig = [
                ['name' => 'search', 'label' => __('Search'), 'type' => 'text', 'placeholder' => __('Title, slug...'), 'col' => 'col-md-6'],
                ['name' => 'status', 'label' => __('Status'), 'type' => 'select', 'options' => [
                    '' => __('All'),
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'archived' => 'Archived'
                ], 'col' => 'col-md-4']
            ];
            include __DIR__ . '/../shared/filter_bar.php';
            ?>

            <?php if ($success = \App\Core\Session::flash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i>
                    <?= \App\Core\View::escape($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th><?= __('Title') ?></th>
                            <th><?= __('Version') ?></th>
                            <th><?= __('Status') ?></th>
                            <th class="text-end"><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($forms)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4"><?= __('No forms found.') ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($forms as $f): ?>
                                <tr>
                                    <td>
                                        <strong class="text-soft"><?= \App\Core\View::escape($f['title']) ?></strong>
                                        <small class="text-muted d-block" style="font-size:0.75rem;">slug: <?= \App\Core\View::escape($f['slug']) ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary">v<?= $f['current_version'] ?></span></td>
                                    <td>
                                        <?php
                                        $badgeClass = 'badge-status-draft';
                                        if ($f['status'] === 'published') $badgeClass = 'badge-status-approved';
                                        elseif ($f['status'] === 'archived') $badgeClass = 'badge-status-rejected';
                                        ?>
                                        <span class="<?= $badgeClass ?>"><?= strtoupper($f['status']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                                            <a href="/admin/forms/<?= $f['id'] ?>/edit" class="btn btn-premium btn-sm" title="<?= __('Edit') ?>" aria-label="<?= __('Edit') ?>"><i class="fa-solid fa-gear" aria-hidden="true"></i></a>
                                            <a href="/admin/forms/<?= $f['id'] ?>/builder" class="btn btn-premium btn-sm" title="<?= __('Design') ?>" aria-label="<?= __('Design') ?>"><i class="fa-solid fa-crop" aria-hidden="true"></i></a>
                                            <a href="/admin/forms/<?= $f['id'] ?>/preview" target="_blank" class="btn btn-premium btn-sm" title="<?= __('Preview') ?>" aria-label="<?= __('Preview') ?>"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                            
                                            <?php if ($f['status'] === 'published'): ?>
                                                 <form action="/admin/forms/<?= $f['id'] ?>/archive" method="POST" class="js-confirm-action d-inline" data-confirm-title="<?= __('Archive form') ?>" data-confirm-message="<?= __('Form will no longer be available for new submissions. Continue?') ?>" data-confirm-button="<?= __('Archive') ?>" data-confirm-variant="warning">
                                                     <?= \App\Core\Csrf::field() ?>
                                                     <button type="submit" class="btn btn-premium btn-sm" title="<?= __('Archive') ?>" aria-label="<?= __('Archive') ?>"><i class="fa-solid fa-box-archive" aria-hidden="true"></i></button>
                                                 </form>
                                             <?php else: ?>
                                                 <form action="/admin/forms/<?= $f['id'] ?>/publish" method="POST" class="js-confirm-action d-inline" data-confirm-title="<?= __('Publish form') ?>" data-confirm-message="<?= __('A new version will be created after publishing. Continue?') ?>" data-confirm-button="<?= __('Publish') ?>" data-confirm-variant="success">
                                                     <?= \App\Core\Csrf::field() ?>
                                                     <button type="submit" class="btn btn-premium btn-sm" style="background:var(--gradient-success);" title="<?= __('Publish') ?>" aria-label="<?= __('Publish') ?>"><i class="fa-solid fa-upload" aria-hidden="true"></i></button>
                                                 </form>
                                             <?php endif; ?>

                                            <a href="/admin/submissions?form_id=<?= $f['id'] ?>" class="btn btn-premium btn-sm" title="<?= __('Submissions') ?>" aria-label="<?= __('Submissions') ?>"><i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i></a>
                                            <a href="/admin/analytics?form_id=<?= $f['id'] ?>" class="btn btn-premium btn-sm" title="<?= __('Statistics') ?>" aria-label="<?= __('Statistics') ?>"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></a>
                                            
                                            <form action="/admin/forms/<?= $f['id'] ?>/duplicate" method="POST" class="js-confirm-action d-inline" data-confirm-title="<?= __('Copy form') ?>" data-confirm-message="<?= __('Create a copy of this form?') ?>" data-confirm-button="<?= __('Copy') ?>" data-confirm-variant="info">
                                                <?= \App\Core\Csrf::field() ?>
                                                <button type="submit" class="btn btn-premium btn-sm" title="<?= __('Copy') ?>" aria-label="<?= __('Copy') ?>"><i class="fa-solid fa-copy" aria-hidden="true"></i></button>
                                            </form>

                                            <a href="/admin/forms/<?= $f['id'] ?>/export" class="btn btn-premium btn-sm" title="<?= __('Export') ?>" aria-label="<?= __('Export') ?>"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></a>

                                            <?php if (\App\Core\Auth::hasPermission('forms.delete') || \App\Core\Auth::role() === 'administrator'): ?>
                                                <form action="/admin/forms/<?= $f['id'] ?>/delete" method="POST" class="js-confirm-action d-inline" data-confirm-title="<?= __('Delete form') ?>" data-confirm-message="<?= __('Deletion is permanent and may affect existing data. Continue?') ?>" data-confirm-button="<?= __('Delete') ?>" data-confirm-variant="danger">
                                                    <?= \App\Core\Csrf::field() ?>
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <button type="submit" class="btn btn-premium btn-sm btn-delete" title="<?= __('Delete') ?>" aria-label="<?= __('Delete') ?>"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                                </form>
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
    </div>
</div>
