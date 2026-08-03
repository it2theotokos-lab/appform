<?php
// $tab may be set by the controller (e.g. showUpdates() passes 'tab'=>'updates')
// Fallback to ?tab= query param for tab-based pages, then default to 'general'
$activeTab = $tab ?? $_GET['tab'] ?? 'general';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h1 class="h3 m-0 text-white"><i class="fa-solid fa-gears me-2 text-primary"></i> <?= __('System Management Center') ?></h1>
        <p class="text-muted m-0"><?= __('Configure general parameters, backups, SMTP settings and demo data.') ?></p>
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
            <h4 class="text-white mb-2"><i class="fa-solid fa-sliders text-primary me-2"></i> <?= __('General Settings') ?></h4>
            <p class="text-muted mb-4"><?= __('Configure basic portal operation parameters.') ?></p>

            <form action="/admin/settings/update" method="POST" style="max-width: 600px;">
                <?= \App\Core\Csrf::field() ?>
                <?php foreach ($settings as $set): ?>
                    <?php if ($set['setting_key'] === 'app_logo_path') continue; // rendered separately ?>
                    <div class="mb-3">
                        <label for="<?= $set['setting_key'] ?>" class="form-label text-white text-capitalize">
                            <?php
                            $label = $set['setting_key'];
                            if ($label === 'app_name') $label = __('Application Name');
                            elseif ($label === 'csv_delimiter') $label = __('CSV Delimiter');
                            elseif ($label === 'default_locale') $label = __('Default Language');
                            elseif ($label === 'records_per_page') $label = __('Records Per Page');
                            elseif ($label === 'upload_max_filesize') $label = __('Max File Size (MB)');
                            else $label = htmlspecialchars($label);
                            echo $label;
                            ?>
                        </label>
                        <input type="text" class="form-control" id="<?= $set['setting_key'] ?>" name="<?= $set['setting_key'] ?>" value="<?= \App\Core\View::escape($set['setting_value']) ?>">
                        <small class="text-muted"><?= __('Enter the parameter value for key') ?> <?= htmlspecialchars($set['setting_key']) ?>.</small>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary mt-3"><i class="fa-solid fa-save me-1"></i> <?= __('Save') ?></button>
            </form>

            <!-- ── Application Logo ──────────────────────────────────────── -->
            <?php
            $logoPath = '';
            foreach ($settings as $set) {
                if ($set['setting_key'] === 'app_logo_path') {
                    $logoPath = $set['setting_value'] ?? '';
                    break;
                }
            }
            $hasLogo = !empty($logoPath) && is_file(dirname(__DIR__, 3) . '/public/' . ltrim($logoPath, '/'));
            ?>
            <hr class="border-secondary my-4">
            <h5 class="text-white mb-1"><i class="fa-solid fa-image text-primary me-2"></i> <?= __('Application Logo') ?></h5>
            <p class="text-muted small mb-3"><?= __('Upload a custom logo (JPG, PNG, WEBP · max 2 MB). The current default logo is used as fallback.') ?></p>

            <div class="row g-4 align-items-start" style="max-width:700px;">
                <!-- Current logo preview -->
                <div class="col-auto">
                    <div class="border border-secondary rounded p-2 bg-dark text-center" style="min-width:140px;">
                        <p class="text-muted small mb-2"><?= __('Current Logo') ?></p>
                        <?php if ($hasLogo): ?>
                            <img src="/<?= htmlspecialchars($logoPath) ?>?v=<?= time() ?>"
                                 alt="<?= __('Current Logo') ?>"
                                 style="max-height:60px;max-width:120px;object-fit:contain;">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center" style="height:60px;width:120px;margin:0 auto;">
                                <i class="fa-solid fa-cubes-stacked fa-2x text-primary"></i>
                            </div>
                            <small class="text-muted d-block mt-1"><?= __('No custom logo is set.') ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upload form -->
                <div class="col">
                    <form action="/admin/settings/logo/upload" method="POST" enctype="multipart/form-data" id="logo-upload-form">
                        <?= \App\Core\Csrf::field() ?>
                        <label class="form-label text-white"><?= __('Upload New Logo') ?></label>
                        <div class="input-group">
                            <input type="file" class="form-control" name="logo_file" id="logo_file"
                                   accept=".jpg,.jpeg,.png,.webp"
                                   aria-label="<?= __('Upload New Logo') ?>">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fa-solid fa-upload me-1"></i> <?= __('Upload') ?>
                            </button>
                        </div>
                        <small class="text-muted">JPG, PNG, WEBP &mdash; max 2 MB</small>
                    </form>

                    <?php if ($hasLogo): ?>
                        <form action="/admin/settings/logo/delete" method="POST" class="mt-3"
                              onsubmit="return confirm('<?= htmlspecialchars(__('Are you sure you want to delete the custom logo and revert to the default?')) ?>')">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fa-solid fa-trash me-1"></i> <?= __('Delete Logo') ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Application Favicon ───────────────────────────────────── -->
            <?php
            $faviconPath = '';
            foreach ($settings as $set) {
                if ($set['setting_key'] === 'app_favicon_path') {
                    $faviconPath = $set['setting_value'] ?? '';
                    break;
                }
            }
            $hasFavicon = !empty($faviconPath) && is_file(dirname(__DIR__, 3) . '/public/' . ltrim($faviconPath, '/'));
            ?>
            <hr class="border-secondary my-4">
            <h5 class="text-white mb-1"><i class="fa-solid fa-icons text-primary me-2"></i> <?= __('Application Favicon') ?></h5>
            <p class="text-muted small mb-3"><?= __('Upload a custom favicon (ICO, PNG, WEBP · max 2 MB). The default favicon is used as fallback.') ?></p>

            <div class="row g-4 align-items-start" style="max-width:700px;">
                <!-- Current favicon preview -->
                <div class="col-auto">
                    <div class="border border-secondary rounded p-2 bg-dark text-center" style="min-width:140px;">
                        <p class="text-muted small mb-2"><?= __('Current Favicon') ?></p>
                        <?php if ($hasFavicon): ?>
                            <img src="/<?= htmlspecialchars($faviconPath) ?>?v=<?= time() ?>"
                                 alt="<?= __('Current Favicon') ?>"
                                 style="max-height:48px;max-width:48px;object-fit:contain;">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center" style="height:48px;width:48px;margin:0 auto;">
                                <i class="fa-solid fa-icons fa-2x text-primary"></i>
                            </div>
                            <small class="text-muted d-block mt-1"><?= __('Default Favicon') ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Favicon upload form -->
                <div class="col">
                    <form action="/admin/settings/favicon/upload" method="POST" enctype="multipart/form-data" id="favicon-upload-form">
                        <?= \App\Core\Csrf::field() ?>
                        <label class="form-label text-white"><?= __('Upload New Favicon') ?></label>
                        <div class="input-group">
                            <input type="file" class="form-control" name="favicon_file" id="favicon_file"
                                   accept=".ico,.png,.webp"
                                   aria-label="<?= __('Upload New Favicon') ?>">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fa-solid fa-upload me-1"></i> <?= __('Upload') ?>
                            </button>
                        </div>
                        <small class="text-muted">ICO, PNG, WEBP &mdash; max 2 MB</small>
                    </form>

                    <?php if ($hasFavicon): ?>
                        <form action="/admin/settings/favicon/delete" method="POST" class="mt-3"
                              onsubmit="return confirm('<?= htmlspecialchars(__('Are you sure you want to delete the custom favicon and revert to the default?')) ?>')">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fa-solid fa-trash me-1"></i> <?= __('Delete Favicon') ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>


    <?php elseif ($activeTab === 'backup'): ?>
        <div>
            <h4 class="text-white mb-2"><i class="fa-solid fa-database text-primary me-2"></i> <?= __('Backup Management') ?></h4>
            <p class="text-muted mb-4"><?= __('Create manual local backups of the database and stored files.') ?></p>

            <div class="d-flex gap-2 mb-4">
                <form action="/admin/settings/backup/create" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="backup_type" value="database">
                    <button type="submit" class="btn btn-outline-success"><i class="fa-solid fa-file-excel me-1"></i> <?= __('Create Database Backup') ?></button>
                </form>
                <form action="/admin/settings/backup/create" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="backup_type" value="files">
                    <button type="submit" class="btn btn-outline-info"><i class="fa-solid fa-file-zipper me-1"></i> <?= __('Create Files Backup') ?></button>
                </form>
                <form action="/admin/settings/backup/create" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="backup_type" value="full">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-box-archive me-1"></i> <?= __('Create Full Backup') ?></button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="text-white">
                            <th><?= __('Name') ?></th>
                            <th><?= __('Type') ?></th>
                            <th><?= __('Size') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Status') ?></th>
                            <th>SHA256</th>
                            <th><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($backups)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4"><?= __('No local backups found.') ?></td>
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
                                            <span class="badge bg-success"><?= $b['status'] === 'verified' ? __('Verified') : __('Completed') ?></span>
                                        <?php elseif ($b['status'] === 'failed'): ?>
                                            <span class="badge bg-danger"><?= __('Failed') ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark"><?= __('Pending') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle text-muted small" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($b['sha256_hash']) ?></td>
                                    <td class="align-middle">
                                        <a href="/admin/settings/backup/<?= (int)$b['id'] ?>/download" class="btn btn-sm btn-outline-info me-1"><i class="fa-solid fa-download"></i></a>
                                        <a href="/admin/settings/backup/<?= (int)$b['id'] ?>/verify" class="btn btn-sm btn-outline-success me-1" title="<?= __('Integrity Check') ?>"><i class="fa-solid fa-shield-heart"></i></a>
                                        <form action="/admin/settings/backup/<?= (int)$b['id'] ?>/delete" method="POST" class="d-inline">
                                            <?= \App\Core\Csrf::field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= htmlspecialchars(__('Are you sure you want to permanently delete this backup?')) ?>')"><i class="fa-solid fa-trash"></i></button>
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
            <?php
            $db = \App\Core\Database::getInstance();
            $globalRules = $db->query("SELECT * FROM notification_templates ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            $allRoles = $db->query("SELECT id, name FROM roles ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h4 class="text-white mb-1"><i class="fa-solid fa-envelope-circle-check text-primary me-2"></i> Global Email Notification Rules</h4>
                    <p class="text-muted mb-0">Διαμορφώστε τους παγκόσμιους κανόνες ειδοποιήσεων συστήματος (π.χ. Δημιουργία Χρήστη, Reset Password κλπ).</p>
                </div>
                <button type="button" class="btn btn-success btn-sm" id="newGlobalRuleBtn"><i class="fa-solid fa-plus me-1"></i> Νέος Κανόνας / New Rule</button>
            </div>

            <div class="row g-4">
                <!-- Left: rules list -->
                <div class="col-md-6">
                    <div class="card p-3">
                        <h6 class="text-white mb-3"><i class="fa-solid fa-list text-primary me-2"></i>Λίστα Κανόνων Συστήματος</h6>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr class="text-white">
                                        <th>Κανόνας</th>
                                        <th>Event</th>
                                        <th>Κατάσταση</th>
                                        <th class="text-end">Ενέργειες</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($globalRules)): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-4">Δεν έχουν οριστεί κανόνες ειδοποίησης.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($globalRules as $rule):
                                            $ruleConfig = json_decode($rule['conditional_logic_json'] ?? '{}', true) ?: [];
                                            $triggerEvent = $ruleConfig['trigger_event'] ?? $rule['slug'];
                                            $hasCondRules = !empty($ruleConfig['enabled']);
                                        ?>
                                        <tr>
                                            <td>
                                                <strong class="text-white"><?= htmlspecialchars($rule['name']) ?></strong>
                                                <?php if ($hasCondRules): ?>
                                                    <span class="badge bg-warning text-dark ms-1" style="font-size:9px;">Conditional</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-muted small"><code><?= htmlspecialchars($triggerEvent) ?></code></td>
                                            <td>
                                                <span class="badge <?= $rule['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $rule['is_active'] ? 'Ενεργός' : 'Ανενεργός' ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-1">
                                                    <button type="button" class="btn btn-xs btn-outline-info edit-global-rule-btn" data-rule='<?= json_encode($rule, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS) ?>'><i class="fa-solid fa-edit"></i></button>
                                                    <form action="/admin/settings/global-notifications/<?= (int)$rule['id'] ?>/delete" method="POST" style="display:inline;" class="js-confirm-action" data-confirm-title="Διαγραφή" data-confirm-message="Θέλετε να διαγράψετε αυτόν τον κανόνα;">
                                                        <?= \App\Core\Csrf::field() ?>
                                                        <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right: Add / Edit form -->
                <div class="col-md-6">
                    <div class="card p-3" id="globalRuleFormContainer">
                        <h6 class="text-white mb-3" id="globalRuleTitle"><i class="fa-solid fa-edit text-info me-2"></i>Επεξεργασία Κανόνα</h6>

                        <form action="/admin/settings/global-notifications/update" method="POST" id="globalRuleForm">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="id" id="globalRuleId">

                            <!-- Rule Name + Active toggle -->
                            <div class="row g-2 mb-3">
                                <div class="col-8">
                                    <label class="form-label text-white small">Όνομα Κανόνα</label>
                                    <input type="text" name="name" id="globalRuleName" class="form-control form-control-sm" required placeholder="π.χ. Νέος Χρήστης – Καλωσόρισμα">
                                </div>
                                <div class="col-4 d-flex align-items-end pb-1">
                                    <div class="form-check ms-1">
                                        <input type="checkbox" name="is_active" id="globalRuleActive" class="form-check-input" value="1">
                                        <label class="form-check-label text-white small" for="globalRuleActive">Ενεργός</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Trigger Event -->
                            <div class="mb-3">
                                <label class="form-label text-white small">Γεγονός Ενεργοποίησης (Trigger Event)</label>
                                <select class="form-select form-select-sm" name="trigger_event" id="globalRuleTrigger">
                                    <option value="user_registered">user_registered — Δημιουργία νέου χρήστη</option>
                                    <option value="password_reset">password_reset — Επαναφορά κωδικού</option>
                                    <option value="account_activated">account_activated — Ενεργοποίηση λογαριασμού</option>
                                    <option value="account_deactivated">account_deactivated — Απενεργοποίηση λογαριασμού</option>
                                    <option value="role_changed">role_changed — Αλλαγή ρόλου χρήστη</option>
                                    <option value="login_failure">login_failure — Αποτυχία σύνδεσης</option>
                                    <option value="system_alert">system_alert — Ειδοποίηση Συστήματος</option>
                                </select>
                                <small class="form-text text-muted">Το slug αυτού του event χρησιμοποιείται ως αναγνωριστικό στον κώδικα.</small>
                            </div>

                            <!-- Recipient Type -->
                            <div class="mb-3">
                                <label class="form-label text-white small">Τύπος Παραλήπτη (Recipient Type)</label>
                                <select class="form-select form-select-sm" name="recipient_type" id="globalRecipientType">
                                    <option value="fixed">Σταθερές διευθύνσεις Email (Fixed)</option>
                                    <option value="event_user">Χρήστης του Event (Event User)</option>
                                    <option value="role">Χρήστες Συγκεκριμένου Ρόλου (Role)</option>
                                </select>
                            </div>

                            <!-- Fixed Recipients field -->
                            <div class="mb-3" id="globalToWrapper">
                                <label class="form-label text-white small">Παραλήπτες (To — emails)</label>
                                <input type="text" name="to_recipients" id="globalRuleTo" class="form-control form-control-sm" placeholder="admin@example.com, other@domain.com">
                                <small class="form-text text-muted">Διαχωρίστε πολλαπλά emails με κόμμα.</small>
                            </div>

                            <!-- Role Recipients -->
                            <div class="mb-3 d-none" id="globalRoleWrapper">
                                <label class="form-label text-white small">Επιλογή Ρόλου</label>
                                <select class="form-select form-select-sm" name="recipient_role_id" id="globalRuleRoleId">
                                    <option value="">Επιλέξτε ρόλο...</option>
                                    <?php foreach ($allRoles as $role): ?>
                                        <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Subject -->
                            <div class="mb-3">
                                <label class="form-label text-white small">Θέμα (Subject Template)</label>
                                <input type="text" name="subject" id="globalRuleSubject" class="form-control form-control-sm" required placeholder="Καλωσήρθατε στο AppForm, {user_name}!">
                            </div>

                            <!-- HTML Body -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label text-white small mb-0">Μήνυμα HTML (HTML Body)</label>
                                    <button type="button" class="btn btn-outline-info btn-xs" id="globalPreviewHtmlBtn"><i class="fa-solid fa-eye me-1"></i>Προεπισκόπηση HTML</button>
                                </div>
                                <textarea name="body_html" id="globalRuleBodyHtml" class="form-control form-control-sm" rows="5" placeholder="<p>Γεια σας, {user_name}!</p><p>Ο λογαριασμός σας δημιουργήθηκε.</p>"></textarea>
                            </div>

                            <!-- Plain Text Fallback -->
                            <div class="mb-3">
                                <label class="form-label text-white small">Εναλλακτικό Μήνυμα Απλού Κειμένου (Plain Text Fallback)</label>
                                <textarea name="body_text" id="globalRuleBodyText" class="form-control form-control-sm" rows="3" placeholder="Γεια σας, {user_name}. Ο λογαριασμός σας δημιουργήθηκε."></textarea>
                                <small class="form-text text-muted">Εμφανίζεται σε clients που δεν υποστηρίζουν HTML.</small>
                            </div>

                            <!-- Smart Tags Picker -->
                            <div class="mb-3">
                                <label class="form-label text-muted small"><i class="fa-solid fa-tags me-1"></i> Smart Tags — Κάντε κλικ για εισαγωγή στη θέση cursor</label>
                                <div class="glass-panel p-2" style="font-size: 11px; background: rgba(255,255,255,0.05); border-radius: 6px;">
                                    <div class="mb-1"><strong class="text-white-50">Στοιχεία Χρήστη:</strong><br>
                                        <button type="button" class="btn btn-outline-secondary btn-xs insert-global-tag-btn me-1 mb-1" data-tag="{user_name}">{user_name} (Όνομα)</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs insert-global-tag-btn me-1 mb-1" data-tag="{user_email}">{user_email} (Email)</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs insert-global-tag-btn me-1 mb-1" data-tag="{user_role}">{user_role} (Ρόλος)</button>
                                    </div>
                                    <div><strong class="text-white-50">Σύστημα:</strong><br>
                                        <button type="button" class="btn btn-outline-secondary btn-xs insert-global-tag-btn me-1 mb-1" data-tag="{site_name}">{site_name}</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs insert-global-tag-btn me-1 mb-1" data-tag="{site_url}">{site_url}</button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs insert-global-tag-btn me-1 mb-1" data-tag="{action_url}">{action_url} (Σύνδεσμος)</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Conditional Logic / Execution Rules -->
                            <div class="border border-secondary rounded p-3 mb-4">
                                <h6 class="text-white small font-heading mb-2">Κανόνες Εκτέλεσης (Conditional Logic)</h6>
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" id="global_cond_enabled" name="cond_enabled">
                                    <label class="form-check-label text-white small" for="global_cond_enabled">Ενεργοποίηση Κανόνων</label>
                                </div>
                                <div class="d-none" id="global_cond_body">
                                    <div class="mb-2">
                                        <label class="form-label small text-white-50">Ενέργεια (Action)</label>
                                        <select class="form-select form-select-sm" name="cond_action" id="global_cond_action">
                                            <option value="show">Αποστολή (Send)</option>
                                            <option value="hide">Μη αποστολή (Do not send)</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small text-white-50">Συνθήκη (Match Mode)</label>
                                        <select class="form-select form-select-sm" name="cond_match" id="global_cond_match">
                                            <option value="all">Όλα τα κριτήρια (All)</option>
                                            <option value="any">Οποιοδήποτε κριτήριο (Any)</option>
                                        </select>
                                    </div>
                                    <div id="global_cond_rules_list" class="mb-2"></div>
                                    <button type="button" class="btn btn-outline-success btn-xs" id="addGlobalRuleBtn"><i class="fa-solid fa-plus me-1"></i>Προσθήκη Κανόνα</button>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-save me-1"></i>Αποθήκευση Κανόνα</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm w-50 d-none" id="cancelGlobalEditBtn">Ακύρωση</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Global HTML Preview Modal -->
            <div class="modal fade" id="globalPreviewModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content bg-dark border border-secondary text-white">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title"><i class="fa-solid fa-eye text-primary me-2"></i>Προεπισκόπηση HTML Email</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <iframe id="globalPreviewIframe" style="width:100%; height:420px; border:none; background:#fff;"></iframe>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Κλείσιμο</button>
                        </div>
                    </div>
                </div>
            </div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    /* ── DOM refs ─────────────────────────────────────────────── */
    const editBtns       = document.querySelectorAll('.edit-global-rule-btn');
    const newRuleBtn     = document.getElementById('newGlobalRuleBtn');
    const cancelBtn      = document.getElementById('cancelGlobalEditBtn');
    const form           = document.getElementById('globalRuleForm');
    const formTitle      = document.getElementById('globalRuleTitle');
    const formId         = document.getElementById('globalRuleId');
    const formName       = document.getElementById('globalRuleName');
    const formActive     = document.getElementById('globalRuleActive');
    const formTrigger    = document.getElementById('globalRuleTrigger');
    const formRecipType  = document.getElementById('globalRecipientType');
    const formTo         = document.getElementById('globalRuleTo');
    const formRoleId     = document.getElementById('globalRuleRoleId');
    const formSubject    = document.getElementById('globalRuleSubject');
    const formBodyHtml   = document.getElementById('globalRuleBodyHtml');
    const formBodyText   = document.getElementById('globalRuleBodyText');
    const toWrapper      = document.getElementById('globalToWrapper');
    const roleWrapper    = document.getElementById('globalRoleWrapper');

    /* ── Recipient type toggle ────────────────────────────────── */
    function updateRecipientUI() {
        const t = formRecipType.value;
        toWrapper.classList.toggle('d-none', t !== 'fixed');
        roleWrapper.classList.toggle('d-none', t !== 'role');
    }
    formRecipType.addEventListener('change', updateRecipientUI);
    updateRecipientUI();

    /* ── Smart Tag cursor-aware insert ───────────────────────── */
    let activeGlobalInput = formBodyHtml;
    [formSubject, formBodyHtml, formBodyText].forEach(el => {
        if (el) el.addEventListener('focus', () => { activeGlobalInput = el; });
    });
    document.querySelectorAll('.insert-global-tag-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const tag = btn.dataset.tag;
            const el = activeGlobalInput || formBodyHtml;
            const start = el.selectionStart ?? el.value.length;
            const end   = el.selectionEnd   ?? el.value.length;
            el.value = el.value.substring(0, start) + tag + el.value.substring(end);
            el.selectionStart = el.selectionEnd = start + tag.length;
            el.focus();
        });
    });

    /* ── HTML Preview ─────────────────────────────────────────── */
    const previewModal = new bootstrap.Modal(document.getElementById('globalPreviewModal'));
    document.getElementById('globalPreviewHtmlBtn').addEventListener('click', () => {
        const html = formBodyHtml.value || '<p class="text-muted">Το μήνυμα είναι κενό.</p>';
        const iframe = document.getElementById('globalPreviewIframe');
        const doc = iframe.contentWindow.document;
        doc.open();
        doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><style>body{font-family:sans-serif;padding:20px;color:#333;line-height:1.6;}</style></head><body>${html}</body></html>`);
        doc.close();
        iframe.setAttribute('sandbox', 'allow-same-origin');
        previewModal.show();
    });

    /* ── Conditional Logic / Rule Builder ────────────────────── */
    const condEnabledChk  = document.getElementById('global_cond_enabled');
    const condBody        = document.getElementById('global_cond_body');
    const condActionSel   = document.getElementById('global_cond_action');
    const condMatchSel    = document.getElementById('global_cond_match');
    const condRulesList   = document.getElementById('global_cond_rules_list');
    const addRuleBtn      = document.getElementById('addGlobalRuleBtn');
    let currentRules = [];

    const globalFields = [
        { key: 'user_name',  label: 'Όνομα Χρήστη' },
        { key: 'user_email', label: 'Email Χρήστη'  },
        { key: 'user_role',  label: 'Ρόλος Χρήστη'  },
        { key: 'site_name',  label: 'Όνομα Site'     }
    ];

    condEnabledChk.addEventListener('change', e => {
        if (e.target.checked) {
            condBody.classList.remove('d-none');
            if (currentRules.length === 0) addGlobalRuleItem();
        } else {
            condBody.classList.add('d-none');
        }
    });

    addRuleBtn.addEventListener('click', () => addGlobalRuleItem());

    function addGlobalRuleItem(rule = { field: '', operator: 'equals', value: '' }) {
        currentRules.push(rule);
        renderGlobalRules();
    }

    function renderGlobalRules() {
        condRulesList.innerHTML = '';
        currentRules.forEach((r, idx) => {
            const div = document.createElement('div');
            div.className = 'border border-secondary rounded p-2 mb-2';
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small text-muted">Κανόνας #${idx + 1}</span>
                    <button type="button" class="btn btn-outline-danger btn-xs border-0 rm-rule-btn" data-idx="${idx}"><i class="fa-solid fa-times"></i></button>
                </div>
                <div class="mb-1">
                    <select class="form-select form-select-sm g-rule-field" name="rules[${idx}][field]">
                        <option value="">Επιλέξτε μεταβλητή...</option>
                        ${globalFields.map(f => `<option value="${f.key}" ${f.key === r.field ? 'selected' : ''}>${f.label} (${f.key})</option>`).join('')}
                    </select>
                </div>
                <div class="mb-1">
                    <select class="form-select form-select-sm g-rule-op" name="rules[${idx}][operator]">
                        <option value="equals"       ${r.operator==='equals'       ?'selected':''}>Ισούται με</option>
                        <option value="not_equals"   ${r.operator==='not_equals'   ?'selected':''}>Δεν ισούται με</option>
                        <option value="contains"     ${r.operator==='contains'     ?'selected':''}>Περιέχει</option>
                        <option value="not_contains" ${r.operator==='not_contains' ?'selected':''}>Δεν περιέχει</option>
                        <option value="is_empty"     ${r.operator==='is_empty'     ?'selected':''}>Είναι κενό</option>
                        <option value="is_not_empty" ${r.operator==='is_not_empty' ?'selected':''}>Δεν είναι κενό</option>
                    </select>
                </div>
                <div>
                    <input type="text" class="form-control form-control-sm g-rule-val" name="rules[${idx}][value]" value="${r.value || ''}" placeholder="Τιμή">
                </div>
            `;
            condRulesList.appendChild(div);
        });

        condRulesList.querySelectorAll('.g-rule-op').forEach(sel => {
            const valInput = sel.closest('div.border').querySelector('.g-rule-val');
            const tog = () => valInput.classList.toggle('d-none', sel.value === 'is_empty' || sel.value === 'is_not_empty');
            sel.addEventListener('change', tog);
            tog();
        });

        condRulesList.querySelectorAll('.rm-rule-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                currentRules.splice(parseInt(btn.dataset.idx), 1);
                renderGlobalRules();
            });
        });
    }

    /* ── Form reset helper ───────────────────────────────────── */
    function resetGlobalForm() {
        formId.value          = '';
        formName.value        = '';
        formName.removeAttribute('readonly');
        formActive.checked    = true;
        formTrigger.value     = 'user_registered';
        formRecipType.value   = 'fixed';
        formTo.value          = '';
        if (formRoleId) formRoleId.value = '';
        formSubject.value     = '';
        formBodyHtml.value    = '';
        formBodyText.value    = '';
        condEnabledChk.checked = false;
        condBody.classList.add('d-none');
        currentRules          = [];
        condRulesList.innerHTML = '';
        updateRecipientUI();
    }

    /* ── "New Rule" button ───────────────────────────────────── */
    newRuleBtn.addEventListener('click', () => {
        formTitle.innerHTML = '<i class="fa-solid fa-plus text-success me-2"></i>Νέος Κανόνας Ειδοποίησης';
        form.action = '/admin/settings/global-notifications';
        resetGlobalForm();
        cancelBtn.classList.remove('d-none');
    });

    /* ── Cancel button ───────────────────────────────────────── */
    cancelBtn.addEventListener('click', () => {
        if (editBtns.length > 0) {
            editBtns[0].click();
        } else {
            formTitle.innerHTML = '<i class="fa-solid fa-edit text-info me-2"></i>Επεξεργασία Κανόνα';
            form.action = '/admin/settings/global-notifications/update';
            resetGlobalForm();
            cancelBtn.classList.add('d-none');
        }
    });

    /* ── Edit buttons ────────────────────────────────────────── */
    editBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const r = JSON.parse(btn.dataset.rule);
            let cfg = {};
            try { cfg = JSON.parse(r.conditional_logic_json || '{}'); } catch(e) {}

            formTitle.innerHTML = '<i class="fa-solid fa-edit text-info me-2"></i>Επεξεργασία Κανόνα';
            form.action   = '/admin/settings/global-notifications/update';
            formId.value  = r.id;
            formName.value = r.name;
            formName.removeAttribute('readonly');
            formActive.checked = parseInt(r.is_active) === 1;

            // Trigger event — from extended config or slug
            const te = cfg.trigger_event || r.slug || 'user_registered';
            const opt = formTrigger.querySelector(`option[value="${te}"]`);
            if (opt) { formTrigger.value = te; } else { formTrigger.value = 'user_registered'; }

            // Recipients
            formRecipType.value = cfg.recipient_type || 'fixed';
            formTo.value        = cfg.to_recipients  || '';
            if (formRoleId) formRoleId.value = cfg.recipient_role_id || '';
            updateRecipientUI();

            formSubject.value   = r.subject   || '';
            formBodyHtml.value  = r.body_html || '';
            formBodyText.value  = r.body_text || '';

            // Conditional logic
            if (cfg && cfg.enabled) {
                condEnabledChk.checked = true;
                condBody.classList.remove('d-none');
                if (condActionSel) condActionSel.value = cfg.action || 'show';
                if (condMatchSel)  condMatchSel.value  = cfg.match  || 'all';
                currentRules = cfg.rules || [];
                renderGlobalRules();
            } else {
                condEnabledChk.checked = false;
                condBody.classList.add('d-none');
                currentRules = [];
                condRulesList.innerHTML = '';
            }

            cancelBtn.classList.add('d-none');
        });
    });

    // Auto-load first rule on page load
    if (editBtns.length > 0) editBtns[0].click();
});
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
                            $isExpired = empty($tok['expires_at']) || strtotime($tok['expires_at']) < time();
                            $statusText = $isExpired ? 'Απαιτεί επανασύνδεση' : 'Συνδεδεμένο';
                            $statusColor = $isExpired ? 'text-warning' : 'text-success';
                        ?>
                            <div class="text-start mb-3 p-3 rounded border" style="background:var(--color-bg);border-color:var(--color-border);">
                                <p class="mb-1 text-muted small">Κατάσταση: <span class="<?= $statusColor ?> font-bold"><?= $statusText ?></span></p>
                                <p class="mb-1 text-muted small">Λογαριασμός: <span style="color:var(--color-text);"><?= htmlspecialchars($tok['connected_account'] ?? 'N/A') ?></span></p>
                                <p class="mb-1 text-muted small">Σύνδεση: <span style="color:var(--color-text);"><?= $tok['last_connected_at'] ? date('d/m/Y H:i', strtotime($tok['last_connected_at'])) : 'N/A' ?></span></p>
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

            <!-- Local Update from ZIP Panel -->
            <?php if (\App\Core\Auth::hasPermission('updates.manage')): ?>
            <div class="card p-3 mb-4" style="background:var(--color-surface);border-color:var(--color-border);">
                <h6 class="border-bottom pb-2 mb-3" style="color:var(--color-text);border-color:var(--color-border)!important;"><i class="fa-solid fa-file-zipper me-2 text-info"></i> Τοπική Αναβάθμιση από Αρχείο</h6>
                <div class="alert alert-warning small">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> ΠΡΟΣΟΧΗ: Δέχεται μόνο επίσημα incremental update ZIPs (π.χ. AppForm-1.1.11-update.zip). Μην ανεβάζετε το full installation ZIP.
                </div>
                <form id="form-local-update" action="/admin/settings/updates/local" method="POST" enctype="multipart/form-data">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="mb-3">
                        <input type="file" name="local_zip" id="inp-local-zip" class="form-control form-control-sm" accept=".zip" required>
                    </div>
                    <button type="submit" id="btn-local-update" class="btn btn-info btn-sm text-white"><i class="fa-solid fa-upload me-1"></i> Έναρξη Τοπικής Αναβάθμισης</button>
                </form>
            </div>
            <?php endif; ?>

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

            // Handle Local Update Form
            const formLocalUpdate = document.getElementById('form-local-update');
            if (formLocalUpdate) {
                formLocalUpdate.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (!confirm('Είστε σίγουροι ότι θέλετε να ξεκινήσετε την τοπική αναβάθμιση; Η διαδικασία δεν μπορεί να διακοπεί.')) {
                        return;
                    }
                    const btnLocal = document.getElementById('btn-local-update');
                    btnLocal.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Μεταφόρτωση...';
                    btnLocal.disabled = true;

                    const formData = new FormData(formLocalUpdate);

                    fetch('/admin/settings/updates/local', {
                        method: 'POST',
                        body: formData
                    })
                    .then(async res => {
                        if (!res.ok) {
                            let msg = `HTTP Σφάλμα ${res.status}`;
                            try {
                                const errData = await res.json();
                                msg = errData.message || msg;
                            } catch (e) {}
                            throw new Error(msg);
                        }
                        return res.json();
                    })
                    .then(data => {
                        btnLocal.innerHTML = '<i class="fa-solid fa-upload me-1"></i> Έναρξη Τοπικής Αναβάθμισης';
                        btnLocal.disabled = false;
                        if (data.success) {
                            alert('Η μεταφόρτωση ολοκληρώθηκε και η διαδικασία εγκατάστασης ξεκίνησε.');
                            progressCard.style.display = 'block';
                            startPolling();
                        } else {
                            alert('Σφάλμα μεταφόρτωσης: ' + (data.message || 'Άγνωστο σφάλμα.'));
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        btnLocal.innerHTML = '<i class="fa-solid fa-upload me-1"></i> Έναρξη Τοπικής Αναβάθμισης';
                        btnLocal.disabled = false;
                        alert('Σφάλμα: ' + err.message);
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
                        progressBar.style.width = '0%';
                        progressBar.innerText = '0%';
                        lblStep.innerText = '-';
                        consoleLog.innerText = '';
                        btnRollback.style.display = 'none';
                        btnUnlock.style.display = 'none';
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
