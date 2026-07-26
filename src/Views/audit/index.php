<div class="mb-4">
    <h1 class="header-page-title">Audit Log Viewer</h1>
    <p class="text-muted">Καταγραφή ενεργειών χρηστών και συμβάντων συστήματος.</p>
</div>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Ημερομηνία</th>
                    <th>Χρήστης</th>
                    <th>Ενέργεια</th>
                    <th>Entity Type</th>
                    <th>Entity ID</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                        <td><span class="badge-status-draft" style="color:var(--color-primary); font-weight: 500;">@<?= \App\Core\View::escape($log['username'] ?? 'System') ?></span></td>
                        <td><code><?= \App\Core\View::escape($log['action']) ?></code></td>
                        <td><?= \App\Core\View::escape($log['entity_type'] ?? '—') ?></td>
                        <td>#<?= $log['entity_id'] ?? '—' ?></td>
                        <td><code><?= \App\Core\View::escape($log['ip_address']) ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
