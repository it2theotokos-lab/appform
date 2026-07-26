<h3>Η εγκατάσταση ολοκληρώθηκε επιτυχώς!</h3>
<p>Η εφαρμογή AppForm έχει ρυθμιστεί και είναι έτοιμη για χρήση.</p>
<table>
    <tr>
        <th>Username Διαχειριστή</th>
        <td><?= htmlspecialchars($admin_username) ?></td>
    </tr>
    <tr>
        <th>URL Εφαρμογής</th>
        <td><a href="<?= htmlspecialchars($app_url) ?>" style="color: var(--primary);"><?= htmlspecialchars($app_url) ?></a></td>
    </tr>
</table>

<div class="alert alert-success" style="margin-top: 20px;">
    <strong>Προσοχή:</strong> Για λόγους ασφαλείας, ο installer έχει κλειδωθεί. Δεν είναι δυνατή η εκτέλεση της εγκατάστασης ξανά.
</div>

<a href="<?= htmlspecialchars($app_url) ?>/login" class="btn" style="text-align: center; text-decoration: none; display: block; box-sizing: border-box;">Είσοδος στο AppForm</a>
