<?php
// $tab may be set by the controller (e.g. showUpdates() passes 'tab'=>'updates')
// Fallback to ?tab= query param for tab-based pages, then default to 'general'
$activeTab = $tab ?? $_GET['tab'] ?? 'general';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h1 class="h3 m-0 text-white"><i class="fa-solid fa-gears me-2 text-primary"></i> Κέντρο Διαχείρισης Συστήματος</h1>
        <p class="text-muted m-0">Διαμόρφωση γενικών παραμέτρων, λήψη αντιγράφων ασφαλείας, ρυθμίσεις SMTP και demo data.</p>
    </div>
</div>

<?php if ($success = \App\Core\Session::flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i>
        <?= \App\Core\View::escape($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
        <?= \App\Core\View::escape($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Tabs Navigation Header -->
<?php require __DIR__ . '/nav.php'; ?>

<!-- Tab Content Container Panels -->
<div class="card p-4">
    <?php if ($activeTab === 'general'): ?>
        <div>
            <h4 class="text-white mb-2"><i class="fa-solid fa-sliders text-primary me-2"></i> Γενικές Ρυθμίσεις</h4>
            <p class="text-muted mb-4">Διαμόρφωση βασικών παραμέτρων λειτουργίας της πύλης.</p>

            <form action="/admin/settings/update" method="POST" style="max-width: 600px;">
                <?= \App\Core\Csrf::field() ?>
                <?php foreach ($settings as $set): ?>
                    <div class="mb-3">
                        <label for="<?= $set['setting_key'] ?>" class="form-label text-white text-capitalize">
                            <?php
                            $label = $set['setting_key'];
                            if ($label === 'app_name') $label = 'Όνομα Εφαρμογής';
                            elseif ($label === 'csv_delimiter') $label = 'Διαχωριστικό CSV';
                            elseif ($label === 'default_locale') $label = 'Προεπιλεγμένη Γλώσσα';
                            elseif ($label === 'records_per_page') $label = 'Εγγραφές ανά Σελίδα';
                            elseif ($label === 'upload_max_filesize') $label = 'Μέγιστο Μέγεθος Αρχείου (MB)';
                            echo htmlspecialchars($label);
                            ?>
                        </label>
                        <input type="text" class="form-control" id="<?= $set['setting_key'] ?>" name="<?= $set['setting_key'] ?>" value="<?= \App\Core\View::escape($set['setting_value']) ?>">
                        <small class="text-muted">Καταχωρήστε την τιμή παραμέτρου για το κλειδί <?= htmlspecialchars($set['setting_key']) ?>.</small>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary mt-3"><i class="fa-solid fa-save me-1"></i> Αποθήκευση</button>
            </form>
        </div>

    <?php elseif ($activeTab === 'backup'): ?>
        <div>
            <h4 class="text-white mb-2"><i class="fa-solid fa-database text-primary me-2"></i> Διαχείριση Αντιγράφων Ασφαλείας</h4>
            <p class="text-muted mb-4">Λήψη χειροκίνητων τοπικών αντιγράφων της βάσης δεδομένων και των αποθηκευμένων αρχείων.</p>

            <div class="d-flex gap-2 mb-4">
                <form action="/admin/settings/backup/create" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="backup_type" value="database">
                    <button type="submit" class="btn btn-outline-success"><i class="fa-solid fa-file-excel me-1"></i> Δημιουργία Backup Βάσης</button>
                </form>
                <form action="/admin/settings/backup/create" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="backup_type" value="files">
                    <button type="submit" class="btn btn-outline-info"><i class="fa-solid fa-file-zipper me-1"></i> Δημιουργία Backup Αρχείων</button>
                </form>
                <form action="/admin/settings/backup/create" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="backup_type" value="full">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-box-archive me-1"></i> Δημιουργία Πλήρους Backup</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="text-white">
                            <th>Όνομα</th>
                            <th>Τύπος</th>
                            <th>Μέγεθος</th>
                            <th>Δημιουργήθηκε</th>
                            <th>Κατάσταση</th>
                            <th>SHA256</th>
                            <th>Ενέργειες</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($backups)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Δεν βρέθηκαν τοπικά αντίγραφα ασφαλείας.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($backups as $b): ?>
                                <tr>
                                    <td class="align-middle text-white font-weight-bold"><?= \App\Core\View::escape($b['filename']) ?></td>
                                    <td class="align-middle text-muted"><?= htmlspecialchars($b['backup_type']) ?></td>
                                    <td class="align-middle text-muted"><?= round($b['file_size'] / 1024 / 1024, 2) ?> MB</td>
                                    <td class="align-middle text-muted"><?= $b['created_at'] ?></td>
                                    <td class="align-middle">
                                        <?php if ($b['status'] === 'completed' || $b['status'] === 'verified'): ?>
                                            <span class="badge bg-success"><?= $b['status'] === 'verified' ? 'Επαληθεύτηκε' : 'Ολοκληρώθηκε' ?></span>
                                        <?php elseif ($b['status'] === 'failed'): ?>
                                            <span class="badge bg-danger">Απέτυχε</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Σε Αναμονή</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle text-muted small" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($b['sha256_hash']) ?></td>
                                    <td class="align-middle">
                                        <a href="/admin/settings/backup/<?= (int)$b['id'] ?>/download" class="btn btn-sm btn-outline-info me-1"><i class="fa-solid fa-download"></i></a>
                                        <a href="/admin/settings/backup/<?= (int)$b['id'] ?>/verify" class="btn btn-sm btn-outline-success me-1" title="Έλεγχος Ακεραιότητας"><i class="fa-solid fa-shield-heart"></i></a>
                                        <form action="/admin/settings/backup/<?= (int)$b['id'] ?>/delete" method="POST" class="d-inline">
                                            <?= \App\Core\Csrf::field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Θέλετε να διαγράψετε οριστικά αυτό το backup;')"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($activeTab === 'demo'): ?>
        <div>
            <h4 class="text-white mb-2"><i class="fa-solid fa-cubes text-primary me-2"></i> Demo Data & Seeders</h4>
            <p class="text-muted mb-4">Εισαγωγή ή διαγραφή εικονικών δοκιμαστικών εγγραφών (users, templates, workflows).</p>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card p-4 border border-secondary bg-dark">
                        <h5 class="text-success"><i class="fa-solid fa-cloud-arrow-down me-2"></i> Εισαγωγή Demo Data</h5>
                        <p class="text-muted small">Θα δημιουργηθεί ένας demo reviewer χρήστης και εικονικά πρότυπα για άμεση δοκιμή ροών.</p>
                        <form action="/admin/settings/demo/import" method="POST">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="btn btn-success mt-2"><i class="fa-solid fa-play me-1"></i> Εκτέλεση Εισαγωγής</button>
                        </form>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card p-4 border border-danger bg-dark">
                        <h5 class="text-danger"><i class="fa-solid fa-trash-can me-2"></i> Διαγραφή Demo Data</h5>
                        <p class="text-muted small">Πληκτρολογήστε τη φράση επιβεβαίωσης <strong>DELETE DEMO DATA</strong> για να αφαιρέσετε τα demo records.</p>
                        <form action="/admin/settings/demo/delete" method="POST">
                            <?= \App\Core\Csrf::field() ?>
                            <div class="mb-3">
                                <input type="text" name="confirmation_phrase" class="form-control form-control-sm" required placeholder="DELETE DEMO DATA">
                            </div>
                            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash me-1"></i> Διαγραφή Demo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($activeTab === 'global_notifications'): ?>
        <div>
            <h4 class="text-white mb-2"><i class="fa-solid fa-envelope-circle-check text-primary me-2"></i> Global Email Notification Rules</h4>
            <p class="text-muted mb-4">Διαμορφώστε τους παγκόσμιους κανόνες ειδοποιήσεων συστήματος (π.χ. Δημιουργία Χρήστη, Reset Password κλπ).</p>

            <?php
            $db = \App\Core\Database::getInstance();
            $globalRules = $db->query("SELECT * FROM notification_templates ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <div class="row g-4">
                <div class="col-md-7">
                    <div class="card p-3 bg-dark border-secondary">
                        <h6 class="text-white mb-3">Λίστα Κανόνων Συστήματος</h6>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr class="text-white">
                                        <th>Κανόνας</th>
                                        <th>Slug</th>
                                        <th>Κατάσταση</th>
                                        <th class="text-end">Ενέργειες</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($globalRules as $rule): ?>
                                        <tr>
                                            <td class="text-white font-weight-bold"><?= htmlspecialchars($rule['name']) ?></td>
                                            <td class="text-muted small"><?= htmlspecialchars($rule['slug']) ?></td>
                                            <td>
                                                <span class="badge <?= $rule['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $rule['is_active'] ? 'Ενεργός' : 'Ανενεργός' ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-xs btn-outline-info edit-global-rule-btn" data-rule='<?= json_encode($rule, JSON_UNESCAPED_UNICODE) ?>'><i class="fa-solid fa-edit"></i> Edit</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card p-3 bg-dark border-secondary" id="globalRuleFormContainer">
                        <h6 class="text-white mb-3" id="globalRuleTitle"><i class="fa-solid fa-edit text-info me-2"></i>Επεξεργασία Κανόνα</h6>
                        <form action="/admin/settings/global-notifications/update" method="POST" id="globalRuleForm">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="id" id="globalRuleId">

                            <div class="mb-3">
                                <label class="form-label text-white small">Όνομα Κανόνα</label>
                                <input type="text" id="globalRuleName" class="form-control form-control-sm" readonly>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" name="is_active" id="globalRuleActive" class="form-check-input" value="1">
                                <label class="form-check-label text-white small" for="globalRuleActive">Ενεργός Κανόνας</label>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-white small">Θέμα (Subject Template)</label>
                                <input type="text" name="subject" id="globalRuleSubject" class="form-control form-control-sm" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-white small">Μήνυμα HTML</label>
                                <textarea name="body_html" id="globalRuleBodyHtml" class="form-control form-control-sm" rows="4" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-white small">Μήνυμα Text</label>
                                <textarea name="body_text" id="globalRuleBodyText" class="form-control form-control-sm" rows="3" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-save me-1"></i>Αποθήκευση Κανόνα</button>
                        </form>
                    </div>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', () => {
                const editBtns = document.querySelectorAll('.edit-global-rule-btn');
                const formId = document.getElementById('globalRuleId');
                const formName = document.getElementById('globalRuleName');
                const formActive = document.getElementById('globalRuleActive');
                const formSubject = document.getElementById('globalRuleSubject');
                const formBodyHtml = document.getElementById('globalRuleBodyHtml');
                const formBodyText = document.getElementById('globalRuleBodyText');

                editBtns.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const r = JSON.parse(btn.dataset.rule);
                        formId.value = r.id;
                        formName.value = r.name;
                        formActive.checked = parseInt(r.is_active) === 1;
                        formSubject.value = r.subject || '';
                        formBodyHtml.value = r.body_html || '';
                        formBodyText.value = r.body_text || '';
                    });
                });

                if (editBtns.length > 0) {
                    editBtns[0].click();
                }
            });
            </script>
        </div>

    <?php elseif ($activeTab === 'smtp'): ?>
        <div>
            <h4 class="text-white mb-2"><i class="fa-solid fa-envelope text-primary me-2"></i> Ρυθμίσεις SMTP & Email</h4>
            <p class="text-muted mb-4">Διαμόρφωση στοιχείων διακομιστή αλληλογραφίας.</p>

            <form action="/admin/settings/smtp/update" method="POST" class="mb-4" style="max-width: 600px;">
                <?= \App\Core\Csrf::field() ?>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label text-white small">SMTP Host</label>
                        <input type="text" name="host" class="form-control form-control-sm" value="<?= \App\Core\View::escape($smtp['host']) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-white small">SMTP Port</label>
                        <input type="number" name="port" class="form-control form-control-sm" value="<?= (int)$smtp['port'] ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small">Encryption</label>
                    <select name="encryption" class="form-select form-select-sm">
                        <option value="None" <?= $smtp['encryption'] === 'None' ? 'selected' : '' ?>>None</option>
                        <option value="STARTTLS" <?= $smtp['encryption'] === 'STARTTLS' ? 'selected' : '' ?>>STARTTLS</option>
                        <option value="SSL/TLS" <?= $smtp['encryption'] === 'SSL/TLS' ? 'selected' : '' ?>>SSL/TLS</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small">Username</label>
                    <input type="text" name="username" class="form-control form-control-sm" value="<?= \App\Core\View::escape($smtp['username']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small">Password</label>
                    <input type="password" name="password" class="form-control form-control-sm" value="••••••••" placeholder="Εισάγετε νέο κωδικό αν θέλετε να τον αλλάξετε">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-white small">From Email</label>
                        <input type="email" name="from_email" class="form-control form-control-sm" value="<?= \App\Core\View::escape($smtp['from_email']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-white small">From Name</label>
                        <input type="text" name="from_name" class="form-control form-control-sm" value="<?= \App\Core\View::escape($smtp['from_name']) ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="auth_enabled" id="auth_enabled" value="1" <?= $smtp['auth_enabled'] ? 'checked' : '' ?>>
                        <label class="form-check-label text-white small" for="auth_enabled">Ενεργοποίηση SMTP Authentication</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Αποθήκευση SMTP</button>
            </form>

            <hr class="border-secondary my-4">

            <div class="card p-4 border border-secondary bg-dark" style="max-width: 600px;">
                <h5 class="text-white"><i class="fa-solid fa-vial me-2 text-info"></i> Δοκιμαστική Αποστολή Email</h5>
                <form action="/admin/settings/smtp/test-email" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label text-white small">Διεύθυνση Παραλήπτη</label>
                        <input type="email" name="recipient" class="form-control form-control-sm" required placeholder="name@example.com">
                    </div>
                    <button type="submit" class="btn btn-outline-info btn-sm"><i class="fa-solid fa-paper-plane me-1"></i> Αποστολή Test Email</button>
                </form>
            </div>
        </div>

    <?php elseif ($activeTab === 'audit'): ?>
        <div>
            <h4 class="text-white mb-2"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Καταγραφές Ενεργειών (Audit History)</h4>
            <p class="text-muted mb-4">Ιστορικό διαχειριστικών ενεργειών και ρυθμίσεων του συστήματος.</p>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="text-white">
                            <th>Ενέργεια</th>
                            <th>Χρήστης</th>
                            <th>IP Διεύθυνση</th>
                            <th>Ημερομηνία</th>
                            <th>Metadata</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($audits)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Δεν βρέθηκαν καταγεγραμμένες διαχειριστικές ενέργειες.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($audits as $a): ?>
                                <tr>
                                    <td class="align-middle text-white font-weight-bold"><?= htmlspecialchars($a['action']) ?></td>
                                    <td class="align-middle text-muted"><?= htmlspecialchars($a['user_name']) ?></td>
                                    <td class="align-middle text-muted"><?= htmlspecialchars($a['ip_address']) ?></td>
                                    <td class="align-middle text-muted"><?= $a['created_at'] ?></td>
                                    <td class="align-middle text-muted small" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= htmlspecialchars($a['metadata_json']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($activeTab === 'restore'): ?>
        <div>
            <h4 class="text-white mb-2"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Κέντρο Επαναφοράς Συστήματος (Restore Center)</h4>
            <p class="text-muted mb-4">Εκτελέστε ασφαλή επαναφορά της βάσης, των αρχείων ή πλήρους συστήματος.</p>

            <div class="row">
                <!-- Step-by-Step Interactive Restore Wizard Panel -->
                <div class="col-md-6 mb-4">
                    <div class="card p-4 border border-secondary bg-dark">
                        <h5 class="text-warning"><i class="fa-solid fa-wand-magic-sparkles me-2"></i> Οδηγός Επαναφοράς</h5>
                        <form action="/admin/settings/restore/execute" method="POST" id="restore-wizard-form">
                            <?= \App\Core\Csrf::field() ?>

                            <div class="mb-3">
                                <label class="form-label text-white small">Βήμα 1: Επιλογή Backup</label>
                                <select class="form-select form-select-sm" name="backup_id" required>
                                    <option value="">-- Επιλέξτε Backup --</option>
                                    <?php foreach ($backups as $bk): ?>
                                        <option value="<?= (int)$bk['id'] ?>">
                                            <?= \App\Core\View::escape($bk['filename']) ?> (<?= htmlspecialchars($bk['backup_type']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-white small">Βήμα 2: Επιβεβαίωση Ασφαλείας</label>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="chk-integrity" required>
                                    <label class="form-check-label text-muted small" for="chk-integrity">
                                        Έλεγχος και επιβεβαίωση ακεραιότητας SHA256 (Υποχρεωτικό)
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="chk-backup" required>
                                    <label class="form-check-label text-muted small" for="chk-backup">
                                        Δημιουργία υποχρεωτικού Emergency Backup πριν την έναρξη (PRE-RESTORE)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="chk-maint" required>
                                    <label class="form-check-label text-muted small" for="chk-maint">
                                        Ενεργοποίηση λειτουργίας συντήρησης (Maintenance Mode)
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-warning w-100" onclick="return confirm('ΠΡΟΣΟΧΗ! Αυτή η ενέργεια θα αντικαταστήσει τα τρέχοντα δεδομένα. Θέλετε να συνεχίσετε;')">
                                <i class="fa-solid fa-play me-1"></i> Έναρξη Επαναφοράς
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card p-4 border border-secondary bg-dark">
                        <h5 class="text-white"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Ιστορικό Επαναφορών</h5>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr class="text-white">
                                        <th>Ημερομηνία</th>
                                        <th>Τύπος</th>
                                        <th>Κατάσταση</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $db = \App\Core\Database::getInstance();
                                    $histList = $db->query("SELECT * FROM system_restore_history ORDER BY id DESC LIMIT 10")->fetchAll();
                                    if (empty($histList)):
                                    ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-2">Δεν υπάρχουν εγγραφές ιστορικού.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($histList as $h): ?>
                                            <tr>
                                                <td class="text-muted"><?= $h['created_at'] ?></td>
                                                <td class="text-white-50"><?= htmlspecialchars($h['restore_type']) ?></td>
                                                <td>
                                                    <?php if ($h['status'] === 'completed'): ?>
                                                        <span class="badge bg-success">Ολοκληρώθηκε</span>
                                                    <?php elseif ($h['status'] === 'failed'): ?>
                                                        <span class="badge bg-danger">Απέτυχε</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?= htmlspecialchars($h['status']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($activeTab === 'cloud'): ?>
        <?php
        $tokensByProvider = [];
        if (isset($cloudTokens) && is_array($cloudTokens)) {
            foreach ($cloudTokens as $tok) {
                $tokensByProvider[$tok['provider']] = $tok;
            }
        }
        ?>
        <div>
            <!-- OAuth Credentials Configuration Form -->
            <div class="card p-4 mb-4 text-start" style="background:var(--color-surface);border-color:var(--color-border);">
                <h5 class="mb-3" style="color:var(--color-text);"><i class="fa-solid fa-key text-primary me-2"></i> Ρύθμιση OAuth Credentials</h5>
                <form action="/admin/settings/update" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="row">
                        <!-- Google Credentials -->
                        <div class="col-md-6 border-end" style="border-color:var(--color-border)!important;">
                            <h6 class="mb-3" style="color:var(--color-text);"><i class="fa-brands fa-google text-success me-2"></i> Google Drive Client API</h6>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Client ID</label>
                                <input type="text" class="form-control" name="google_client_id" value="<?= \App\Core\View::escape(\App\Models\SystemSetting::getVal('google_client_id') ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Client Secret</label>
                                <?php $googleSecret = \App\Models\SystemSetting::getVal('google_client_secret'); ?>
                                <input type="password" class="form-control" name="google_client_secret" value="<?= !empty($googleSecret) ? '[Κρυπτογραφημένο / Αμετάβλητο]' : '' ?>" placeholder="<?= !empty($googleSecret) ? '[Κρυπτογραφημένο / Αμετάβλητο]' : 'Εισάγετε Client Secret' ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Redirect URI</label>
                                <input type="text" class="form-control" name="google_redirect_uri" value="<?= \App\Core\View::escape(\App\Models\SystemSetting::getVal('google_redirect_uri') ?? '') ?>">
                            </div>
                        </div>

                        <!-- Microsoft Credentials -->
                        <div class="col-md-6 ps-md-4">
                            <h6 class="mb-3" style="color:var(--color-text);"><i class="fa-brands fa-windows text-info me-2"></i> Microsoft OneDrive API</h6>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Client ID</label>
                                <input type="text" class="form-control" name="onedrive_client_id" value="<?= \App\Core\View::escape(\App\Models\SystemSetting::getVal('onedrive_client_id') ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Client Secret</label>
                                <?php $onedriveSecret = \App\Models\SystemSetting::getVal('onedrive_client_secret'); ?>
                                <input type="password" class="form-control" name="onedrive_client_secret" value="<?= !empty($onedriveSecret) ? '[Κρυπτογραφημένο / Αμετάβλητο]' : '' ?>" placeholder="<?= !empty($onedriveSecret) ? '[Κρυπτογραφημένο / Αμετάβλητο]' : 'Εισάγετε Client Secret' ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Tenant ID</label>
                                <input type="text" class="form-control" name="onedrive_tenant_id" value="<?= \App\Core\View::escape(\App\Models\SystemSetting::getVal('onedrive_tenant_id') ?? 'common') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Redirect URI</label>
                                <input type="text" class="form-control" name="onedrive_redirect_uri" value="<?= \App\Core\View::escape(\App\Models\SystemSetting::getVal('onedrive_redirect_uri') ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2"><i class="fa-solid fa-floppy-disk me-1"></i> Αποθήκευση API Credentials</button>
                </form>
            </div>

            <!-- OAuth Cloud Connection Actions -->
            <div class="row mb-4">
                <!-- Microsoft OneDrive -->
                <div class="col-md-6 mb-3">
                    <div class="card p-4 text-center" style="background:var(--color-surface);border-color:var(--color-border);">
                        <i class="fa-brands fa-windows text-info mb-3" style="font-size: 3rem;"></i>
                        <h5 style="color:var(--color-text);">Microsoft OneDrive</h5>

                        <?php 
                        $hasOneDriveCreds = !empty(\App\Models\SystemSetting::getVal('onedrive_client_id')) && 
                                             !empty(\App\Models\SystemSetting::getVal('onedrive_client_secret')) && 
                                             !empty(\App\Models\SystemSetting::getVal('onedrive_redirect_uri'));
                        if (isset($tokensByProvider['onedrive'])):
                            $tok = $tokensByProvider['onedrive'];
                            $isExpired = strtotime($tok['expires_at']) < time();
                            $statusText = $isExpired ? 'Απαιτεί επανασύνδεση' : 'Συνδεδεμένο';
                            $statusColor = $isExpired ? 'text-warning' : 'text-success';
                        ?>
                            <div class="text-start mb-3 p-3 rounded border" style="background:var(--color-bg);border-color:var(--color-border);">
                                <p class="mb-1 text-muted small">Κατάσταση: <span class="<?= $statusColor ?> font-bold"><?= $statusText ?></span></p>
                                <p class="mb-1 text-muted small">Λογαριασμός: <span style="color:var(--color-text);"><?= htmlspecialchars($tok['connected_account'] ?? 'N/A') ?></span></p>
                                <p class="mb-1 text-muted small">Σύνδεση: <span style="color:var(--color-text);"><?= date('d/m/Y H:i', strtotime($tok['last_connected_at'])) ?></span></p>
                                <p class="mb-0 text-muted small">Φάκελος: <span style="color:var(--color-text);"><?= htmlspecialchars($tok['destination_folder'] ?? '/AppForm-Backups/') ?></span></p>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if ($hasOneDriveCreds): ?>
                                    <a href="/admin/settings/cloud/auth/onedrive" class="btn btn-sm btn-outline-warning flex-fill"><i class="fa-solid fa-arrows-rotate"></i> Επανασύνδεση</a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary flex-fill" disabled><i class="fa-solid fa-arrows-rotate"></i> Επανασύνδεση</button>
                                <?php endif; ?>
                                <form action="/admin/settings/cloud/test/onedrive" method="POST" class="flex-fill">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-info w-100"><i class="fa-solid fa-square-check"></i> Δοκιμή</button>
                                </form>
                                <form action="/admin/settings/cloud/disconnect/onedrive" method="POST" class="flex-fill">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Θέλετε να αποσυνδεθείτε από το OneDrive;')"><i class="fa-solid fa-link-slash"></i> Αποσύνδεση</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small">Συνδέστε το AppForm Enterprise με το Microsoft OneDrive λογαριασμό σας.</p>
                            <?php if ($hasOneDriveCreds): ?>
                                <a href="/admin/settings/cloud/auth/onedrive" class="btn btn-outline-info w-100"><i class="fa-solid fa-link me-1"></i> Σύνδεση με OneDrive</a>
                            <?php else: ?>
                                <div class="alert alert-warning py-2 mb-0 text-start small"><i class="fa-solid fa-triangle-exclamation me-1"></i> Δεν έχουν ρυθμιστεί OAuth credentials για τον συγκεκριμένο provider.</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Google Drive -->
                <div class="col-md-6 mb-3">
                    <div class="card p-4 text-center" style="background:var(--color-surface);border-color:var(--color-border);">
                        <i class="fa-brands fa-google text-success mb-3" style="font-size: 3rem;"></i>
                        <h5 style="color:var(--color-text);">Google Drive</h5>

                        <?php 
                        $hasGoogleCreds = !empty(\App\Models\SystemSetting::getVal('google_client_id')) && 
                                           !empty(\App\Models\SystemSetting::getVal('google_client_secret')) && 
                                           !empty(\App\Models\SystemSetting::getVal('google_redirect_uri'));
                        if (isset($tokensByProvider['googledrive'])):
                            $tok = $tokensByProvider['googledrive'];
                            $isExpired = strtotime($tok['expires_at']) < time();
                            $statusText = $isExpired ? 'Απαιτεί επανασύνδεση' : 'Συνδεδεμένο';
                            $statusColor = $isExpired ? 'text-warning' : 'text-success';
                        ?>
                            <div class="text-start mb-3 p-3 rounded border" style="background:var(--color-bg);border-color:var(--color-border);">
                                <p class="mb-1 text-muted small">Κατάσταση: <span class="<?= $statusColor ?> font-bold"><?= $statusText ?></span></p>
                                <p class="mb-1 text-muted small">Λογαριασμός: <span style="color:var(--color-text);"><?= htmlspecialchars($tok['connected_account'] ?? 'N/A') ?></span></p>
                                <p class="mb-1 text-muted small">Σύνδεση: <span style="color:var(--color-text);"><?= date('d/m/Y H:i', strtotime($tok['last_connected_at'])) ?></span></p>
                                <p class="mb-0 text-muted small">Φάκελος: <span style="color:var(--color-text);"><?= htmlspecialchars($tok['destination_folder'] ?? '/AppForm-Backups/') ?></span></p>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if ($hasGoogleCreds): ?>
                                    <a href="/admin/settings/cloud/auth/googledrive" class="btn btn-sm btn-outline-warning flex-fill"><i class="fa-solid fa-arrows-rotate"></i> Επανασύνδεση</a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary flex-fill" disabled><i class="fa-solid fa-arrows-rotate"></i> Επανασύνδεση</button>
                                <?php endif; ?>
                                <form action="/admin/settings/cloud/test/googledrive" method="POST" class="flex-fill">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-info w-100"><i class="fa-solid fa-square-check"></i> Δοκιμή</button>
                                </form>
                                <form action="/admin/settings/cloud/disconnect/googledrive" method="POST" class="flex-fill">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Θέλετε να αποσυνδεθείτε από το Google Drive;')"><i class="fa-solid fa-link-slash"></i> Αποσύνδεση</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small">Συνδέστε το AppForm Enterprise με το Google Drive workspace σας.</p>
                            <?php if ($hasGoogleCreds): ?>
                                <a href="/admin/settings/cloud/auth/googledrive" class="btn btn-outline-success w-100"><i class="fa-solid fa-link me-1"></i> Σύνδεση με Google Drive</a>
                            <?php else: ?>
                                <div class="alert alert-warning py-2 mb-0 text-start small"><i class="fa-solid fa-triangle-exclamation me-1"></i> Δεν έχουν ρυθμιστεί OAuth credentials για τον συγκεκριμένο provider.</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Cloud Backups Replication Table -->
            <div class="table-responsive p-3 rounded border" style="background:var(--color-surface);border-color:var(--color-border);">
                <h6 class="mb-3" style="color:var(--color-text);"><i class="fa-solid fa-cloud-arrow-up me-2 text-warning"></i> Ιστορικό Συγχρονισμού Backup</h6>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="color:var(--color-text);">Όνομα Αρχείου</th>
                            <th style="color:var(--color-text);">Provider</th>
                            <th style="color:var(--color-text);">Ταχύτητα</th>
                            <th style="color:var(--color-text);">Κατάσταση Cloud</th>
                            <th style="color:var(--color-text);">Ενέργειες</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($backups)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Δεν βρέθηκαν τοπικά backup για συγχρονισμό.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($backups as $bk): ?>
                                <tr>
                                    <td class="align-middle" style="color:var(--color-text);"><?= \App\Core\View::escape($bk['filename']) ?></td>
                                    <td class="align-middle text-muted"><?= htmlspecialchars($bk['cloud_provider'] ?: 'None') ?></td>
                                    <td class="align-middle text-muted"><?= $bk['upload_speed_kbps'] ? $bk['upload_speed_kbps'] . ' KB/s' : '-' ?></td>
                                    <td class="align-middle">
                                        <?php if ($bk['cloud_status'] === 'uploaded'): ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-cloud-check me-1"></i> Uploaded</span>
                                        <?php elseif ($bk['cloud_status'] === 'uploading'): ?>
                                            <span class="badge bg-warning text-dark"><i class="fa-solid fa-spinner fa-spin me-1"></i> Uploading</span>
                                        <?php elseif ($bk['cloud_status'] === 'failed'): ?>
                                            <span class="badge bg-danger" style="cursor: pointer;" onclick="alert('Σφάλμα: <?= \App\Core\View::escape($bk['cloud_error'] ?? 'Άγνωστο σφάλμα συγχρονισμού.') ?>')"><i class="fa-solid fa-triangle-exclamation me-1"></i> Failed (Προβολή Σφάλματος)</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <?php
                                        $hasConnected = !empty($tokensByProvider);
                                        ?>
                                        <form action="/admin/settings/cloud/sync/<?= (int)$bk['id'] ?>" method="POST" class="d-inline">
                                            <?= \App\Core\Csrf::field() ?>
                                            <select name="provider" class="form-select form-select-sm d-inline-block w-auto me-2" <?= !$hasConnected ? 'disabled' : '' ?> required>
                                                <?php if (isset($tokensByProvider['googledrive'])): ?>
                                                    <option value="googledrive">Google Drive</option>
                                                <?php endif; ?>
                                                <?php if (isset($tokensByProvider['onedrive'])): ?>
                                                    <option value="onedrive">OneDrive</option>
                                                <?php endif; ?>
                                                <?php if (!$hasConnected): ?>
                                                    <option value="">(Απαιτείται σύνδεση)</option>
                                                <?php endif; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary" <?= !$hasConnected ? 'disabled title="Συνδέστε πρώτα έναν Cloud Provider"' : '' ?>>
                                                <i class="fa-solid fa-sync"></i> <?= $bk['cloud_status'] === 'failed' ? 'Επανάληψη' : 'Αποστολή στο Cloud' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($activeTab === 'queue'): ?>
        <div>
            <h4 class="text-white mb-2"><i class="fa-solid fa-list-check text-primary me-2"></i> Κεντρική Ουρά Εργασιών (Enterprise Background Job Queue)</h4>
            <p class="text-muted mb-4">Διαχείριση και παρακολούθηση της κατάστασης εκτέλεσης background εργασιών (email, PDF, excel, backups).</p>

            <!-- Dispatch Mock Job Actions -->
            <div class="card p-3 mb-4 bg-dark border-secondary">
                <h6 class="text-white mb-3"><i class="fa-solid fa-paper-plane me-2 text-info"></i> Dispatch Test Queue Jobs</h6>
                <div class="d-flex gap-2">
                    <form action="/admin/settings/queue/dispatch-test" method="POST">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="job_type" value="email">
                        <button type="submit" class="btn btn-outline-info btn-sm"><i class="fa-solid fa-envelope me-1"></i> SendEmailJob</button>
                    </form>
                    <form action="/admin/settings/queue/dispatch-test" method="POST">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="job_type" value="pdf">
                        <button type="submit" class="btn btn-outline-warning btn-sm"><i class="fa-solid fa-file-pdf me-1"></i> GeneratePdfJob</button>
                    </form>
                    <form action="/admin/settings/queue/dispatch-test" method="POST">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="job_type" value="excel">
                        <button type="submit" class="btn btn-outline-success btn-sm"><i class="fa-solid fa-file-excel me-1"></i> GenerateExcelJob</button>
                    </form>
                </div>
            </div>

            <div class="row">
                <!-- Workers Health Monitor -->
                <div class="col-md-4 mb-4">
                    <div class="card p-3 bg-dark border-secondary">
                        <h6 class="text-white mb-3"><i class="fa-solid fa-microchip me-2 text-success"></i> Background Workers</h6>
                        <ul class="list-group list-group-flush bg-transparent">
                            <?php if (empty($workers)): ?>
                                <li class="list-group-item bg-transparent text-muted small py-2">Κανένας ενεργός worker στο σύστημα.</li>
                            <?php else: ?>
                                <?php foreach ($workers as $w): ?>
                                    <li class="list-group-item bg-transparent text-white-50 small d-flex justify-content-between align-items-center py-2 px-0">
                                        <div>
                                            <strong>PID: <?= (int)$w['pid'] ?></strong> (<?= htmlspecialchars($w['hostname']) ?>)<br>
                                            <span class="text-muted">Queues: <?= htmlspecialchars($w['queue_list']) ?></span>
                                        </div>
                                        <span class="badge bg-success"><?= htmlspecialchars($w['status']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Jobs Queue Execution History -->
                <div class="col-md-8 mb-4">
                    <div class="card p-3 bg-dark border-secondary">
                        <h6 class="text-white mb-3"><i class="fa-solid fa-clock-rotate-left me-2 text-warning"></i> Ιστορικό Εργασιών (Jobs Queue)</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr class="text-white">
                                        <th>ID</th>
                                        <th>Τύπος</th>
                                        <th>Ουρά</th>
                                        <th>Κατάσταση</th>
                                        <th>Progress</th>
                                        <th>Ενέργειες</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($jobs)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">Δεν βρέθηκαν καταγεγραμμένες εργασίες στην ουρά.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($jobs as $j): ?>
                                            <tr>
                                                <td class="align-middle text-muted"><?= (int)$j['id'] ?></td>
                                                <td class="align-middle text-white small"><?= htmlspecialchars(basename(str_replace('\\', '/', $j['type']))) ?></td>
                                                <td class="align-middle text-muted small"><?= htmlspecialchars($j['queue']) ?></td>
                                                <td class="align-middle">
                                                    <?php if ($j['status'] === 'completed'): ?>
                                                        <span class="badge bg-success">Completed</span>
                                                    <?php elseif ($j['status'] === 'running'): ?>
                                                        <span class="badge bg-warning text-dark">Running</span>
                                                    <?php elseif ($j['status'] === 'failed'): ?>
                                                        <span class="badge bg-danger" title="<?= \App\Core\View::escape($j['last_error']) ?>">Failed</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?= htmlspecialchars($j['status']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="align-middle">
                                                    <div class="progress" style="height: 5px; width: 60px;">
                                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?= (int)$j['progress'] ?>%" aria-valuenow="<?= (int)$j['progress'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <small class="text-muted small"><?= (int)$j['progress'] ?>%</small>
                                                </td>
                                                <td class="align-middle">
                                                    <?php if ($j['status'] === 'pending' || $j['status'] === 'scheduled'): ?>
                                                        <form action="/admin/settings/queue/<?= (int)$j['id'] ?>/cancel" method="POST" class="d-inline">
                                                            <?= \App\Core\Csrf::field() ?>
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Ακύρωση"><i class="fa-solid fa-ban"></i></button>
                                                        </form>
                                                    <?php elseif ($j['status'] === 'failed'): ?>
                                                        <form action="/admin/settings/queue/<?= (int)$j['id'] ?>/retry" method="POST" class="d-inline">
                                                            <?= \App\Core\Csrf::field() ?>
                                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Επανεκτέλεση"><i class="fa-solid fa-arrow-rotate-right"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($activeTab === 'plugins'): ?>
        <div>
            <h4 class="text-white mb-2"><i class="fa-solid fa-puzzle-piece text-primary me-2"></i> Διαχείριση Πρόσθετων (Plugins & Extensions)</h4>
            <p class="text-muted mb-4">Εγκατάσταση, ενεργοποίηση και απενεργοποίηση πρόσθετων ενοτήτων λειτουργίας.</p>

            <!-- Install Mock Plugin Package -->
            <div class="card p-3 mb-4 bg-dark border-secondary">
                <h6 class="text-white mb-3"><i class="fa-solid fa-cloud-arrow-up me-2 text-info"></i> Install New Plugin Package (.zip)</h6>
                <form action="/admin/settings/plugins/install" method="POST" enctype="multipart/form-data">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="input-group">
                        <input type="file" name="plugin_zip" class="form-control form-control-sm bg-dark text-white border-secondary" required>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-upload"></i> Upload & Install</button>
                    </div>
                </form>
            </div>

            <!-- Installed Plugins Grid Lists -->
            <div class="table-responsive bg-dark border border-secondary rounded p-3">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr class="text-white">
                            <th>Πρόσθετο</th>
                            <th>Κλειδί</th>
                            <th>Έκδοση</th>
                            <th>Κατάσταση</th>
                            <th>Εγκαταστάθηκε</th>
                            <th>Ενέργειες</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($plugins)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Δεν βρέθηκαν εγκατεστημένα πρόσθετα.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($plugins as $p): ?>
                                <tr>
                                    <td class="align-middle text-white fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                                    <td class="align-middle text-muted small"><?= htmlspecialchars($p['plugin_key']) ?></td>
                                    <td class="align-middle text-white small"><?= htmlspecialchars($p['installed_version']) ?></td>
                                    <td class="align-middle">
                                        <?php if ($p['status'] === 'enabled'): ?>
                                            <span class="badge bg-success">Enabled</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle text-muted small"><?= htmlspecialchars($p['installed_at']) ?></td>
                                    <td class="align-middle">
                                        <?php if ($p['status'] === 'enabled'): ?>
                                            <form action="/admin/settings/plugins/<?= htmlspecialchars($p['plugin_key']) ?>/disable" method="POST" class="d-inline">
                                                <?= \App\Core\Csrf::field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-warning">Disable</button>
                                            </form>
                                        <?php else: ?>
                                            <form action="/admin/settings/plugins/<?= htmlspecialchars($p['plugin_key']) ?>/enable" method="POST" class="d-inline">
                                                <?= \App\Core\Csrf::field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-success">Enable</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
    <?php elseif ($activeTab === 'updates'): ?>
        <div>
            <h4 class="text-white mb-2"><i class="fa-solid fa-cloud-arrow-down text-primary me-2"></i> Αναβάθμιση & Ενημέρωση Συστήματος</h4>
            <p class="text-muted mb-4">Διαχείριση εκδόσεων λογισμικού, λήψη νέων releases και εκτέλεση incremental updates.</p>

            <div class="row">
                <!-- Current Version Metadata Info -->
                <div class="col-md-4 mb-4">
                    <div class="card p-3 h-100" style="background:var(--color-surface);border-color:var(--color-border);">
                        <h6 class="border-bottom pb-2" style="color:var(--color-text);border-color:var(--color-border)!important;"><i class="fa-solid fa-circle-info me-2 text-info"></i> Τρέχουσα Έκδοση</h6>
                        <table class="table table-sm table-borderless text-muted mb-0">
                            <tr>
                                <td>Έκδοση (Version):</td>
                                <td style="color:var(--color-text);font-weight:600;"><?= htmlspecialchars($verData['version'] ?? '1.0.0') ?></td>
                            </tr>
                            <tr>
                                <td>Αριθμός Build:</td>
                                <td style="color:var(--color-text);"><?= htmlspecialchars($verData['build'] ?? 1) ?></td>
                            </tr>
                            <tr>
                                <td>Κανάλι (Channel):</td>
                                <td style="color:var(--color-text);"><span class="badge bg-secondary"><?= htmlspecialchars($verData['channel'] ?? 'stable') ?></span></td>
                            </tr>
                        </table>
                        <div class="mt-4 d-grid gap-2">
                            <button id="btn-check-updates" class="btn btn-primary btn-sm"><i class="fa-solid fa-arrows-rotate me-1"></i> Έλεγχος για Ενημερώσεις</button>
                            <a href="/admin/settings/updates/logs/download" class="btn btn-outline-info btn-sm"><i class="fa-solid fa-download me-1"></i> Διαγνωστικά Logs</a>
                        </div>
                    </div>
                </div>

                <!-- Update State / Available Upgrades Panel -->
                <div class="col-md-8 mb-4">
                    <div class="card p-3 h-100" style="background:var(--color-surface);border-color:var(--color-border);">
                        <h6 class="border-bottom pb-2" style="color:var(--color-text);border-color:var(--color-border)!important;"><i class="fa-solid fa-bullseye me-2 text-success"></i> Διαθέσιμη Αναβάθμιση</h6>
                        <div id="no-updates-alert" class="alert alert-secondary mb-0">
                            Πατήστε "Έλεγχος για Ενημερώσεις" για να αναζητήσετε διαθέσιμα πακέτα.
                        </div>
                        <div id="update-details" style="display:none;">
                            <div class="alert alert-success">
                                <h6 class="alert-heading fw-bold mb-1" style="color:var(--color-text);"><i class="fa-solid fa-circle-check me-2"></i> Βρέθηκε Νέα Έκδοση: <span id="lbl-target-version"></span></h6>
                                <p class="small mb-0">Το πακέτο είναι συμβατό με τις τρέχουσες απαιτήσεις συστήματος.</p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small">Release Notes / Changelog:</label>
                                <div id="lbl-changelog" class="p-2 border rounded small" style="max-height: 120px; overflow-y: auto; background:var(--color-bg); border-color:var(--color-border)!important; color:var(--color-text-muted);"></div>
                            </div>
                            <?php if (\App\Core\Auth::hasPermission('updates.manage')): ?>
                            <form id="form-start-update" action="/admin/settings/updates/start" method="POST">
                                <?= \App\Core\Csrf::field() ?>
                                <input type="hidden" name="target_version" id="inp-target-version">
                                <input type="hidden" name="build_number" id="inp-build-number">
                                <input type="hidden" name="channel" id="inp-channel">
                                <button type="submit" class="btn btn-success btn-sm"><i class="fa-solid fa-play me-1"></i> Έναρξη Διαδικασίας Αναβάθμισης</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Execution Console Log / Real-time Progress Bar -->
            <div id="update-progress-card" class="card p-3 mb-4" style="display:none;background:var(--color-surface);border-color:var(--color-border);">
                <h6 class="mb-3" style="color:var(--color-text);"><i class="fa-solid fa-gear fa-spin me-2 text-warning"></i> Πρόοδος Εγκατάστασης</h6>
                <div class="progress mb-3 bg-secondary" style="height: 20px;">
                    <div id="update-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;">0%</div>
                </div>
                <div class="d-flex justify-content-between mb-3 text-muted small">
                    <div>Τρέχον Βήμα: <span id="lbl-current-step" class="fw-bold" style="color:var(--color-text);">-</span></div>
                    <div>Heartbeat: <span id="lbl-heartbeat" style="color:var(--color-text);">-</span></div>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">Logs Εγκατάστασης:</label>
                    <pre id="update-console-log" class="p-3 border rounded small mb-0" style="max-height: 250px; overflow-y: auto; font-family: monospace; background:var(--color-bg); border-color:var(--color-border)!important; color:var(--color-success, #28a745);"></pre>
                </div>
                <div class="d-flex gap-2">
                    <?php if (\App\Core\Auth::hasPermission('updates.rollback')): ?>
                    <button id="btn-rollback" class="btn btn-danger btn-sm" style="display:none;"><i class="fa-solid fa-clock-rotate-left me-1"></i> Rollback</button>
                    <?php endif; ?>
                    <?php if (\App\Core\Auth::hasPermission('updates.manage')): ?>
                    <button id="btn-unlock" class="btn btn-outline-warning btn-sm" style="display:none;"><i class="fa-solid fa-lock-open me-1"></i> Force Unlock</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnCheck = document.getElementById('btn-check-updates');
            const alertNo = document.getElementById('no-updates-alert');
            const panelDetails = document.getElementById('update-details');
            const lblVersion = document.getElementById('lbl-target-version');
            const lblChangelog = document.getElementById('lbl-changelog');
            const formStart = document.getElementById('form-start-update');

            const inpVersion = document.getElementById('inp-target-version');
            const inpBuild = document.getElementById('inp-build-number');
            const inpChannel = document.getElementById('inp-channel');

            const progressCard = document.getElementById('update-progress-card');
            const progressBar = document.getElementById('update-progress-bar');
            const lblStep = document.getElementById('lbl-current-step');
            const lblHeartbeat = document.getElementById('lbl-heartbeat');
            const consoleLog = document.getElementById('update-console-log');

            const btnRollback = document.getElementById('btn-rollback');
            const btnUnlock = document.getElementById('btn-unlock');

            let pollInterval = null;

            // Check Updates Trigger
            btnCheck.addEventListener('click', function() {
                btnCheck.disabled = true;
                btnCheck.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Έλεγχος...';

                fetch('/admin/settings/updates/check', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: '_token=' + encodeURIComponent('<?= \App\Core\Csrf::token() ?>')
                })
                .then(res => res.json())
                .then(data => {
                    btnCheck.disabled = false;
                    btnCheck.innerHTML = '<i class="fa-solid fa-arrows-rotate me-1"></i> Έλεγχος για Ενημερώσεις';

                    if (data.success && data.latest) {
                        alertNo.style.display = 'none';
                        panelDetails.style.display = 'block';
                        lblVersion.innerText = data.latest.version + ' (Build: ' + data.latest.build + ')';
                        lblChangelog.innerText = data.latest.changelog || 'Δεν υπάρχουν διαθέσιμες σημειώσεις.';

                        inpVersion.value = data.latest.version;
                        inpBuild.value = data.latest.build;
                        inpChannel.value = data.latest.channel || 'stable';
                    } else {
                        alertNo.innerText = 'Δεν βρέθηκαν διαθέσιμες ενημερώσεις. Η εφαρμογή είναι ενημερωμένη.';
                        alertNo.style.display = 'block';
                        panelDetails.style.display = 'none';
                    }
                })
                .catch(() => {
                    btnCheck.disabled = false;
                    btnCheck.innerHTML = '<i class="fa-solid fa-arrows-rotate me-1"></i> Έλεγχος για Ενημερώσεις';
                });
            });

            // Start Update Trigger
            if (formStart) {
                formStart.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (!confirm('ΠΡΟΣΟΧΗ: Η αναβάθμιση θα θέσει την εφαρμογή σε Maintenance Mode. Είστε σίγουροι ότι θέλετε να ξεκινήσετε;')) {
                        return;
                    }

                    const fd = new FormData(formStart);
                    const params = new URLSearchParams(fd);

                    fetch('/admin/settings/updates/start', {
                        method: 'POST',
                        body: params
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            progressCard.style.display = 'block';
                            startPolling();
                        } else {
                            alert('Αποτυχία εκκίνησης αναβάθμισης.');
                        }
                    });
                });
            }

            function startPolling() {
                if (pollInterval) clearInterval(pollInterval);
                pollInterval = setInterval(pollStatus, 1500);
                pollStatus();
            }

            function pollStatus() {
                fetch('/admin/settings/updates/status')
                .then(res => res.json())
                .then(data => {
                    const ACTIVE_STATES = [
                        'pending','waiting_for_lock','maintenance_enabled',
                        'backing_up_files','backing_up_database','verifying_package',
                        'extracting','running_migrations','validating_application'
                    ];
                    const status = data.status || 'idle';
                    const pct    = data.progress_percent || 0;

                    if (ACTIVE_STATES.includes(status)) {
                        // ── In-progress: show card, keep polling ────────────────
                        progressCard.style.display = 'block';
                        progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-success';
                        progressBar.style.width = pct + '%';
                        progressBar.innerText   = pct + '%';
                        lblStep.innerText       = data.current_step || '-';
                        lblHeartbeat.innerText  = data.heartbeat_at || '-';
                        btnRollback.style.display = 'none';
                        btnUnlock.style.display   = 'none';

                    } else if (status === 'completed') {
                        // ── Completed: 100%, green, stop polling ────────────────
                        progressCard.style.display = 'block';
                        progressBar.className = 'progress-bar bg-success';
                        progressBar.style.width = '100%';
                        progressBar.innerText   = '100%';
                        lblStep.innerText       = 'completed';
                        lblHeartbeat.innerText  = data.heartbeat_at || '-';
                        btnRollback.style.display = 'none';
                        btnUnlock.style.display   = 'none';
                        clearInterval(pollInterval);

                    } else if (status === 'failed' || status === 'rollback_failed') {
                        // ── Failed: red bar, show actions, stop polling ─────────
                        progressCard.style.display = 'block';
                        progressBar.className = 'progress-bar bg-danger';
                        progressBar.style.width = pct + '%';
                        progressBar.innerText   = 'ΑΠΟΤΥΧΙΑ (' + pct + '%)';
                        lblStep.innerText       = data.current_step || 'error';
                        lblHeartbeat.innerText  = data.heartbeat_at || '-';
                        btnRollback.style.display = 'inline-block';
                        btnUnlock.style.display   = 'inline-block';
                        clearInterval(pollInterval);

                    } else {
                        // ── Idle / no active update: hide card, stop polling ────
                        progressCard.style.display = 'none';
                        clearInterval(pollInterval);
                    }

                    // Build log display (for any non-idle state)
                    if (data.logs && progressCard.style.display !== 'none') {
                        let logText = '';
                        data.logs.forEach(l => {
                            logText += '[' + l.created_at + '] [' + l.level + '] ' + l.message + '\n';
                        });
                        consoleLog.innerText = logText;
                        consoleLog.scrollTop = consoleLog.scrollHeight;
                    }
                })
                .catch(() => {
                    // On fetch error: silently stop polling
                    clearInterval(pollInterval);
                });
            }

            // Force Unlock
            btnUnlock.addEventListener('click', function() {
                if (confirm('Είστε σίγουροι ότι θέλετε να ξεκλειδώσετε χειροκίνητα τη διαδικασία;')) {
                    fetch('/admin/settings/updates/force-release', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: '_token=' + encodeURIComponent('<?= \App\Core\Csrf::token() ?>')
                    }).then(() => pollStatus());
                }
            });

            // Rollback
            btnRollback.addEventListener('click', function() {
                if (confirm('Είστε σίγουροι ότι θέλετε να εκτελέσετε Rollback στην προηγούμενη έκδοση;')) {
                    fetch('/admin/settings/updates/rollback', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: '_token=' + encodeURIComponent('<?= \App\Core\Csrf::token() ?>')
                    }).then(() => pollStatus());
                }
            });

            // Initial Polling Check
            pollStatus();
        });
        </script>
    <?php endif; ?>
</div>
<?php htmlspecialchars(''); // Keep trailing placeholder ?>
