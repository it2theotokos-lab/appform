<div class="card p-4">
    <h2 class="card-title mb-2"><i class="fa-solid fa-bars me-2 text-primary" aria-hidden="true"></i> Διαχείριση Μενού ανά Ρόλο</h2>
    <p class="text-muted mb-4">Επιλέξτε έναν ρόλο παρακάτω για να διαμορφώσετε το sidebar μενού πλοήγησης.</p>

    <div class="list-group" style="max-width: 500px; gap: 8px;">
        <?php foreach ($roles as $r): ?>
            <a href="/admin/menus/<?= $r['id'] ?>/edit" 
               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center rounded border p-3" 
               style="background:var(--color-bg); border-color:var(--color-border)!important; color:var(--color-text); transition: all 0.2s ease-in-out;">
                <span class="fw-semibold"><i class="fa-solid fa-user-shield me-2 text-primary" aria-hidden="true"></i> <?= \App\Core\View::escape($r['name']) ?></span>
                <i class="fa-solid fa-chevron-right text-muted" aria-hidden="true"></i>
            </a>
        <?php endforeach; ?>
    </div>
</div>
