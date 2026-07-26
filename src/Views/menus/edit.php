<div class="mb-4">
    <a href="/admin/menus" class="btn btn-outline-secondary btn-sm mb-3"><i class="fa-solid fa-arrow-left"></i> Πίσω στα Μενού</a>
    <h3 class="font-heading text-white">Διαμόρφωση Μενού: <?= \App\Core\View::escape($role['name']) ?></h3>
</div>

<?php if ($success = \App\Core\Session::flash('success')): ?>
    <div class="alert alert-success"><?= \App\Core\View::escape($success) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Left Column: Add Menu Item form -->
    <div class="col-md-5">
        <div class="glass-panel p-4">
            <h5 class="font-heading mb-4 text-white">Προσθήκη Στοιχείου Μενού</h5>
            <form action="/admin/menus/<?= $menu['id'] ?>/add-item" method="POST">
                <?= \App\Core\Csrf::field() ?>

                <div class="mb-3">
                    <label for="parent_id" class="form-label">Γονικό Στοιχείο Μενού</label>
                    <select class="form-select form-select-sm" id="parent_id" name="parent_id">
                        <option value="">-- Χωρίς Γονικό (Ρίζα) --</option>
                        <?php foreach ($items as $it): ?>
                            <option value="<?= $it['id'] ?>"><?= str_repeat('&nbsp;&nbsp;&nbsp;', $it['depth'] ?? 0) ?>└ <?= \App\Core\View::escape($it['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="label" class="form-label">Ετικέτα (Label)</label>
                    <input type="text" class="form-control form-control-sm" id="label" name="label" required placeholder="π.χ. Αξιολογήσεις">
                </div>

                <div class="mb-3">
                    <label for="icon" class="form-label">Εικονίδιο (Icon Class)</label>
                    <input type="text" class="form-control form-control-sm" id="icon" name="icon" value="fa-solid fa-link" placeholder="π.χ. fa-solid fa-folder">
                </div>

                <div class="mb-3">
                    <label for="item_type" class="form-label">Τύπος Συνδέσμου</label>
                    <select class="form-select form-select-sm" id="item_type" name="item_type" required>
                        <option value="route">Εσωτερικό Route</option>
                        <option value="external">Εξωτερικό URL</option>
                        <option value="form">Σύνδεσμος Φόρμας</option>
                        <option value="header">Κεφαλίδα (Header)</option>
                        <option value="divider">Διαχωριστικό (Divider)</option>
                    </select>
                </div>

                <div class="mb-3" id="groupRouteName">
                    <label for="route_name" class="form-label">Όνομα Route (Path)</label>
                    <input type="text" class="form-control form-control-sm" id="route_name" name="route_name" placeholder="π.χ. /dashboard">
                </div>

                <div class="mb-3 d-none" id="groupUrl">
                    <label for="url" class="form-label">Εξωτερικό URL</label>
                    <input type="url" class="form-control form-control-sm" id="url" name="url" placeholder="https://example.com">
                </div>

                <div class="mb-3 d-none" id="groupFormId">
                    <label for="form_id" class="form-label">Επιλογή Φόρμας</label>
                    <select class="form-select form-select-sm" id="form_id" name="form_id">
                        <option value="">Επιλέξτε φόρμα...</option>
                        <?php foreach ($forms as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= \App\Core\View::escape($f['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="permission_slug" class="form-label">Απαιτούμενο Δικαίωμα (Προαιρετικό)</label>
                    <select class="form-select form-select-sm" id="permission_slug" name="permission_slug">
                        <option value="">Κανένα</option>
                        <?php foreach ($permissions as $p): ?>
                            <option value="<?= $p['slug'] ?>"><?= \App\Core\View::escape($p['slug']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Σειρά Εμφάνισης (Sort Order)</label>
                    <input type="number" class="form-control form-control-sm" id="sort_order" name="sort_order" value="0">
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="open_in_new_tab" name="open_in_new_tab" value="1">
                    <label class="form-check-label small text-muted" for="open_in_new_tab">Άνοιγμα σε νέο tab</label>
                </div>

                <button type="submit" class="btn btn-premium btn-sm w-100">Προσθήκη στο Μενού <i class="fa-solid fa-plus ms-1"></i></button>
            </form>
        </div>
    </div>

    <!-- Right Column: Menu Items structure tree list -->
    <div class="col-md-7">
        <div class="glass-panel p-4">
            <h5 class="font-heading mb-4 text-white">Ιεραρχία Στοιχείων Μενού</h5>
            
            <div class="list-group" id="menuItemsList">
                <?php if (empty($items)): ?>
                    <p class="text-muted text-center py-4">Δεν έχουν προστεθεί στοιχεία στο μενού.</p>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php $indent = ($item['depth'] ?? 0) * 25; ?>
                        <div class="list-group-item bg-dark bg-opacity-25 border border-glass d-flex justify-content-between align-items-center mb-2 rounded" style="margin-left: <?= $indent ?>px;">
                            <div>
                                <i class="<?= htmlspecialchars($item['icon'] ?? 'fa-solid fa-link') ?> me-2 text-primary"></i>
                                <strong class="text-white"><?= \App\Core\View::escape($item['label']) ?></strong>
                                <small class="text-muted d-block ms-4">
                                    Τύπος: <?= $item['item_type'] ?> 
                                    <?php if ($item['item_type'] === 'route') echo '| Path: ' . htmlspecialchars($item['route_name']); ?>
                                    <?php if ($item['item_type'] === 'external') echo '| URL: ' . htmlspecialchars($item['url']); ?>
                                    | Σειρά: <?= $item['sort_order'] ?>
                                </small>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <!-- Reorder buttons -->
                                <form action="/admin/menus/items/<?= $item['id'] ?>/reorder" method="POST" class="d-inline">
                                    <?= \App\Core\Csrf::field() ?>
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit" class="btn btn-outline-secondary btn-xs" title="Μετακίνηση Πάνω"><i class="fa-solid fa-chevron-up"></i></button>
                                </form>
                                <form action="/admin/menus/items/<?= $item['id'] ?>/reorder" method="POST" class="d-inline">
                                    <?= \App\Core\Csrf::field() ?>
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit" class="btn btn-outline-secondary btn-xs" title="Μετακίνηση Κάτω"><i class="fa-solid fa-chevron-down"></i></button>
                                </form>

                                <!-- Edit button -->
                                <button type="button" class="btn btn-outline-warning btn-sm border-0 btn-edit-menu-item" 
                                        data-id="<?= $item['id'] ?>"
                                        data-label="<?= htmlspecialchars($item['label']) ?>"
                                        data-icon="<?= htmlspecialchars($item['icon'] ?? 'fa-solid fa-link') ?>"
                                        data-type="<?= htmlspecialchars($item['item_type']) ?>"
                                        data-route="<?= htmlspecialchars($item['route_name'] ?? '') ?>"
                                        data-url="<?= htmlspecialchars($item['url'] ?? '') ?>"
                                        data-form-id="<?= htmlspecialchars($item['form_id'] ?? '') ?>"
                                        data-permission="<?= htmlspecialchars($item['permission_slug'] ?? '') ?>"
                                        data-sort="<?= htmlspecialchars($item['sort_order']) ?>"
                                        data-parent-id="<?= htmlspecialchars($item['parent_id'] ?? '') ?>"
                                        data-tab="<?= htmlspecialchars($item['open_in_new_tab'] ?? 0) ?>"
                                        data-active="<?= htmlspecialchars($item['is_active'] ?? 1) ?>"
                                        title="Επεξεργασία">
                                    <i class="fa-solid fa-edit"></i>
                                </button>

                                <!-- Delete button with modal -->
                                <form action="/admin/menus/items/<?= $item['id'] ?>/delete" method="POST" class="d-inline">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button type="submit" class="btn btn-outline-danger btn-sm border-0" onclick="return confirm('Θέλετε να διαγράψετε αυτό το στοιχείο μενού;');"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Menu Item Modal -->
<div class="modal fade" id="editMenuItemModal" tabindex="-1" aria-labelledby="editMenuItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border border-glass">
            <div class="modal-header border-bottom border-glass">
                <h5 class="modal-title text-white" id="editMenuItemModalLabel">Επεξεργασία Στοιχείου Μενού</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editMenuItemForm" action="" method="POST">
                <?= \App\Core\Csrf::field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_parent_id" class="form-label">Γονικό Στοιχείο Μενού</label>
                        <select class="form-select form-select-sm" id="edit_parent_id" name="parent_id">
                            <option value="">-- Χωρίς Γονικό (Ρίζα) --</option>
                            <?php foreach ($items as $it): ?>
                                <option value="<?= $it['id'] ?>"><?= str_repeat('&nbsp;&nbsp;&nbsp;', $it['depth'] ?? 0) ?>└ <?= \App\Core\View::escape($it['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_label" class="form-label">Ετικέτα (Label)</label>
                        <input type="text" class="form-control form-control-sm" id="edit_label" name="label" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_icon" class="form-label">Εικονίδιο (Icon Class)</label>
                        <input type="text" class="form-control form-control-sm" id="edit_icon" name="icon">
                    </div>

                    <div class="mb-3">
                        <label for="edit_item_type" class="form-label">Τύπος Συνδέσμου</label>
                        <select class="form-select form-select-sm" id="edit_item_type" name="item_type" required>
                            <option value="route">Εσωτερικό Route</option>
                            <option value="external">Εξωτερικό URL</option>
                            <option value="form">Σύνδεσμος Φόρμας</option>
                            <option value="header">Κεφαλίδα (Header)</option>
                            <option value="divider">Διαχωριστικό (Divider)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="editGroupRouteName">
                        <label for="edit_route_name" class="form-label">Όνομα Route (Path)</label>
                        <input type="text" class="form-control form-control-sm" id="edit_route_name" name="route_name">
                    </div>

                    <div class="mb-3 d-none" id="editGroupUrl">
                        <label for="edit_url" class="form-label">Εξωτερικό URL</label>
                        <input type="url" class="form-control form-control-sm" id="edit_url" name="url">
                    </div>

                    <div class="mb-3 d-none" id="editGroupFormId">
                        <label for="edit_form_id" class="form-label">Επιλογή Φόρμας</label>
                        <select class="form-select form-select-sm" id="edit_form_id" name="form_id">
                            <option value="">Επιλέξτε φόρμα...</option>
                            <?php foreach ($forms as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= \App\Core\View::escape($f['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_permission_slug" class="form-label">Απαιτούμενο Δικαίωμα</label>
                        <select class="form-select form-select-sm" id="edit_permission_slug" name="permission_slug">
                            <option value="">Κανένα</option>
                            <?php foreach ($permissions as $p): ?>
                                <option value="<?= $p['slug'] ?>"><?= \App\Core\View::escape($p['slug']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_sort_order" class="form-label">Σειρά Εμφάνισης</label>
                        <input type="number" class="form-control form-control-sm" id="edit_sort_order" name="sort_order">
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_open_in_new_tab" name="open_in_new_tab" value="1">
                        <label class="form-check-label small text-muted" for="edit_open_in_new_tab">Άνοιγμα σε νέο tab</label>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active" value="1">
                        <label class="form-check-label small text-muted" for="edit_is_active">Ενεργό στοιχείο</label>
                    </div>
                </div>
                <div class="modal-footer border-top border-glass">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Ακύρωση</button>
                    <button type="submit" class="btn btn-premium btn-sm">Αποθήκευση Αλλαγών <i class="fa-solid fa-save ms-1"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('item_type').addEventListener('change', (e) => {
    const val = e.target.value;
    const gRoute = document.getElementById('groupRouteName');
    const gUrl = document.getElementById('groupUrl');
    const gForm = document.getElementById('groupFormId');

    gRoute.classList.add('d-none');
    gUrl.classList.add('d-none');
    gForm.classList.add('d-none');

    if (val === 'route') {
        gRoute.classList.remove('d-none');
    } else if (val === 'external') {
        gUrl.classList.remove('d-none');
    } else if (val === 'form') {
        gForm.classList.remove('d-none');
    }
});

document.getElementById('edit_item_type').addEventListener('change', (e) => {
    const val = e.target.value;
    const gRoute = document.getElementById('editGroupRouteName');
    const gUrl = document.getElementById('editGroupUrl');
    const gForm = document.getElementById('editGroupFormId');

    gRoute.classList.add('d-none');
    gUrl.classList.add('d-none');
    gForm.classList.add('d-none');

    if (val === 'route') {
        gRoute.classList.remove('d-none');
    } else if (val === 'external') {
        gUrl.classList.remove('d-none');
    } else if (val === 'form') {
        gForm.classList.remove('d-none');
    }
});

document.querySelectorAll('.btn-edit-menu-item').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const form = document.getElementById('editMenuItemForm');
        form.action = '/admin/menus/items/' + id + '/update';

        document.getElementById('edit_parent_id').value = this.dataset.parentId || '';
        document.getElementById('edit_label').value = this.dataset.label;
        document.getElementById('edit_icon').value = this.dataset.icon;
        
        const type = this.dataset.type;
        document.getElementById('edit_item_type').value = type;

        const gRoute = document.getElementById('editGroupRouteName');
        const gUrl = document.getElementById('editGroupUrl');
        const gForm = document.getElementById('editGroupFormId');

        gRoute.classList.add('d-none');
        gUrl.classList.add('d-none');
        gForm.classList.add('d-none');

        if (type === 'route') {
            gRoute.classList.remove('d-none');
            document.getElementById('edit_route_name').value = this.dataset.route;
        } else if (type === 'external') {
            gUrl.classList.remove('d-none');
            document.getElementById('edit_url').value = this.dataset.url;
        } else if (type === 'form') {
            gForm.classList.remove('d-none');
            document.getElementById('edit_form_id').value = this.dataset.formId;
        }

        document.getElementById('edit_permission_slug').value = this.dataset.permission || '';
        document.getElementById('edit_sort_order').value = this.dataset.sort;
        document.getElementById('edit_open_in_new_tab').checked = parseInt(this.dataset.tab) === 1;
        document.getElementById('edit_is_active').checked = parseInt(this.dataset.active) === 1;

        const editModal = new bootstrap.Modal(document.getElementById('editMenuItemModal'));
        editModal.show();
    });
});
</script>
