<?php
use App\Core\Auth;
use App\Core\Csrf;
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="header-page-title mb-0"><i class="fa-solid fa-user-gear text-info me-2"></i><?= __('Manage Access') ?></h1>
    <div>
        <a href="/admin/files" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i><?= __('Back to Files') ?>
        </a>
    </div>
</div>

<div id="alertContainer"></div>

            <!-- File Overview Card -->
            <div class="card card-outline card-primary mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-file me-2"></i><?= htmlspecialchars($file['title']) ?></h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong><?= __('Original Filename') ?>:</strong> <?= htmlspecialchars($file['original_name']) ?></p>
                            <p class="mb-1"><strong><?= __('File Size') ?>:</strong> <?= number_format($file['file_size'] / 1024, 1) ?> KB</p>
                            <p class="mb-1"><strong><?= __('MIME Type') ?>:</strong> <?= htmlspecialchars($file['mime_type']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong><?= __('Uploaded By') ?>:</strong> <?= htmlspecialchars($file['uploader_name'] ?: $file['uploader_username']) ?></p>
                            <p class="mb-1"><strong><?= __('Upload Date') ?>:</strong> <?= $file['created_at'] ?></p>
                            <?php if ($file['description']): ?>
                                <p class="mb-1"><strong><?= __('Description') ?>:</strong> <?= htmlspecialchars($file['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Access Control Card -->
            <div class="card card-outline card-info">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title"><i class="fa-solid fa-lock me-2"></i><?= __('Current Access Permissions') ?></h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        <?= __('The Uploader and Administrators always have full access to this file. Additional recipients listed below can view and download it.') ?>
                    </div>

                    <!-- Existing Permissions Table -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle" id="permTable">
                            <thead class="table-light">
                                <tr>
                                    <th><?= __('Recipient Type') ?></th>
                                    <th><?= __('Recipient Name') ?></th>
                                    <th><?= __('Date Granted') ?></th>
                                    <th class="text-end"><?= __('Action') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($permissions)): ?>
                                    <tr id="emptyPermRow">
                                        <td colspan="4" class="text-center text-muted py-3">
                                            <em><?= __('No custom recipients granted yet. Only Uploader & Administrators have access.') ?></em>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($permissions as $p): ?>
                                        <tr id="perm-row-<?= $p['id'] ?>">
                                            <td>
                                                <span class="badge bg-secondary"><?= strtoupper($p['grantee_type']) ?></span>
                                            </td>
                                            <td><strong><?= htmlspecialchars($p['grantee_label'] ?: 'ID #' . $p['grantee_id']) ?></strong></td>
                                            <td><small><?= $p['created_at'] ?></small></td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePermission(<?= $file['id'] ?>, <?= $p['id'] ?>)">
                                                    <i class="fa-solid fa-user-minus me-1"></i><?= __('Revoke') ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <hr>

                    <!-- Add New Permission Form -->
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-user-plus text-success me-2"></i><?= __('Add Recipient / Grant Access') ?></h5>
                    <div class="row g-3 align-items-end" id="addPermForm">
                        <div class="col-md-4">
                            <label class="form-label"><?= __('Target Type') ?></label>
                            <select id="granteeType" class="form-select" onchange="onGranteeTypeChange()">
                                <option value="user"><?= __('Specific User') ?></option>
                                <option value="department"><?= __('Department (includes all child units)') ?></option>
                                <option value="subdepartment"><?= __('Sub-department') ?></option>
                                <option value="team"><?= __('Team') ?></option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label"><?= __('Target Recipient') ?></label>
                            <select id="granteeId" class="form-select">
                                <!-- Populated dynamically by JS -->
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-success w-100" onclick="addPermission(<?= $file['id'] ?>)">
                                <i class="fa-solid fa-plus me-1"></i><?= __('Grant Access') ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
const csrfToken = '<?= Csrf::token() ?>';
const allUsers = <?= json_encode($allUsers) ?>;
const orgUnits = <?= json_encode($orgUnits) ?>;

function onGranteeTypeChange() {
    const type = document.getElementById('granteeType').value;
    const select = document.getElementById('granteeId');
    select.innerHTML = '';

    if (type === 'user') {
        allUsers.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            const displayName = (u.full_name && String(u.full_name).trim()) ? u.full_name : u.username;
            opt.textContent = displayName + ' (@' + u.username + ')';
            select.appendChild(opt);
        });
    } else {
        const filtered = orgUnits.filter(ou => ou.type === type);
        filtered.forEach(ou => {
            const opt = document.createElement('option');
            opt.value = ou.id;
            opt.textContent = ou.name + (ou.parent_name ? ' (' + ou.parent_name + ')' : '');
            select.appendChild(opt);
        });
    }
}

// Initial populate
document.addEventListener('DOMContentLoaded', onGranteeTypeChange);

function showAlert(type, msg) {
    document.getElementById('alertContainer').innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show">
            ${msg}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
}

function addPermission(fileId) {
    const type = document.getElementById('granteeType').value;
    const gid = document.getElementById('granteeId').value;

    if (!gid) {
        showAlert('warning', '<?= __('Please select a target recipient.') ?>');
        return;
    }

    const formData = new FormData();
    formData.append('_token', csrfToken);
    formData.append('perm_action', 'add');
    formData.append('grantee_type', type);
    formData.append('grantee_id', gid);

    fetch(`/admin/files/${fileId}/permissions`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => {
        if (!r.ok && r.status !== 200) {
            return r.text().then(t => { throw new Error(`HTTP ${r.status}: ${t.substring(0, 120)}`); });
        }
        return r.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => location.reload(), 900);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(err => showAlert('danger', '⚠️ ' + (err.message || 'Error processing request.')));
}

function removePermission(fileId, permId) {
    if (!confirm('<?= __('Are you sure you want to revoke access for this recipient?') ?>')) return;

    const formData = new FormData();
    formData.append('_token', csrfToken);
    formData.append('perm_action', 'remove');
    formData.append('perm_id', permId);

    fetch(`/admin/files/${fileId}/permissions`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => {
        if (!r.ok && r.status !== 200) {
            return r.text().then(t => { throw new Error(`HTTP ${r.status}`); });
        }
        return r.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            const row = document.getElementById(`perm-row-${permId}`);
            if (row) row.remove();
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(err => showAlert('danger', '⚠️ ' + (err.message || 'Error processing request.')));
}
</script>
