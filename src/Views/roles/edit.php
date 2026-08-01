<div class="mb-4">
    <a href="/admin/roles" class="btn btn-outline-secondary btn-sm mb-3"><i class="fa-solid fa-arrow-left"></i> Πίσω</a>
    <h3 class="font-heading text-white">Επεξεργασία Δικαιωμάτων Ρόλου: <?= \App\Core\View::escape($role['name']) ?></h3>
</div>

<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger"><?= \App\Core\View::escape($error) ?></div>
<?php endif; ?>

<div class="glass-panel p-4">
    <form action="/admin/roles/<?= $role['id'] ?>/edit" method="POST">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="_method" value="PUT">

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label for="name" class="form-label">Όνομα Ρόλου</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= \App\Core\View::escape($role['name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label for="description" class="form-label">Περιγραφή</label>
                <input type="text" class="form-control" id="description" name="description" value="<?= \App\Core\View::escape($role['description'] ?? '') ?>">
            </div>
        </div>

        <h5 class="font-heading mb-3 text-white border-bottom border-glass pb-2">Δικαιώματα Πρόσβασης & Ενεργειών ανά Ενότητα</h5>

        <?php
        $grouped = [];
        foreach ($permissions as $p) {
            $parts = explode('.', $p['slug']);
            $module = $parts[0] ?? 'general';
            $grouped[$module][] = $p;
        }
        ?>

        <?php
        $moduleLabels = [
            'dashboard'          => 'Dashboard / Πίνακας Ελέγχου',
            'users'              => 'Users / Χρήστες',
            'roles'              => 'Roles / Ρόλοι',
            'permissions'        => 'Permissions / Δικαιώματα',
            'forms'              => 'Forms / Φόρμες',
            'submissions'        => 'Submissions / Υποβολές',
            'repositories'       => 'Repositories / Πηγές Δεδομένων',
            'menus'              => 'Navigation Menus / Μενού Πλοήγησης',
            'analytics'          => 'Analytics / Στατιστικά',
            'exports'            => 'Exports / Εξαγωγές',
            'document_templates' => 'Document Templates / Πρότυπα Εγγράφων',
            'workflows'          => 'Workflows / Ροές Εργασίας',
            'workflow_tasks'     => 'Workflow Tasks / Εργασίες Workflow',
            'workflow_instances' => 'Workflow Instances / Εκτελέσεις Workflow',
            'settings'           => 'System Settings / Ρυθμίσεις Συστήματος',
            'backups'            => 'Backups / Αντίγραφα Ασφαλείας',
            'demo_data'          => 'Demo Data / Δεδομένα Επίδειξης',
            'smtp'               => 'SMTP & Email / Αλληλογραφία',
            'restore'            => 'Restore Center / Επαναφορά',
            'queue'              => 'Background Queue / Ουρά Εργασιών',
            'plugins'            => 'Plugins / Πρόσθετα',
            'updates'            => 'Updates & System / Αναβαθμίσεις',
            'audit'              => 'Audit Logs / Καταγραφές Ελέγχου',
            'data_exchange'      => 'Data Exchange / Ανταλλαγή Δεδομένων',
        ];
        ?>

        <?php foreach ($grouped as $module => $perms): ?>
            <div class="card bg-dark bg-opacity-25 border border-glass mb-3">
                <div class="card-header bg-dark bg-opacity-50 border-bottom border-glass py-2">
                    <strong class="text-white text-uppercase font-heading" style="letter-spacing:1px;font-size:0.85rem;">
                        <i class="fa-solid fa-layer-group me-2 text-primary"></i><?= htmlspecialchars($moduleLabels[$module] ?? ucfirst($module)) ?>
                    </strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($perms as $p): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" id="perm_<?= $p['id'] ?>"
                                        <?= in_array($p['id'], $currentPerms) ? 'checked' : '' ?>
                                        <?= ($role['slug'] === 'administrator') ? 'disabled' : '' ?>>
                                    <label class="form-check-label text-muted" for="perm_<?= $p['id'] ?>">
                                        <strong class="text-white"><?= \App\Core\View::escape($p['slug']) ?></strong>
                                        <span class="d-block small text-muted"><?= \App\Core\View::escape($p['name']) ?></span>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-premium w-100 mt-3">Αποθήκευση Αλλαγών & Δικαιωμάτων <i class="fa-solid fa-save ms-2"></i></button>
    </form>
</div>
