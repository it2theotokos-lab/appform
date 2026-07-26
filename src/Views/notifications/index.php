<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="header-page-title mb-0">Ειδοποιήσεις</h1>
    <form action="/notifications/read-all" method="POST">
        <?= \App\Core\Csrf::field() ?>
        <button type="submit" class="btn btn-premium btn-sm"><i class="fa-solid fa-check-double me-1" aria-hidden="true"></i> Σήμανση όλων ως αναγνωσμένα</button>
    </form>
</div>

<div class="card p-4">
    <?php if (empty($notifications)): ?>
        <p class="text-muted text-center py-4">Δεν έχετε καμία ειδοποίηση.</p>
    <?php else: ?>
        <div class="list-group" style="gap: 8px;">
            <?php foreach ($notifications as $n): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center rounded border p-3"
                     style="background:var(--color-bg); border-color:var(--color-border)!important; color:var(--color-text); transition: all 0.2s ease-in-out;">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="<?= $n['is_read'] ? 'badge-status-rejected' : 'badge-status-approved' ?>">
                                <?= $n['is_read'] ? 'ΑΝΑΓΝΩΣΜΕΝΟ' : 'ΝΕΟ' ?>
                            </span>
                            <strong class="text-soft"><?= \App\Core\View::escape($n['title']) ?></strong>
                        </div>
                        <p class="mb-1 text-muted" style="font-size:0.875rem;"><?= \App\Core\View::escape($n['message']) ?></p>
                        <small class="text-muted" style="font-size:0.75rem;"><i class="fa-regular fa-clock me-1" aria-hidden="true"></i><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></small>
                    </div>
                    <div>
                        <?php if (!$n['is_read']): ?>
                            <form action="/notifications/<?= $n['id'] ?>/read" method="POST">
                                <?= \App\Core\Csrf::field() ?>
                                <button type="submit" class="btn btn-premium btn-sm">Ανάγνωση</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
