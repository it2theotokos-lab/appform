<h3>Βήμα 4: Δημιουργία Διαχειριστή</h3>
<form method="POST" action="/install?step=4">
    <?= \App\Core\Csrf::field() ?>

    <div class="form-group">
        <label for="admin_user">Όνομα Χρήστη (Username)</label>
        <input type="text" id="admin_user" name="admin[username]" value="<?= htmlspecialchars($admin['username'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label for="admin_name">Ονοματεπώνυμο</label>
        <input type="text" id="admin_name" name="admin[full_name]" value="<?= htmlspecialchars($admin['full_name'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label for="admin_email">Email</label>
        <input type="email" id="admin_email" name="admin[email]" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label for="admin_pass">Κωδικός Πρόσβασης (Password)</label>
        <input type="password" id="admin_pass" name="admin[password]" required>
    </div>

    <div class="form-group">
        <label for="admin_pass_confirm">Επιβεβαίωση Κωδικού</label>
        <input type="password" id="admin_pass_confirm" name="admin[password_confirmation]" required>
    </div>

    <button type="submit" class="btn">Συνέχεια</button>
</form>
