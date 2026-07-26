<div class="glass-panel p-4 text-center my-5">
    <div class="mb-4">
        <i class="fa-solid fa-triangle-exclamation text-warning fs-1"></i>
    </div>
    <h3 class="font-heading text-white mb-3"><?= \App\Core\View::escape($title) ?></h3>
    <p class="text-muted fs-5 mb-4"><?= \App\Core\View::escape($message) ?></p>
    <a href="/login" class="btn btn-premium"><i class="fa-solid fa-sign-in me-2"></i>Σύνδεση στο Portal</a>
</div>
