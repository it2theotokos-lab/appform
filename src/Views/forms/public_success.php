<div class="glass-panel p-5 text-center my-5 border border-glass">
    <div class="mb-4">
        <i class="fa-solid fa-circle-check text-success fs-1"></i>
    </div>
    <h3 class="font-heading text-white mb-3">Επιτυχής Υποβολή!</h3>
    <h5 class="text-white-50 mb-4"><?= \App\Core\View::escape($title) ?></h5>
    <p class="text-muted fs-5 mb-5"><?= \App\Core\View::escape($message) ?></p>
    <a href="/login" class="btn btn-premium"><i class="fa-solid fa-sign-in me-2"></i>Σύνδεση στο Portal</a>
</div>
