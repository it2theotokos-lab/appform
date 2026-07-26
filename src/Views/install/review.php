<h3>Βήμα 5: Έλεγχος Στοιχείων & Εγκατάσταση</h3>
<table>
    <tr>
        <th>Παράμετρος</th>
        <th>Τιμή</th>
    </tr>
    <tr>
        <td>Όνομα Εφαρμογής</td>
        <td><?= htmlspecialchars($app['name'] ?? '') ?></td>
    </tr>
    <tr>
        <td>Base URL</td>
        <td><?= htmlspecialchars($app['url'] ?? '') ?></td>
    </tr>
    <tr>
        <td>Περιβάλλον</td>
        <td><?= htmlspecialchars($app['env'] ?? '') ?></td>
    </tr>
    <tr>
        <td>Host Βάσης</td>
        <td><?= htmlspecialchars($db['host'] ?? '') ?></td>
    </tr>
    <tr>
        <td>Όνομα Βάσης</td>
        <td><?= htmlspecialchars($db['name'] ?? '') ?></td>
    </tr>
    <tr>
        <td>Διαχειριστής (Username)</td>
        <td><?= htmlspecialchars($admin['username'] ?? '') ?></td>
    </tr>
    <tr>
        <td>Email Διαχειριστή</td>
        <td><?= htmlspecialchars($admin['email'] ?? '') ?></td>
    </tr>
</table>

<form method="POST" action="/install?step=5">
    <?= \App\Core\Csrf::field() ?>
    <button type="submit" class="btn">Εκκίνηση Εγκατάστασης</button>
</form>
