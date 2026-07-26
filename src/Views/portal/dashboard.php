<div class="row g-4 mb-5">
    <h5 class="font-heading mb-3 text-white"><i class="fa-solid fa-list-check me-2"></i> Διαθέσιμες Φόρμες προς Υποβολή</h5>
    <?php if (empty($availableForms)): ?>
        <div class="col-12">
            <div class="glass-panel p-4 text-center">
                <p class="text-muted mb-0">Δεν υπάρχουν διαθέσιμες φόρμες για εσάς αυτή τη στιγμή.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($availableForms as $form): ?>
            <div class="col-md-4">
                <div class="glass-panel p-4 d-flex flex-column h-100 justify-content-between">
                    <div>
                        <h5 class="text-white font-heading mb-2"><?= htmlspecialchars($form['title']) ?></h5>
                        <p class="text-muted small"><?= htmlspecialchars($form['description'] ?? 'Χωρίς περιγραφή') ?></p>
                    </div>
                    <a href="/forms/<?= htmlspecialchars($form['slug']) ?>" class="btn btn-premium btn-sm w-100 mt-3">
                        Συμπλήρωση Φόρμας <i class="fa-solid fa-pen-to-square ms-2"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="glass-panel p-4">
    <h5 class="font-heading mb-4 text-white"><i class="fa-solid fa-clock-rotate-left me-2"></i> Ιστορικό Υποβολών</h5>
    
    <?php if ($success = \App\Core\Session::flash('success')): ?>
        <div class="alert alert-success"><?= \App\Core\View::escape($success) ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Φόρμα</th>
                    <th>Ημερομηνία Υποβολής</th>
                    <th>Κατάσταση</th>
                    <th>Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mySubmissions)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Δεν έχετε κάνει καμία υποβολή ακόμα.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($mySubmissions as $sub): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($sub['form_title']) ?></strong></td>
                            <td><?= $sub['submitted_at'] ? date('d/m/Y H:i', strtotime($sub['submitted_at'])) : 'Προσχέδιο' ?></td>
                            <td>
                                <?php
                                $badgeClass = 'bg-secondary';
                                if ($sub['status'] === 'approved') $badgeClass = 'bg-success';
                                elseif ($sub['status'] === 'rejected') $badgeClass = 'bg-danger';
                                elseif ($sub['status'] === 'under_review') $badgeClass = 'bg-warning text-dark';
                                elseif ($sub['status'] === 'draft') $badgeClass = 'bg-info text-dark';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= strtoupper($sub['status']) ?></span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="/my-submissions/<?= $sub['uuid'] ?>" class="btn btn-outline-info btn-sm">
                                        <i class="fa-solid fa-eye"></i> Λεπτομέρειες
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
