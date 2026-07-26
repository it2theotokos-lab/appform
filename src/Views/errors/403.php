<div class="glass-panel p-5 text-center my-5">
    <h1 class="text-danger font-heading mb-3" style="font-size: 72px;">403</h1>
    <h3 class="text-white mb-3">Δεν επιτρέπεται η πρόσβαση</h3>
    <p class="text-muted mb-4"><?= \App\Core\View::escape($message ?? 'Δεν έχετε τα απαραίτητα δικαιώματα για να δείτε αυτή τη σελίδα.') ?></p>
    <a href="/dashboard" class="btn btn-premium">Επιστροφή στο Dashboard</a>
</div>
