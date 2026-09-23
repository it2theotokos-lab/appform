<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="header-page-title mb-0"><?= __('Notifications') ?></h1>
    <form action="/notifications/read-all" method="POST">
        <?= \App\Core\Csrf::field() ?>
        <button type="submit" class="btn btn-premium btn-sm"><i class="fa-solid fa-check-double me-1" aria-hidden="true"></i> <?= __('Mark all as read') ?></button>
    </form>
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
                    <div>
                        <?php if (!$n['is_read']): ?>
                            <form action="/notifications/<?= $n['id'] ?>/read" method="POST">
                                <?= \App\Core\Csrf::field() ?>
                                <button type="submit" class="btn btn-premium btn-sm"><?= __('Read') ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
