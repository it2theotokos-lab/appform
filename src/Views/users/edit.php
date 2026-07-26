<div class="mb-4">
    <a href="/admin/users" class="btn btn-outline-secondary btn-sm mb-3"><i class="fa-solid fa-arrow-left"></i> Πίσω</a>
    <h3 class="font-heading text-white">Επεξεργασία Χρήστη</h3>
</div>

<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger"><?= \App\Core\View::escape($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-7">
        <div class="glass-panel p-4">
            <h5 class="font-heading mb-4 text-white">Βασικά Στοιχεία</h5>
            <form action="/admin/users/<?= $user['id'] ?>/edit" method="POST">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="_method" value="PUT">

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?= \App\Core\View::escape($user['username']) ?>" disabled>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= \App\Core\View::escape($user['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Ονοματεπώνυμο</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= \App\Core\View::escape($user['full_name']) ?>" required>
                </div>

                <div class="mb-4">
                    <label for="role_id" class="form-label">Ρόλος</label>
                    <select class="form-select" id="role_id" name="role_id" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= $user['role_id'] == $r['id'] ? 'selected' : '' ?>><?= \App\Core\View::escape($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="manager_id" class="form-label">Προϊστάμενος (Manager)</label>
                    <select class="form-select" id="manager_id" name="manager_id">
                        <option value="">-- Χωρίς Προϊστάμενο --</option>
                        <?php foreach ($managers as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= (int)($user['manager_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>>
                                <?= \App\Core\View::escape($m['full_name']) ?> &mdash; <?= \App\Core\View::escape($m['username']) ?> &mdash; <?= \App\Core\View::escape($m['role_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-premium w-100">Αποθήκευση Αλλαγών <i class="fa-solid fa-save ms-2"></i></button>
            </form>
        </div>
    </div>

    <div class="col-md-5">
        <div class="glass-panel p-4">
            <h5 class="font-heading mb-4 text-white">Επαναφορά Κωδικού</h5>
            <form action="/admin/users/<?= $user['id'] ?>/reset-password" method="POST">
                <?= \App\Core\Csrf::field() ?>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Νέος Κωδικός πρόσβασης</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-outline-warning w-100">Αλλαγή Κωδικού <i class="fa-solid fa-key ms-2"></i></button>
            </form>
        </div>
    </div>
</div>
