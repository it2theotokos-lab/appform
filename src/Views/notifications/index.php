<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="header-page-title mb-0"><?= __('Notifications') ?></h1>
    <div class="d-flex gap-2">
        <form action="/notifications/read-all" method="POST">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn-premium btn-sm"><i class="fa-solid fa-check-double me-1" aria-hidden="true"></i> <?= __('Mark all as read') ?></button>
        </form>
        <?php if (!empty($totalNotifications)): ?>
            <form action="/notifications/delete-all" method="POST" onsubmit="return confirm('Να διαγραφούν όλες οι δικές σας ειδοποιήσεις;');">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash me-1" aria-hidden="true"></i> Διαγραφή όλων</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card p-4">
    <?php if (empty($notifications)): ?>
        <p class="text-muted text-center py-4"><?= __('You have no notifications.') ?></p>
    <?php else: ?>
        <div class="list-group" style="gap: 8px;">
            <?php foreach ($notifications as $n): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center rounded border p-3"
                     style="background:var(--color-bg); border-color:var(--color-border)!important; color:var(--color-text); transition: all 0.2s ease-in-out;">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="<?= $n['is_read'] ? 'badge-status-rejected' : 'badge-status-approved' ?>">
                                <?= $n['is_read'] ? __('READ') : __('NEW') ?>
                            </span>
                            <strong class="text-soft"><?= \App\Core\View::escape($n['title']) ?></strong>
                        </div>
                        <p class="mb-1 text-muted" style="font-size:0.875rem;"><?= \App\Core\View::escape($n['message']) ?></p>
                        <?php if (!empty($n['sender_name'])): ?>
                            <small class="text-info d-block mb-1" style="font-size:0.75rem;"><i class="fa-solid fa-user me-1" aria-hidden="true"></i>Από: <?= \App\Core\View::escape($n['sender_name']) ?></small>
                        <?php endif; ?>
                        <small class="text-muted" style="font-size:0.75rem;"><i class="fa-regular fa-clock me-1" aria-hidden="true"></i><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></small>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <?php if (!$n['is_read']): ?>
                            <form action="/notifications/<?= $n['id'] ?>/read" method="POST">
                                <?= \App\Core\Csrf::field() ?>
                                <button type="submit" class="btn btn-premium btn-sm"><?= __('Read') ?></button>
                            </form>
                        <?php endif; ?>
                        <form action="/notifications/<?= $n['id'] ?>/delete" method="POST" onsubmit="return confirm('Να διαγραφεί η ειδοποίηση;');">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="page" value="<?= (int)$page ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Διαγραφή" aria-label="Διαγραφή">
                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4" aria-label="Σελίδες ειδοποιήσεων">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="/notifications?page=<?= max(1, $page - 1) ?>">Προηγούμενη</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="/notifications?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="/notifications?page=<?= min($totalPages, $page + 1) ?>">Επόμενη</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
