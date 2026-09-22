<div class="container py-5">
    <div class="glass-panel p-4 p-md-5 mx-auto" style="max-width: 760px;">
        <div class="text-center mb-4">
            <i class="fa-solid fa-calendar-check text-warning fa-3x mb-3"></i>
            <h2 class="font-heading text-white">Έχει ήδη γίνει σημερινή υποβολή</h2>
            <p class="text-muted mb-0"><?= \App\Core\View::escape($message) ?></p>
        </div>

        <?php if ($submission): ?>
            <div class="p-3 rounded border border-glass bg-dark bg-opacity-25 mb-4">
                <strong class="text-white"><?= \App\Core\View::escape($form['title']) ?></strong>
                <div class="small text-muted mt-1">
                    Υποβολή #<?= (int)$submission['id'] ?> · <?= date('d/m/Y H:i', strtotime($submission['submitted_at'] ?: $submission['created_at'])) ?>
                </div>
                <a class="btn btn-outline-primary btn-sm mt-3" href="/my-submissions/<?= rawurlencode($submission['uuid']) ?>">
                    <i class="fa-solid fa-eye me-1"></i> Προβολή υπάρχουσας υποβολής
                </a>
            </div>

            <?php if ($pendingRequest): ?>
                <div class="alert alert-info mb-0">
                    <i class="fa-solid fa-clock me-2"></i>Το αίτημα διόρθωσης έχει σταλεί και αναμένει απόφαση reviewer.
                </div>
            <?php else: ?>
                <form action="/my-submissions/<?= rawurlencode($submission['uuid']) ?>/correction-request" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <label for="correction_reason" class="form-label text-white">Λόγος αιτήματος διόρθωσης</label>
                    <textarea id="correction_reason" name="reason" class="form-control mb-3" rows="4" required maxlength="2000" placeholder="Περιγράψτε τι χρειάζεται να διορθωθεί..."></textarea>
                    <button class="btn btn-warning w-100" type="submit">
                        <i class="fa-solid fa-paper-plane me-2"></i>Αίτημα διόρθωσης προς reviewer
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
