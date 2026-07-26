<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon stat-primary">
                <i class="fa-solid fa-file-invoice" aria-hidden="true"></i>
            </div>
            <div class="stat-card-info">
                <div class="stat-card-label">Συνολικές Φόρμες</div>
                <div class="stat-card-value"><?= $formsCount ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon stat-info">
                <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i>
            </div>
            <div class="stat-card-info">
                <div class="stat-card-label">Υποβολές Χρηστών</div>
                <div class="stat-card-value"><?= $subsCount ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon stat-success">
                <i class="fa-solid fa-users" aria-hidden="true"></i>
            </div>
            <div class="stat-card-info">
                <div class="stat-card-label">Εγγεγραμμένοι Χρήστες</div>
                <div class="stat-card-value"><?= $usersCount ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="card-title mb-0"><i class="fa-solid fa-clock-rotate-left me-2 text-primary" aria-hidden="true"></i> Πρόσφατες Υποβολές</h2>
        <a href="/admin/submissions" class="btn btn-premium btn-sm">Προβολή Όλων</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Φόρμα</th>
                    <th>Χρήστης</th>
                    <th>Ημερομηνία</th>
                    <th>Κατάσταση</th>
                    <th class="text-end">Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($latestSubmissions)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Δεν υπάρχουν πρόσφατες υποβολές.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($latestSubmissions as $sub): ?>
                        <tr>
                            <td>#<?= $sub['id'] ?></td>
                            <td><strong><?= htmlspecialchars($sub['form_title']) ?></strong></td>
                            <td><span class="badge-status-draft" style="color:var(--color-primary);"><?= htmlspecialchars($sub['username']) ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($sub['created_at'])) ?></td>
                            <td>
                                <?php
                                $badgeClass = 'badge-status-draft';
                                if ($sub['status'] === 'approved') $badgeClass = 'badge-status-approved';
                                elseif ($sub['status'] === 'rejected') $badgeClass = 'badge-status-rejected';
                                elseif ($sub['status'] === 'under_review') $badgeClass = 'badge-status-pending';
                                ?>
                                <span class="<?= $badgeClass ?>"><?= strtoupper($sub['status']) ?></span>
                            </td>
                            <td class="text-end">
                                <a href="/admin/submissions/<?= $sub['id'] ?>" class="btn btn-premium btn-sm">
                                    <i class="fa-solid fa-eye me-1" aria-hidden="true"></i> Προβολή
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
