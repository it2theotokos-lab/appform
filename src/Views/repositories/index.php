<div class="row g-4">
    <div class="col-md-5">
        <div class="card p-4">
            <h2 class="card-title mb-4"><i class="fa-solid fa-database me-2 text-primary" aria-hidden="true"></i> Προσθήκη Νέου Repository</h2>

            <?php if ($error = \App\Core\Session::flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
                    <?= \App\Core\View::escape($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="/admin/repositories" method="POST">
                <?= \App\Core\Csrf::field() ?>

                <div class="mb-3">
                    <label for="name" class="form-label">Όνομα Repository</label>
                    <input type="text" class="form-control" id="name" name="name" required placeholder="π.χ. Λίστα Τμημάτων">
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" class="form-control" id="slug" name="slug" required placeholder="e.g. departments">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Περιγραφή</label>
                    <textarea class="form-control" id="description" name="description" rows="2" placeholder="Περιγραφή..."></textarea>
                </div>

                <div class="mb-4">
                    <label for="data_json" class="form-label">Επιλογές (JSON Format)</label>
                    <textarea class="form-control font-monospace" id="data_json" name="data_json" rows="8" required placeholder='[
  {"value": "it", "label": "Πληροφορική"},
  {"value": "hr", "label": "Ανθρώπινο Δυναμικό"}
]'></textarea>
                </div>

                <button type="submit" class="btn btn-premium w-100 py-2">Δημιουργία Repository <i class="fa-solid fa-plus ms-2" aria-hidden="true"></i></button>
            </form>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card p-4">
            <h2 class="card-title mb-4"><i class="fa-solid fa-table-list me-2 text-primary" aria-hidden="true"></i> Υπάρχοντα Repositories</h2>

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
                            <th>Όνομα</th>
                            <th>Slug</th>
                            <th>Στοιχεία</th>
                            <th>Κατάσταση</th>
                            <th class="text-end">Ενέργειες</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($repos)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Δεν υπάρχουν Repositories.</td></tr>
                        <?php else: ?>
                            <?php foreach ($repos as $r): ?>
                                <tr>
                                    <td><strong><?= \App\Core\View::escape($r['name']) ?></strong></td>
                                    <td><code><?= \App\Core\View::escape($r['slug']) ?></code></td>
                                    <td>
                                        <?php 
                                        $items = json_decode($r['data_json'], true); 
                                        echo count($items) . ' επιλογές';
                                        ?>
                                    </td>
                                    <td>
                                        <span class="<?= $r['is_active'] ? 'badge-status-approved' : 'badge-status-rejected' ?>">
                                            <?= $r['is_active'] ? 'ΕΝΕΡΓΟ' : 'ΑΝΕΝΕΡΓΟ' ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="/admin/repositories/<?= $r['id'] ?>/edit" class="btn btn-premium btn-sm" title="Επεξεργασία"><i class="fa-solid fa-edit" aria-hidden="true"></i></a>
                                            <a href="/admin/repositories/<?= $r['id'] ?>" target="_blank" class="btn btn-premium btn-sm" title="Προβολή"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                            
                                            <form action="/admin/repositories/<?= $r['id'] ?>/toggle-status" method="POST" class="js-confirm-action" data-confirm-title="Αλλαγή κατάστασης" data-confirm-message="Θέλετε να αλλάξετε την κατάσταση του repository;" data-confirm-button="Επιβεβαίωση" data-confirm-variant="warning">
                                                 <?= \App\Core\Csrf::field() ?>
                                                 <button type="submit" class="btn btn-premium btn-sm" title="Ενεργοποίηση/Απενεργοποίηση">
                                                     <i class="fa-solid <?= $r['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>" aria-hidden="true"></i>
                                                 </button>
                                             </form>

                                             <form action="/admin/repositories/<?= $r['id'] ?>/delete" method="POST" class="js-confirm-action" data-confirm-title="Διαγραφή repository" data-confirm-message="Η διαγραφή είναι οριστική. Θέλετε να συνεχίσετε;" data-confirm-button="Διαγραφή" data-confirm-variant="danger">
                                                 <?= \App\Core\Csrf::field() ?>
                                                 <input type="hidden" name="_method" value="DELETE">
                                                 <button type="submit" class="btn btn-premium btn-sm btn-delete" title="Διαγραφή"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                             </form>
                                        </div>
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
