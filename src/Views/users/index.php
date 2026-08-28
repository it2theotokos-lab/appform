<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="header-page-title mb-0"><?= __('User Management') ?></h1>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/data-exchange?entity=users" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-file-export me-2"></i> <?= __('Export') ?></a>
        <a href="/admin/users/organization" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-sitemap me-2"></i> <?= __('Org Structure') ?></a>
        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateTeam">
            <i class="fa-solid fa-people-group me-2"></i> <?= __('Create Team') ?>
        </button>
        <a href="/admin/users/create" class="btn btn-premium btn-sm"><i class="fa-solid fa-user-plus me-2" aria-hidden="true"></i> <?= __('Add User') ?></a>
    </div>
</div>
<!-- APPFORM USERS CONFIRM FIX BUILD 2026-07-18-01 -->


<?php if ($success = \App\Core\Session::flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i>
        <?= \App\Core\View::escape($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
        <?= \App\Core\View::escape($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card p-4 mb-4">
    <form method="GET" action="/admin/users" class="row g-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="<?= __('Search…') ?>" value="<?= \App\Core\View::escape($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="role_id" class="form-select">
                <option value=""><?= __('All Roles') ?></option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= $roleId == $r['id'] ? 'selected' : '' ?>><?= \App\Core\View::escape($r['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value=""><?= __('All Statuses') ?></option>
                <option value="1" <?= $status === '1' ? 'selected' : '' ?>><?= __('Active') ?></option>
                <option value="0" <?= $status === '0' ? 'selected' : '' ?>><?= __('Inactive') ?></option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-premium w-100"><i class="fa-solid fa-filter me-2" aria-hidden="true"></i> <?= __('Filter') ?></button>
        </div>
    </form>
</div>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= __('Name') ?></th>
                    <th>Email</th>
                    <th><?= __('Role') ?></th>
                    <th><?= __('Org Structure') ?></th>
                    <th><?= __('Provider') ?></th>
                    <th><?= __('Status') ?></th>
                    <th class="text-end"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4"><?= __('No users found.') ?></td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="user-avatar" style="width:32px;height:32px;font-size:0.75rem;" aria-hidden="true">
                                        <?= substr(strtoupper($u['username'] ?? 'U'), 0, 2) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-soft"><?= \App\Core\View::escape($u['full_name']) ?></div>
                                        <div class="text-muted" style="font-size:0.75rem;">@<?= \App\Core\View::escape($u['username']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= \App\Core\View::escape($u['email']) ?></td>
                            <td><span class="badge bg-secondary"><?= \App\Core\View::escape($u['role_name']) ?></span></td>
                            <td>
                                <?php if (!empty($u['org_unit_name'])): ?>
                                    <span class="badge" style="background:var(--color-accent,#6c63ff);font-size:0.7rem;">
                                        <i class="fa-solid fa-<?= $u['org_unit_type'] === 'department' ? 'building' : ($u['org_unit_type'] === 'team' ? 'people-group' : 'diagram-project') ?> me-1"></i>
                                        <?= \App\Core\View::escape($u['org_unit_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:0.75rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-dark"><?= strtoupper($u['authentication_provider'] ?? 'LOCAL') ?></span>
                            </td>
                            <td>
                                <span class="<?= $u['is_active'] ? 'badge-status-approved' : 'badge-status-rejected' ?>">
                                    <?= $u['is_active'] ? __('Active') : __('Inactive') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="/admin/users/<?= $u['id'] ?>/edit" class="btn btn-premium btn-sm" title="<?= __('Edit') ?>">
                                        <i class="fa-solid fa-edit" aria-hidden="true"></i>
                                    </a>
                                    
                                    <form action="/admin/users/<?= $u['id'] ?>/toggle-status" method="POST" class="js-confirm-action" data-confirm-title="<?= __('Change Status') ?>" data-confirm-message="<?= __('Do you want to change this user\'s status?') ?>" data-confirm-button="<?= __('Confirm') ?>" data-confirm-variant="warning">
                                         <?= \App\Core\Csrf::field() ?>
                                         <button type="submit" class="btn btn-premium btn-sm" title="<?= $u['is_active'] ? __('Deactivate') : __('Activate') ?>">
                                             <i class="fa-solid <?= $u['is_active'] ? 'fa-user-slash' : 'fa-user-check' ?>" aria-hidden="true"></i>
                                         </button>
                                     </form>
 
                                     <form action="/admin/users/<?= $u['id'] ?>/delete" method="POST" class="js-confirm-action" data-confirm-title="<?= __('Delete User') ?>" data-confirm-message="<?= __('Deletion is permanent. Do you want to continue?') ?>" data-confirm-button="<?= __('Delete') ?>" data-confirm-variant="danger">
                                         <?= \App\Core\Csrf::field() ?>
                                         <input type="hidden" name="_method" value="DELETE">
                                         <button type="submit" class="btn btn-premium btn-sm btn-delete" title="<?= __('Delete') ?>">
                                             <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                         </button>
                                     </form>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center mb-0">
                <?php 
                    // Build base URL for pagination links preserving filters
                    $queryParams = $_GET;
                    unset($queryParams['page']);
                    $queryString = http_build_query($queryParams);
                    $baseUrl = '/admin/users?' . ($queryString ? $queryString . '&' : '');
                ?>
                
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $baseUrl . 'page=' . ($page - 1) ?>" aria-label="<?= __('Previous') ?>">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $baseUrl . 'page=' . $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $baseUrl . 'page=' . ($page + 1) ?>" aria-label="<?= __('Next') ?>">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
<!-- Create Team Modal -->
<div class="modal fade" id="modalCreateTeam" tabindex="-1" aria-labelledby="modalCreateTeamLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content glass-panel">
            <div class="modal-header border-0">
                <h5 class="modal-title font-heading text-white" id="modalCreateTeamLabel">
                    <i class="fa-solid fa-people-group me-2"></i><?= __('Create Team') ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= __('Close') ?>"></button>
            </div>
            <form action="/admin/organization/units" method="POST">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="type" value="team">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="team_name" class="form-label"><?= __('Unit Name') ?></label>
                        <input type="text" class="form-control" id="team_name" name="name" required maxlength="150"
                               placeholder="<?= __('Unit Name') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="team_parent_id" class="form-label"><?= __('Parent Department') ?></label>
                        <select class="form-select" id="team_parent_id" name="parent_id" required>
                            <option value=""><?= __('Select Department…') ?></option>
                            <?php
                            try {
                                $depts = \App\Models\OrgUnit::getDepartments();
                                foreach ($depts as $d): ?>
                                    <option value="<?= (int)$d['id'] ?>"><?= \App\Core\View::escape($d['name']) ?></option>
                                <?php endforeach;
                            } catch (\Throwable $e) {}
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('Cancel') ?></button>
                    <button type="submit" class="btn btn-premium"><i class="fa-solid fa-save me-2"></i><?= __('Create Team') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
