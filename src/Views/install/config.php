<h3>Βήμα 3: Ρύθμιση Εφαρμογής</h3>
<form method="POST" action="/install?step=3">
    <?= \App\Core\Csrf::field() ?>

    <div class="form-group">
        <label for="app_name">Όνομα Εφαρμογής</label>
        <input type="text" id="app_name" name="app[name]" value="<?= htmlspecialchars($app['name'] ?? 'AppForm') ?>" required>
    </div>

    <div class="form-group">
        <label for="app_url">Base URL Εφαρμογής</label>
        <input type="url" id="app_url" name="app[url]" value="<?= htmlspecialchars($app['url'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label for="app_env">Περιβάλλον</label>
        <select id="app_env" name="app[env]">
            <option value="production" <?= ($app['env'] ?? 'production') === 'production' ? 'selected' : '' ?>>Production</option>
            <option value="development" <?= ($app['env'] ?? '') === 'development' ? 'selected' : '' ?>>Development</option>
        </select>
    </div>

    <button type="submit" class="btn">Συνέχεια</button>
</form>
