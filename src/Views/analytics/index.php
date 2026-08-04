<div class="card p-4">
    <h2 class="card-title mb-2"><i class="fa-solid fa-chart-line me-2 text-primary" aria-hidden="true"></i> <?= __('Select Form for Analytics') ?></h2>
    <p class="text-muted mb-4"><?= __('Select one of the forms below to view detailed submission statistics.') ?></p>

    <div class="list-group" style="max-width: 600px; gap: 8px;">
        <?php foreach ($forms as $form): ?>
            <a href="/admin/analytics/forms/<?= $form['id'] ?>" 
               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center rounded border p-3"
               style="background:var(--color-bg); border-color:var(--color-border)!important; color:var(--color-text); transition: all 0.2s ease-in-out;">
                <span>
                    <strong class="text-soft"><?= \App\Core\View::escape($form['title']) ?></strong>
                    <small class="text-muted d-block" style="font-size:0.75rem;">slug: <?= \App\Core\View::escape($form['slug']) ?></small>
                </span>
                <i class="fa-solid fa-chart-bar text-muted" aria-hidden="true"></i>
            </a>
        <?php endforeach; ?>
    </div>
</div>
