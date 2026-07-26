<div class="mb-4">
    <a href="/admin/forms" class="btn btn-outline-secondary btn-sm mb-3"><i class="fa-solid fa-arrow-left"></i> Πίσω στις Φόρμες</a>
    <h3 class="font-heading text-white">Ανάθεση Φόρμας: <?= \App\Core\View::escape($form['title']) ?></h3>
</div>

<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger"><?= \App\Core\View::escape($error) ?></div>
<?php endif; ?>

<div class="glass-panel p-4">
    <form action="/admin/forms/<?= $form['id'] ?>/assignments" method="POST">
        <?= \App\Core\Csrf::field() ?>

        <div class="row g-4">
            <!-- Roles assignment column -->
            <div class="col-md-6 border-end border-glass">
                <h5 class="font-heading mb-3 text-white"><i class="fa-solid fa-users me-2"></i> Ανάθεση σε Ρόλους</h5>
                <p class="text-muted small">Επιλέξτε ποιοι ρόλοι χρηστών θα έχουν πρόσβαση να βλέπουν και να υποβάλλουν αυτή τη φόρμα.</p>
                
                <div class="d-flex flex-column gap-2 mt-3">
                    <?php 
                    $assignedRoles = array_column($roleAssignments, 'role_id');
                    foreach ($roles as $role): 
                    ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="roles[]" value="<?= $role['id'] ?>" id="role_<?= $role['id'] ?>"
                                <?= in_array($role['id'], $assignedRoles) ? 'checked' : '' ?>>
                            <label class="form-check-label text-muted" for="role_<?= $role['id'] ?>">
                                <strong><?= \App\Core\View::escape($role['name']) ?></strong>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Users assignment column -->
            <div class="col-md-6">
                <h5 class="font-heading mb-3 text-white"><i class="fa-solid fa-user me-2"></i> Ανάθεση σε Συγκεκριμένους Χρήστες</h5>
                <p class="text-muted small">Επιλέξτε συγκεκριμένους χρήστες που θα έχουν πρόσβαση, ανεξαρτήτως ρόλου.</p>

                <div class="d-flex flex-column gap-2 mt-3">
                    <?php 
                    $assignedUsers = array_column($userAssignments, 'user_id');
                    foreach ($users as $u): 
                    ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="users[]" value="<?= $u['id'] ?>" id="user_<?= $u['id'] ?>"
                                <?= in_array($u['id'], $assignedUsers) ? 'checked' : '' ?>>
                            <label class="form-check-label text-muted" for="user_<?= $u['id'] ?>">
                                <strong><?= \App\Core\View::escape($u['full_name']) ?></strong> <small class="text-muted">(<?= \App\Core\View::escape($u['username']) ?>)</small>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-premium w-100 mt-5">Αποθήκευση Αναθέσεων <i class="fa-solid fa-save ms-2"></i></button>
    </form>
</div>
