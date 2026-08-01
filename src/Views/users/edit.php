<div class="mb-4">
    <a href="/admin/users" class="btn btn-outline-secondary btn-sm mb-3">
        <i class="fa-solid fa-arrow-left me-1"></i><?= __('Back') ?>
    </a>
    <h3 class="font-heading text-white"><?= __('Edit User') ?> — <?= \App\Core\View::escape($user['full_name'] ?: $user['username']) ?></h3>
</div>

<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= \App\Core\View::escape($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success = \App\Core\Session::flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= \App\Core\View::escape($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs mb-4" id="userEditTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-basic" data-bs-toggle="tab" data-bs-target="#tabBasic"
                type="button" role="tab" aria-controls="tabBasic" aria-selected="true">
            <i class="fa-solid fa-user me-2"></i><?= __('Basic Info') ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-org" data-bs-toggle="tab" data-bs-target="#tabOrg"
                type="button" role="tab" aria-controls="tabOrg" aria-selected="false">
            <i class="fa-solid fa-sitemap me-2"></i><?= __('Organizational Placement') ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-extra" data-bs-toggle="tab" data-bs-target="#tabExtra"
                type="button" role="tab" aria-controls="tabExtra" aria-selected="false">
            <i class="fa-solid fa-address-card me-2"></i><?= __('Extra Details') ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-security" data-bs-toggle="tab" data-bs-target="#tabSecurity"
                type="button" role="tab" aria-controls="tabSecurity" aria-selected="false">
            <i class="fa-solid fa-lock me-2"></i><?= __('Security') ?>
        </button>
    </li>
</ul>

<!-- ═══════════════════════════════════════════════════════════════════
     MAIN USER PROFILE EDIT FORM
     Contains: Basic Info / Org Placement / Extra Details
     Does NOT contain any password inputs.
     ═══════════════════════════════════════════════════════════════════ -->
<form action="/admin/users/<?= $user['id'] ?>/edit"
      method="POST"
      id="userEditForm"
      autocomplete="off">
    <?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="_method" value="PUT">

    <div class="tab-content" id="userEditTabsContent">

        <!-- ── TAB 1: Basic Info ──────────────────────────────────── -->
        <div class="tab-pane fade show active" id="tabBasic" role="tabpanel" aria-labelledby="tab-basic">
            <div class="glass-panel p-4" style="max-width:600px;">
                <h5 class="font-heading mb-4 text-white"><?= __('Basic Info') ?></h5>

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?= \App\Core\View::escape($user['username']) ?>" disabled autocomplete="off">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= \App\Core\View::escape($user['email']) ?>"
                           required autocomplete="email">
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label"><?= __('Full Name') ?></label>
                    <input type="text" class="form-control" id="full_name" name="full_name"
                           value="<?= \App\Core\View::escape($user['full_name']) ?>"
                           required autocomplete="off">
                </div>

                <div class="mb-3">
                    <label for="role_id" class="form-label"><?= __('Role') ?></label>
                    <select class="form-select" id="role_id" name="role_id" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= $user['role_id'] == $r['id'] ? 'selected' : '' ?>>
                                <?= \App\Core\View::escape($r['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="manager_id" class="form-label"><?= __('Manager') ?></label>
                    <select class="form-select" id="manager_id" name="manager_id">
                        <option value="">-- <?= __('No Manager') ?> --</option>
                        <?php foreach ($managers as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= (int)($user['manager_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>>
                                <?= \App\Core\View::escape($m['full_name']) ?> &mdash; <?= \App\Core\View::escape($m['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-premium w-100" id="btnSaveBasic">
                    <?= __('Save') ?> <i class="fa-solid fa-save ms-2"></i>
                </button>
            </div>
        </div>

        <!-- ── TAB 2: Organizational Placement ──────────────────── -->
        <div class="tab-pane fade" id="tabOrg" role="tabpanel" aria-labelledby="tab-org">
            <div class="glass-panel p-4" style="max-width:600px;">
                <h5 class="font-heading mb-4 text-white">
                    <i class="fa-solid fa-sitemap me-2"></i><?= __('Organizational Placement') ?>
                </h5>

                <?php
                // Current unit display
                $currentUnitName = '';
                $currentUnitType = '';
                foreach ($orgUnits as $ou) {
                    if ((int)$ou['id'] === (int)($user['org_unit_id'] ?? 0)) {
                        $currentUnitName = $ou['name'];
                        $currentUnitType = $ou['type'];
                        break;
                    }
                }
                ?>

                <?php if ($currentUnitName): ?>
                    <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
                        <i class="fa-solid fa-sitemap"></i>
                        <span><?= __('Current Unit') ?>: <strong><?= \App\Core\View::escape($currentUnitName) ?></strong>
                        <span class="badge bg-secondary ms-1"><?= __($currentUnitType === 'department' ? 'Department' : ($currentUnitType === 'team' ? 'Team' : 'Sub-department')) ?></span>
                        </span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary d-flex align-items-center gap-2 mb-4">
                        <i class="fa-solid fa-inbox"></i>
                        <span><?= __('No unit assigned') ?></span>
                    </div>
                <?php endif; ?>

                <div class="mb-4">
                    <label for="org_unit_id" class="form-label"><?= __('Assign to Unit') ?></label>
                    <select class="form-select" id="org_unit_id" name="org_unit_id">
                        <option value="">— <?= __('No unit assigned') ?> —</option>
                        <?php
                        // Group by type for readability
                        $grouped = ['department' => [], 'subdepartment' => [], 'team' => []];
                        foreach ($orgUnits as $ou) {
                            $grouped[$ou['type']][] = $ou;
                        }
                        $labels = ['department' => __('Departments'), 'subdepartment' => __('Sub-departments'), 'team' => __('Teams')];
                        foreach ($grouped as $type => $units) {
                            if (empty($units)) continue;
                            echo "<optgroup label=\"" . htmlspecialchars($labels[$type]) . "\">";
                            foreach ($units as $ou) {
                                $sel = (int)($user['org_unit_id'] ?? 0) === (int)$ou['id'] ? 'selected' : '';
                                $prefix = $ou['parent_name'] ? ' (' . htmlspecialchars($ou['parent_name']) . ')' : '';
                                echo "<option value=\"{$ou['id']}\" {$sel}>" . htmlspecialchars($ou['name']) . $prefix . "</option>";
                            }
                            echo "</optgroup>";
                        }
                        ?>
                    </select>
                    <small class="form-text text-muted mt-1 d-block">
                        <i class="fa-solid fa-info-circle me-1"></i><?= __('Select a Department, Sub-department or Team to assign this user.') ?>
                    </small>
                </div>

                <button type="submit" class="btn btn-premium w-100" id="btnSaveOrg">
                    <?= __('Save') ?> <i class="fa-solid fa-save ms-2"></i>
                </button>
            </div>
        </div>

        <!-- ── TAB 3: Extra Details ───────────────────────────── -->
        <div class="tab-pane fade" id="tabExtra" role="tabpanel" aria-labelledby="tab-extra">
            <div class="glass-panel p-4" style="max-width:600px;">
                <h5 class="font-heading mb-4 text-white">
                    <i class="fa-solid fa-address-card me-2"></i><?= __('Extra Details') ?>
                </h5>
                <p class="text-muted mb-4" style="font-size:0.85rem;">
                    <i class="fa-solid fa-info-circle me-1"></i><?= __('All fields are optional.') ?>
                </p>

                <div class="mb-3">
                    <label for="personal_email" class="form-label"><?= __('Personal Email') ?></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" class="form-control" id="personal_email" name="personal_email"
                               value="<?= \App\Core\View::escape($user['personal_email'] ?? '') ?>"
                               maxlength="191" placeholder="user@personal.com" autocomplete="off">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="corporate_phone" class="form-label"><?= __('Corporate Phone') ?></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                        <input type="tel" class="form-control" id="corporate_phone" name="corporate_phone"
                               value="<?= \App\Core\View::escape($user['corporate_phone'] ?? '') ?>"
                               maxlength="30" placeholder="+30 210 0000000" autocomplete="off">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="mobile_phone" class="form-label"><?= __('Mobile Phone') ?></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-mobile-screen"></i></span>
                        <input type="tel" class="form-control" id="mobile_phone" name="mobile_phone"
                               value="<?= \App\Core\View::escape($user['mobile_phone'] ?? '') ?>"
                               maxlength="30" placeholder="+30 69x xxxxxxx" autocomplete="off">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="internal_phone" class="form-label"><?= __('Internal Phone') ?></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                        <input type="text" class="form-control" id="internal_phone" name="internal_phone"
                               value="<?= \App\Core\View::escape($user['internal_phone'] ?? '') ?>"
                               maxlength="20" placeholder="1234" autocomplete="off">
                    </div>
                </div>

                <button type="submit" class="btn btn-premium w-100" id="btnSaveExtra">
                    <?= __('Save') ?> <i class="fa-solid fa-save ms-2"></i>
                </button>
            </div>
        </div>

        <!-- ── TAB 4: Security — placeholder (real form is OUTSIDE this form) ── -->
        <div class="tab-pane fade" id="tabSecurity" role="tabpanel" aria-labelledby="tab-security">
            <!-- Reset Password form rendered below, outside #userEditForm -->
            <div id="resetPasswordMount"></div>
        </div>

    </div><!-- /.tab-content -->
</form><!-- /#userEditForm — NO password inputs above this line -->

<!-- ═══════════════════════════════════════════════════════════════════
     RESET PASSWORD FORM — completely separate, outside #userEditForm
     The browser cannot associate this password input with the main form.
     ═══════════════════════════════════════════════════════════════════ -->
<form action="/admin/users/<?= $user['id'] ?>/reset-password"
      method="POST"
      id="resetPasswordForm"
      autocomplete="off"
      style="display:none;">
    <?= \App\Core\Csrf::field() ?>

    <div class="glass-panel p-4" style="max-width:480px;">
        <h5 class="font-heading mb-2 text-white">
            <i class="fa-solid fa-lock me-2"></i><?= __('Security') ?> — <?= __('Reset Password') ?>
        </h5>
        <p class="text-muted mb-4" style="font-size:0.85rem;">
            <i class="fa-solid fa-info-circle me-1"></i>
            <?= __('Set a new password for this user. This action is independent of all other profile fields.') ?>
        </p>

        <div class="mb-3">
            <label for="new_password" class="form-label"><?= __('New Password') ?></label>
            <input type="password"
                   class="form-control"
                   id="new_password"
                   name="password"
                   autocomplete="new-password"
                   required
                   minlength="8"
                   placeholder="<?= __('Minimum 8 characters') ?>">
        </div>

        <div class="mb-4">
            <label for="new_password_confirm" class="form-label"><?= __('Confirm New Password') ?></label>
            <input type="password"
                   class="form-control"
                   id="new_password_confirm"
                   name="password_confirmation"
                   autocomplete="new-password"
                   required
                   minlength="8"
                   placeholder="<?= __('Repeat password') ?>">
        </div>

        <button type="submit" class="btn btn-outline-warning w-100" id="btnChangePassword">
            <i class="fa-solid fa-key me-2"></i><?= __('Change Password') ?>
        </button>
    </div>
</form><!-- /#resetPasswordForm -->

<script>
// Move the Reset Password form into the Security tab mount point.
// Done via JS to ensure it renders inside the tab panel visually,
// while remaining outside #userEditForm in the DOM at all times.
(function () {
    var mount = document.getElementById('resetPasswordMount');
    var form  = document.getElementById('resetPasswordForm');
    if (mount && form) {
        form.style.display = '';
        mount.appendChild(form);
    }

    // Client-side guard: confirm passwords match before submit
    form && form.addEventListener('submit', function (e) {
        var pw  = document.getElementById('new_password');
        var pw2 = document.getElementById('new_password_confirm');
        if (pw && pw2 && pw.value !== pw2.value) {
            e.preventDefault();
            alert('<?= __('Passwords do not match.') ?>');
            pw2.focus();
        }
    });
})();
</script>
