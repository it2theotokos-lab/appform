<div class="mb-4">
    <a href="/dashboard" class="btn btn-outline-secondary btn-sm mb-3"><i class="fa-solid fa-arrow-left"></i> Πίσω στο Portal</a>
    <h3 class="font-heading text-white">Το Προφίλ μου</h3>
</div>

<?php if ($success = \App\Core\Session::flash('success')): ?>
    <div class="alert alert-success"><?= \App\Core\View::escape($success) ?></div>
<?php endif; ?>

<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger"><?= \App\Core\View::escape($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-7">
        <div class="glass-panel p-4">
            <h5 class="font-heading text-white mb-4"><i class="fa-solid fa-user-gear me-2 text-primary"></i> Προσωπικά Στοιχεία</h5>
            <form action="/admin/profile" method="POST">
                <?= \App\Core\Csrf::field() ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Όνομα Χρήστη (Username)</label>
                        <input type="text" class="form-control" value="<?= \App\Core\View::escape($user['username'] ?? '') ?>" disabled readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Ρόλος</label>
                        <input type="text" class="form-control" value="<?= \App\Core\View::escape($user['role_name'] ?? '') ?>" disabled readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Ονοματεπώνυμο</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= \App\Core\View::escape($user['full_name'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Ηλεκτρονική Διεύθυνση (Email)</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= \App\Core\View::escape($user['email'] ?? '') ?>" required>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Τμήμα / Διεύθυνση</label>
                        <input type="text" class="form-control" value="<?= \App\Core\View::escape($user['directory_department'] ?? 'Γενική Διεύθυνση') ?>" disabled readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Προϊστάμενος (Manager)</label>
                        <input type="text" class="form-control" value="<?= \App\Core\View::escape($user['manager_name'] ?? 'Δεν έχει οριστεί') ?>" disabled readonly>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Ενημέρωση Προφίλ <i class="fa-solid fa-save ms-2"></i></button>
            </form>
        </div>
    </div>

    <div class="col-md-5">
        <div class="glass-panel p-4">
            <h5 class="font-heading text-white mb-4"><i class="fa-solid fa-key me-2 text-warning"></i> Αλλαγή Κωδικού</h5>
            <form action="/admin/profile/change-password" method="POST">
                <?= \App\Core\Csrf::field() ?>

                <div class="mb-3">
                    <label for="current_password" class="form-label">Τρέχων Κωδικός</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>

                <div class="mb-4">
                    <label for="new_password" class="form-label">Νέος Κωδικός</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                </div>

                <button type="submit" class="btn btn-premium w-100">Αλλαγή Κωδικού <i class="fa-solid fa-shield-halved ms-2"></i></button>
            </form>
        </div>
    </div>
</div>
