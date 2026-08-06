<?php
// View: Organizational Structure Tree (Unlimited Depth)
// Path: src/Views/users/organization.php

if (!function_exists('renderOrgTreeNode')) {
    function renderOrgTreeNode(array $node, int $depth, array $allUnits): void {
        $isRoot = ($depth === 0);
        $type = $node['type'] ?? 'department';
        $badgeClass = match($type) {
            'department' => 'bg-primary',
            'subdepartment' => 'text-bg-info',
            'team' => 'text-bg-success',
            default => 'text-bg-secondary'
        };
        $iconClass = match($type) {
            'department' => 'fa-building',
            'subdepartment' => 'fa-diagram-project',
            'team' => 'fa-people-group',
            default => 'fa-folder-tree'
        };
        $typeLabel = match($type) {
            'department' => __('Department'),
            'subdepartment' => __('Sub-department'),
            'team' => __('Team'),
            default => ucfirst($type)
        };
        ?>
        <div class="org-node rounded mb-2 p-3" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);margin-left:<?= $depth * 1.5 ?>rem;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge <?= $badgeClass ?> rounded-pill">
                        <i class="fa-solid <?= $iconClass ?> me-1"></i><?= $typeLabel ?>
                    </span>
                    <strong class="text-white fs-6"><?= \App\Core\View::escape($node['name']) ?></strong>
                    <?php if (!empty($node['users'])): ?>
                        <small class="text-muted">(<?= count($node['users']) ?> <?= __('members') ?>)</small>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <!-- Add Sub-unit button on every node -->
                    <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal"
                            data-bs-target="#modalAddChild<?= $node['id'] ?>" title="<?= __('Add Sub-unit') ?>">
                        <i class="fa-solid fa-plus me-1"></i><?= __('Add Sub-unit') ?>
                    </button>
                    <!-- Edit Unit -->
                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#modalEdit<?= $node['id'] ?>" title="<?= __('Edit') ?>">
                        <i class="fa-solid fa-edit"></i>
                    </button>
                    <!-- Delete Unit -->
                    <form action="/admin/organization/units/<?= $node['id'] ?>/delete" method="POST"
                          class="js-confirm-action d-inline"
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

            <!-- Assigned Members -->
            <?php if (!empty($node['users'])): ?>
                <div class="mt-2 d-flex flex-wrap gap-2 ps-2">
                    <?php foreach ($node['users'] as $usr): ?>
                        <a href="/admin/users/<?= $usr['id'] ?>/edit" class="badge text-bg-secondary text-decoration-none" style="font-size:0.75rem;">
                            <i class="fa-solid fa-user me-1"></i><?= \App\Core\View::escape($usr['full_name'] ?: $usr['username']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Child Nodes -->
            <?php if (!empty($node['children'])): ?>
                <div class="org-children mt-3">
                    <?php foreach ($node['children'] as $child): ?>
                        <?php renderOrgTreeNode($child, $depth + 1, $allUnits); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Edit Modal for Node -->
        <div class="modal fade" id="modalEdit<?= $node['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content glass-panel">
                <div class="modal-header border-0">
                    <h5 class="modal-title font-heading text-white"><?= __('Edit Organizational Unit') ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="/admin/organization/units/<?= $node['id'] ?>/update" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label"><?= __('Unit Name') ?></label>
                            <input type="text" class="form-control" name="name"
                                   value="<?= \App\Core\View::escape($node['name']) ?>" required maxlength="150">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= __('Parent Unit') ?></label>
                            <select class="form-select" name="parent_id">
                                <option value=""><?= __('Root Unit (No Parent)') ?></option>
                                <?php foreach ($allUnits as $u): ?>
                                    <?php if ((int)$u['id'] !== (int)$node['id']): ?>
                                        <option value="<?= (int)$u['id'] ?>" <?= (int)($node['parent_id'] ?? 0) === (int)$dId = $u['id'] ? 'selected' : '' ?>>
                                            <?= \App\Core\View::escape($u['name']) ?> (<?= ucfirst($u['type']) ?>)
                                        </option>
                                    <?php endif; ?>
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

        <!-- Add Sub-unit Modal for Node -->
        <div class="modal fade" id="modalAddChild<?= $node['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content glass-panel">
                <div class="modal-header border-0">
                    <h5 class="modal-title font-heading text-white">
                        <i class="fa-solid fa-plus me-2"></i><?= __('Add Sub-unit under') ?> "<?= \App\Core\View::escape($node['name']) ?>"
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="/admin/organization/units" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="parent_id" value="<?= (int)$node['id'] ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label"><?= __('Unit Name') ?></label>
                            <input type="text" class="form-control" name="name" required maxlength="150" placeholder="<?= __('Unit Name') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= __('Unit Type') ?></label>
                            <select class="form-select" name="type">
                                <option value="subdepartment"><?= __('Sub-department') ?></option>
                                <option value="team"><?= __('Team') ?></option>
                                <option value="department"><?= __('Department') ?></option>
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
        <?php
    }
}
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
        <?php foreach ($tree as $rootNode): ?>
            <?php renderOrgTreeNode($rootNode, 0, $departments); ?>
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
                    <label for="subdept_parent" class="form-label"><?= __('Parent Unit') ?></label>
                    <select class="form-select" id="subdept_parent" name="parent_id" required>
                        <option value=""><?= __('Select Parent Unit…') ?></option>
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
                    <label for="team_parent_org" class="form-label"><?= __('Parent Unit') ?></label>
                    <select class="form-select" id="team_parent_org" name="parent_id" required>
                        <option value=""><?= __('Select Parent Unit…') ?></option>
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

