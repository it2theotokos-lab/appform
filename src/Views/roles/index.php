<div class="row g-4">
    <div class="col-md-5">
        <div class="card p-4">
            <h2 class="card-title mb-4"><i class="fa-solid fa-user-shield me-2 text-primary" aria-hidden="true"></i> Προσθήκη Νέου Ρόλου</h2>
            
            <?php if ($error = \App\Core\Session::flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
                    <?= \App\Core\View::escape($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="/admin/roles" method="POST">
                <?= \App\Core\Csrf::field() ?>

                <div class="mb-3">
                    <label for="name" class="form-label">Όνομα Ρόλου</label>
                    <input type="text" class="form-control" id="name" name="name" required placeholder="π.χ. Υπάλληλος Γραφείου">
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" class="form-control" id="slug" name="slug" required placeholder="π.χ. staff">
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label">Περιγραφή</label>
                    <textarea class="form-control" id="description" name="description" rows="2" placeholder="Σύντομη περιγραφή..."></textarea>
                </div>

                <button type="submit" class="btn btn-premium w-100 py-2">Δημιουργία Ρόλου <i class="fa-solid fa-plus ms-2" aria-hidden="true"></i></button>
            </form>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card p-4">
            <h2 class="card-title mb-4"><i class="fa-solid fa-shield-halved me-2 text-primary" aria-hidden="true"></i> Υπάρχοντες Ρόλοι</h2>
            
            <?php if ($success = \App\Core\Session::flash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i>
                    <?= \App\Core\View::escape($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ρόλος</th>
                            <th>Slug</th>
                            <th class="text-end">Ενέργειες</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $r): ?>
                            <tr>
                                <td>#<?= $r['id'] ?></td>
                                <td><strong><?= \App\Core\View::escape($r['name']) ?></strong></td>
                                <td><code><?= \App\Core\View::escape($r['slug']) ?></code></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="/admin/roles/<?= $r['id'] ?>/edit" class="btn btn-premium btn-sm"><i class="fa-solid fa-shield-halved me-1" aria-hidden="true"></i> Δικαιώματα</a>
                                        <?php if (!$r['is_system']): ?>
                                            <form action="/admin/roles/<?= $r['id'] ?>/delete" method="POST" class="js-confirm-action" data-confirm-title="Διαγραφή ρόλου" data-confirm-message="Θέλετε σίγουρα να διαγράψετε αυτόν τον ρόλο;" data-confirm-button="Διαγραφή" data-confirm-variant="danger">
                                                 <?= \App\Core\Csrf::field() ?>
                                                 <input type="hidden" name="_method" value="DELETE">
                                                 <button type="submit" class="btn btn-premium btn-sm btn-delete" title="Διαγραφή"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                             </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
