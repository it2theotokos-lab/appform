<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="h3 m-0 text-white"><i class="fa-solid fa-user-shield me-2 text-primary"></i> Αντιστοίχιση Ρόλων LDAP</h1>
        <p class="text-muted m-0">Συσχέτιση των Active Directory Groups με τους ρόλους πρόσβασης του AppForm.</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/settings/ldap" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Επιστροφή
        </a>
    </div>
</div>

<?= \App\Core\View::render('settings/nav') ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card p-3">
            <h6 class="text-white mb-3">Προσθήκη Αντιστοίχισης</h6>
            <form action="/admin/settings/ldap/role-mappings" method="POST">
                <?= \App\Core\Csrf::field() ?>

                <div class="mb-3">
                    <label for="directory_group" class="form-label small">AD Group Name</label>
                    <input type="text" class="form-control form-control-sm" id="directory_group" name="directory_group" required placeholder="π.χ. AppForm-Managers">
                </div>

                <div class="mb-3">
                    <label for="appform_role_id" class="form-label small">AppForm Role</label>
                    <select class="form-select form-select-sm" id="appform_role_id" name="appform_role_id">
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= \App\Core\View::escape($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-premium btn-sm w-100">Προσθήκη Mapping</button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card p-3">
            <h6 class="text-white mb-3">Υπάρχουσες Αντιστοιχίσεις</h6>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>AD Group</th>
                            <th>AppForm Role</th>
                            <th class="text-end">Ενέργειες</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mappings)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Δεν έχουν οριστεί αντιστοιχίσεις ρόλων.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mappings as $m): ?>
                                <tr>
                                    <td><strong><?= \App\Core\View::escape($m['directory_group']) ?></strong></td>
                                    <td><span class="badge bg-secondary"><?= \App\Core\View::escape($m['role_name']) ?></span></td>
                                    <td class="text-end">
                                        <form action="/admin/settings/ldap/role-mappings/<?= $m['id'] ?>/delete" method="POST" class="d-inline">
                                            <?= \App\Core\Csrf::field() ?>
                                            <button type="submit" class="btn btn-outline-danger btn-xs"><i class="fa-solid fa-trash"></i></button>
                                        </form>
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
