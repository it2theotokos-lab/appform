<h3>Βήμα 1: Έλεγχος Απαιτήσεων Συστήματος</h3>
<table>
    <thead>
        <tr>
            <th>Απαίτηση</th>
            <th>Αναμενόμενο</th>
            <th>Ανιχνεύθηκε</th>
            <th>Κατάσταση</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($requirements as $key => $req): ?>
        <tr>
            <td><?= htmlspecialchars($req['name']) ?></td>
            <td><?= htmlspecialchars($req['required']) ?></td>
            <td><?= htmlspecialchars($req['detected']) ?></td>
            <td>
                <?php if ($req['pass']): ?>
                    <span class="badge badge-success">PASS</span>
                <?php else: ?>
                    <span class="badge badge-danger">FAIL</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<form method="POST" action="/install?step=1">
    <?= \App\Core\Csrf::field() ?>
    <button type="submit" class="btn" <?= $allowProceed ? '' : 'disabled' ?>>Συνέχεια</button>
</form>
