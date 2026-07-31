<?php
// View: Organizational Structure Tree
// Path: src/Views/users/organization.php
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <a href="/admin/users" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> <?= __('Back') ?></a>
        <h1 class="header-page-title mb-0"><i class="fa-solid fa-sitemap me-2"></i><?= __('Organizational Structure') ?></h1>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddDepartment">
            <i class="fa-solid fa-building me-2"></i><?= __('Add Department') ?>
        </button>
        <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddSubdepartment">
            <i class="fa-solid fa-diagram-project me-2"></i><?= __('Add Sub-department') ?>
        </button>
        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddTeam">
            <i class="fa-solid fa-people-group me-2"></i><?= __('Add Team') ?>
        </button>
    </div>
</div>

<?php if ($success = \App\Core\Session::flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= \App\Core\View::escape($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= \App\Core\View::escape($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($tree)): ?>
    <div class="card p-5 text-center text-muted">
        <i class="fa-solid fa-sitemap fa-3x mb-3 opacity-25"></i>
        <p class="mb-0"><?= __('No units defined yet.') ?></p>
        <p class="mt-2"><small><?= __('Start by adding a Department using the button above.') ?></small></p>
    </div>
<?php else: ?>
    <div class="org-tree">
        <?php foreach ($tree as $dept): ?>
            <div class="card mb-3 org-node org-dept">
                <!-- Department Header -->
                <div class="card-header d-flex justify-content-between align-items-center" style="background:rgba(var(--color-accent-rgb,108,99,255),0.15);border-bottom:1px solid rgba(255,255,255,0.08);">
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-primary rounded-pill"><i class="fa-solid fa-building me-1"></i><?= __('Department') ?></span>
                        <strong class="text-white fs-5"><?= \App\Core\View::escape($dept['name']) ?></strong>
                        <?php if (!empty($dept['users'])): ?>
                            <small class="text-muted">(<?= count($dept['users']) ?> <?= __('members') ?>)</small>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#modalEdit<?= $dept['id'] ?>">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <form action="/admin/organization/units/<?= $dept['id'] ?>/delete" method="POST"
                              class="js-confirm-action"
                              data-confirm-title="<?= __('Delete Organizational Unit') ?>"
                              data-confirm-message="<?= __('Are you sure you want to delete this organizational unit?') ?>"
                              data-confirm-button="<?= __('Delete') ?>"
                              data-confirm-variant="danger">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body p-3">
                    <!-- Direct members of department -->
                    <?php if (!empty($dept['users'])): ?>
                        <div class="mb-3 ps-2">
                            <small class="text-muted d-block mb-2"><i class="fa-solid fa-users me-1"></i><?= __('Direct Members') ?></small>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($dept['users'] as $usr): ?>
                                    <a href="/admin/users/<?= $usr['id'] ?>/edit" class="badge text-bg-secondary text-decoration-none" style="font-size:0.8rem;">
                                        <i class="fa-solid fa-user me-1"></i><?= \App\Core\View::escape($usr['full_name'] ?: $usr['username']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Children: Sub-departments and Teams -->
                    <?php if (!empty($dept['children'])): ?>
                        <div class="org-children">
                            <?php foreach ($dept['children'] as $child): ?>
                                <?php $isTeam = ($child['type'] === 'team'); ?>
                                <div class="org-node org-child rounded mb-2 p-3" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);margin-left:1.5rem;">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge <?= $isTeam ? 'text-bg-success' : 'text-bg-info' ?> rounded-pill me-2">
                                                <i class="fa-solid fa-<?= $isTeam ? 'people-group' : 'diagram-project' ?> me-1"></i>
                                                <?= $isTeam ? __('Team') : __('Sub-department') ?>
                                            </span>
                                            <span class="fw-semibold text-white"><?= \App\Core\View::escape($child['name']) ?></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#modalEdit<?= $child['id'] ?>">
                                                <i class="fa-solid fa-edit"></i>
                                            </button>
                                            <form action="/admin/organization/units/<?= $child['id'] ?>/delete" method="POST"
                                                  class="js-confirm-action"
                                                  data-confirm-title="<?= __('Delete Organizational Unit') ?>"
                                                  data-confirm-message="<?= __('Are you sure you want to delete this organizational unit?') ?>"
                                                  data-confirm-button="<?= __('Delete') ?>"
                                                  data-confirm-variant="danger">
                                                <?= \App\Core\Csrf::field() ?>
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <?php if (!empty($child['users'])): ?>
                                        <div class="mt-2 d-flex flex-wrap gap-2">
                                            <?php foreach ($child['users'] as $cusr): ?>
                                                <a href="/admin/users/<?= $cusr['id'] ?>/edit" class="badge text-bg-secondary text-decoration-none" style="font-size:0.75rem;">
                                                    <i class="fa-solid fa-user me-1"></i><?= \App\Core\View::escape($cusr['full_name'] ?: $cusr['username']) ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-2 text-muted" style="font-size:0.78rem;">
                                            <i class="fa-solid fa-inbox me-1"></i><?= __('No members assigned.') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Edit modal for child unit -->
                                <div class="modal fade" id="modalEdit<?= $child['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog"><div class="modal-content glass-panel">
                                        <div class="modal-header border-0">
                                            <h5 class="modal-title font-heading text-white"><?= __('Edit Organizational Unit') ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="/admin/organization/units/<?= $child['id'] ?>/update" method="POST">
                                            <?= \App\Core\Csrf::field() ?>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label"><?= __('Unit Name') ?></label>
                                                    <input type="text" class="form-control" name="name"
                                                           value="<?= \App\Core\View::escape($child['name']) ?>" required maxlength="150">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label"><?= __('Parent Department') ?></label>
                                                    <select class="form-select" name="parent_id" required>
                                                        <?php foreach ($departments as $d): ?>
                                                            <option value="<?= (int)$d['id'] ?>" <?= (int)$child['parent_id'] === (int)$d['id'] ? 'selected' : '' ?>>
                                                                <?= \App\Core\View::escape($d['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('Cancel') ?></button>
                                                <button type="submit" class="btn btn-premium"><?= __('Save') ?></button>
                                            </div>
                                        </form>
                                    </div></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <?php if (empty($dept['users'])): ?>
                            <div class="text-muted text-center py-2" style="font-size:0.8rem;">
                                <i class="fa-solid fa-inbox me-1"></i><?= __('No members assigned.') ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Edit modal for department -->
                <div class="modal fade" id="modalEdit<?= $dept['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog"><div class="modal-content glass-panel">
                        <div class="modal-header border-0">
                            <h5 class="modal-title font-heading text-white"><?= __('Edit Organizational Unit') ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="/admin/organization/units/<?= $dept['id'] ?>/update" method="POST">
                            <?= \App\Core\Csrf::field() ?>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label"><?= __('Unit Name') ?></label>
                                    <input type="text" class="form-control" name="name"
                                           value="<?= \App\Core\View::escape($dept['name']) ?>" required maxlength="150">
                                </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('Cancel') ?></button>
                                <button type="submit" class="btn btn-premium"><?= __('Save') ?></button>
                            </div>
                        </form>
                    </div></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal: Add Department -->
<div class="modal fade" id="modalAddDepartment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content glass-panel">
        <div class="modal-header border-0">
            <h5 class="modal-title font-heading text-white"><i class="fa-solid fa-building me-2"></i><?= __('Add Department') ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form action="/admin/organization/units" method="POST">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="type" value="department">
            <div class="modal-body">
                <div class="mb-3">
                    <label for="dept_name" class="form-label"><?= __('Unit Name') ?></label>
                    <input type="text" class="form-control" id="dept_name" name="name" required maxlength="150"
                           placeholder="<?= __('Unit Name') ?>">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('Cancel') ?></button>
                <button type="submit" class="btn btn-premium"><i class="fa-solid fa-save me-2"></i><?= __('Create') ?></button>
            </div>
        </form>
    </div></div>
</div>

<!-- Modal: Add Sub-department -->
<div class="modal fade" id="modalAddSubdepartment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content glass-panel">
        <div class="modal-header border-0">
            <h5 class="modal-title font-heading text-white"><i class="fa-solid fa-diagram-project me-2"></i><?= __('Add Sub-department') ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form action="/admin/organization/units" method="POST">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="type" value="subdepartment">
            <div class="modal-body">
                <div class="mb-3">
                    <label for="subdept_name" class="form-label"><?= __('Unit Name') ?></label>
                    <input type="text" class="form-control" id="subdept_name" name="name" required maxlength="150">
                </div>
                <div class="mb-3">
                    <label for="subdept_parent" class="form-label"><?= __('Parent Department') ?></label>
                    <select class="form-select" id="subdept_parent" name="parent_id" required>
                        <option value=""><?= __('Select Department…') ?></option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= (int)$d['id'] ?>"><?= \App\Core\View::escape($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('Cancel') ?></button>
                <button type="submit" class="btn btn-premium"><i class="fa-solid fa-save me-2"></i><?= __('Create') ?></button>
            </div>
        </form>
    </div></div>
</div>

<!-- Modal: Add Team -->
<div class="modal fade" id="modalAddTeam" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content glass-panel">
        <div class="modal-header border-0">
            <h5 class="modal-title font-heading text-white"><i class="fa-solid fa-people-group me-2"></i><?= __('Add Team') ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form action="/admin/organization/units" method="POST">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="type" value="team">
            <div class="modal-body">
                <div class="mb-3">
                    <label for="team_name_org" class="form-label"><?= __('Unit Name') ?></label>
                    <input type="text" class="form-control" id="team_name_org" name="name" required maxlength="150">
                </div>
                <div class="mb-3">
                    <label for="team_parent_org" class="form-label"><?= __('Parent Department') ?></label>
                    <select class="form-select" id="team_parent_org" name="parent_id" required>
                        <option value=""><?= __('Select Department…') ?></option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= (int)$d['id'] ?>"><?= \App\Core\View::escape($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('Cancel') ?></button>
                <button type="submit" class="btn btn-premium"><i class="fa-solid fa-save me-2"></i><?= __('Create Team') ?></button>
            </div>
        </form>
    </div></div>
</div>
