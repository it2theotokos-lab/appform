<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="h3 m-0 text-white"><i class="fa-solid fa-server me-2 text-primary"></i> <?= __('Active Directory / LDAP Settings') ?></h1>
        <p class="text-muted m-0"><?= __('Configure connection to Active Directory domain.') ?></p>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/settings/ldap/role-mappings" class="btn btn-outline-primary me-2">
            <i class="fa-solid fa-user-shield me-1"></i> <?= __('LDAP Role Mapping') ?>
        </a>
        <a href="/admin/settings" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> <?= __('Back') ?>
        </a>
    </div>
</div>

<?php require __DIR__ . '/nav.php'; ?>

<div class="card p-4">
    <form action="/admin/settings/ldap" method="POST" id="ldap-settings-form">
        <?= \App\Core\Csrf::field() ?>

        <div class="form-check form-switch mb-4">
            <input class="form-check-input" type="checkbox" id="provider_enabled" name="provider_enabled" value="1" <?= ($config['provider_enabled'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label text-white" for="provider_enabled"><?= __('Enable LDAP / AD Provider') ?></label>
        </div>

        <div class="row g-3">
            <div class="col-md-6 mb-3">
                <label for="ldap_host" class="form-label"><?= __('LDAP Host') ?> (e.g. active-directory.domain.com)</label>
                <input type="text" class="form-control" id="ldap_host" name="ldap_host" value="<?= \App\Core\View::escape($config['ldap_host'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="ldap_port" class="form-label"><?= __('LDAP Port') ?> (e.g. 389, 636)</label>
                <input type="number" class="form-control" id="ldap_port" name="ldap_port" value="<?= (int)($config['ldap_port'] ?? 389) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="use_ssl" name="use_ssl" value="1" <?= ($config['use_ssl'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label text-white" for="use_ssl">Use SSL / LDAPS</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="use_starttls" name="use_starttls" value="1" <?= ($config['use_starttls'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label text-white" for="use_starttls">Use StartTLS</label>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <label for="base_dn" class="form-label">Base DN (e.g. DC=domain,DC=com)</label>
                <input type="text" class="form-control" id="base_dn" name="base_dn" value="<?= \App\Core\View::escape($config['base_dn'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="bind_dn" class="form-label">Bind DN / User Distinguished Name</label>
                <input type="text" class="form-control" id="bind_dn" name="bind_dn" value="<?= \App\Core\View::escape($config['bind_dn'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="bind_password" class="form-label">Bind Password</label>
                <input type="password" class="form-control" id="bind_password" name="bind_password" value="" placeholder="[<?= __('Encrypted / Unchanged') ?>]">
            </div>
            <div class="col-md-6 mb-3">
                <label for="user_search_base" class="form-label">User Search Base OU (e.g. OU=Users)</label>
                <input type="text" class="form-control" id="user_search_base" name="user_search_base" value="<?= \App\Core\View::escape($config['user_search_base'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="user_filter" class="form-label">User Filter Query (e.g. (sAMAccountName={username}))</label>
                <input type="text" class="form-control" id="user_filter" name="user_filter" value="<?= \App\Core\View::escape($config['user_filter'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="group_search_base" class="form-label">Group Search Base OU</label>
                <input type="text" class="form-control" id="group_search_base" name="group_search_base" value="<?= \App\Core\View::escape($config['group_search_base'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="connection_timeout" class="form-label"><?= __('Timeout (seconds)') ?></label>
                <input type="number" class="form-control" id="connection_timeout" name="connection_timeout" value="<?= (int)($config['connection_timeout'] ?? 5) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="default_role_id" class="form-label"><?= __('Default User Role') ?></label>
                <select class="form-select" id="default_role_id" name="default_role_id">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= (int)($config['default_role_id'] ?? 2) === (int)$r['id'] ? 'selected' : '' ?>><?= \App\Core\View::escape($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="d-flex gap-3 justify-content-between mt-4">
            <button type="button" class="btn btn-outline-warning" id="test-connection-btn">
                <i class="fa-solid fa-vial me-1"></i> <?= __('Test LDAP Connection') ?>
            </button>
            <button type="submit" class="btn btn-premium">
                <i class="fa-solid fa-save me-1"></i> <?= __('Save Settings') ?>
            </button>
        </div>
    </form>
</div>

<div class="modal fade" id="testResultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border border-glass">
            <div class="modal-header border-bottom border-glass">
                <h5 class="modal-title text-white"><?= __('LDAP Test Result') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-soft" id="test-result-body">
                <?= __('Testing connection...') ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const testBtn = document.getElementById('test-connection-btn');
    testBtn.addEventListener('click', () => {
        const formData = new FormData();
        formData.append('csrf_token', '<?= \App\Core\Csrf::token() ?>');

        fetch('/admin/settings/ldap/test', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            const body = document.getElementById('test-result-body');
            body.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            const modal = new bootstrap.Modal(document.getElementById('testResultModal'));
            modal.show();
        })
        .catch(err => {
            const body = document.getElementById('test-result-body');
            body.innerHTML = `<div class="alert alert-danger"><?= __('Failed to connect to LDAP server.') ?></div>`;
            const modal = new bootstrap.Modal(document.getElementById('testResultModal'));
            modal.show();
        });
    });
});
</script>
