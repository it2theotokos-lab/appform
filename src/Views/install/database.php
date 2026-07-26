<h3>Βήμα 2: Ρύθμιση Βάσης Δεδομένων</h3>
<form method="POST" action="/install?step=2">
    <?= \App\Core\Csrf::field() ?>
    
    <div class="form-group">
        <label for="db_host">Host Βάσης Δεδομένων</label>
        <input type="text" id="db_host" name="db[host]" value="<?= htmlspecialchars($db['host'] ?? '127.0.0.1') ?>" required>
    </div>

    <div class="form-group">
        <label for="db_port">Port Βάσης Δεδομένων</label>
        <input type="text" id="db_port" name="db[port]" value="<?= htmlspecialchars($db['port'] ?? '3306') ?>" required>
    </div>

    <div class="form-group">
        <label for="db_name">Όνομα Βάσης Δεδομένων</label>
        <input type="text" id="db_name" name="db[name]" value="<?= htmlspecialchars($db['name'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label for="db_user">Όνομα Χρήστη Βάσης</label>
        <input type="text" id="db_user" name="db[user]" value="<?= htmlspecialchars($db['user'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label for="db_pass">Κωδικός Πρόσβασης Βάσης</label>
        <input type="password" id="db_pass" name="db[pass]" value="">
    </div>

    <button type="submit" name="action" value="test" class="btn" style="background-color: var(--border-color); margin-bottom: 10px;">Έλεγχος Σύνδεσης</button>
    <button type="submit" name="action" value="save" class="btn">Συνέχεια</button>
</form>
