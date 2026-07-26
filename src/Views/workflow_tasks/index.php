<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h1 class="h3 m-0 text-soft"><i class="fa-solid fa-square-check me-2 text-primary"></i> Εκκρεμείς Ενέργειες Έγκρισης</h1>
        <p class="text-muted m-0">Επιλέξτε μια εργασία για να προχωρήσετε με την ανασκόπηση, έγκριση ή υπογραφή εγγράφου.</p>
    </div>
</div>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Αριθμός Εγγράφου</th>
                    <th>Τίτλος</th>
                    <th>Πρότυπο</th>
                    <th>Βήμα Ροής</th>
                    <th>Δημιουργός</th>
                    <th>Κατάσταση Εργασίας</th>
                    <th>Ημερομηνία Ανάθεσης</th>
                    <th>Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Δεν βρέθηκαν εκκρεμείς εργασίες έγκρισης.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td class="align-middle font-weight-bold text-soft"><?= \App\Core\View::escape($task['document_number']) ?></td>
                            <td class="align-middle text-soft"><?= \App\Core\View::escape($task['doc_title']) ?></td>
                            <td class="align-middle text-muted"><?= \App\Core\View::escape($task['template_title']) ?></td>
                            <td class="align-middle">
                                <?php
                                $stepName = $task['step_name'];
                                if ($stepName === 'Manager Review') {
                                    $stepName = 'Έλεγχος Προϊσταμένου';
                                } elseif ($stepName === 'Director Signature') {
                                    $stepName = 'Υπογραφή Διευθυντή';
                                }
                                ?>
                                <span class="badge bg-secondary"><?= \App\Core\View::escape($stepName) ?></span>
                            </td>
                            <td class="align-middle text-muted"><?= \App\Core\View::escape($task['creator_name']) ?></td>
                            <td class="align-middle">
                                <?php if ($task['status'] === 'active'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-spinner fa-spin me-1"></i> Εκκρεμεί</span>
                                <?php else: ?>
                                    <span class="badge bg-dark"><?= \App\Core\View::escape($task['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle text-muted"><?= $task['created_at'] ?></td>
                            <td class="align-middle">
                                <a href="/workflow/tasks/<?= (int)$task['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fa-solid fa-square-check me-1"></i> Εξέταση
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
