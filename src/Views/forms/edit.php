<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <a href="/admin/forms" class="btn btn-outline-secondary btn-sm mb-2"><i class="fa-solid fa-arrow-left"></i> Πίσω στις Φόρμες</a>
        <h3 class="font-heading text-white mb-0">Επεξεργασία Στοιχείων Φόρμας</h3>
    </div>
    <div class="btn-group">
        <a href="/admin/forms/<?= $form['id'] ?>/edit" class="btn btn-primary active"><i class="fa-solid fa-gear me-1"></i> Γενικές Ρυθμίσεις</a>
        <a href="/admin/forms/<?= $form['id'] ?>/notifications" class="btn btn-outline-primary"><i class="fa-solid fa-bell me-1"></i> Ειδοποιήσεις</a>
    </div>
</div>

<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
        <?= \App\Core\View::escape($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="glass-panel p-4">
    <form action="/admin/forms/<?= $form['id'] ?>/update" method="POST">
        <?= \App\Core\Csrf::field() ?>
        <div class="row">
            <div class="col-md-7 border-end border-glass pe-4">

        <div class="mb-3">
            <label for="title" class="form-label text-white">Τίτλος Φόρμας</label>
            <input type="text" class="form-control" id="title" name="title" value="<?= \App\Core\View::escape($form['title']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="slug" class="form-label text-white">Slug</label>
            <input type="text" class="form-control" id="slug" name="slug" value="<?= \App\Core\View::escape($form['slug']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label text-white">Περιγραφή</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?= \App\Core\View::escape($form['description']) ?></textarea>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= $form['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label text-muted" for="is_active">Ενεργή Φόρμα</label>
        </div>

        <div class="mb-4 form-check">
            <input type="checkbox" class="form-check-input" id="allow_drafts" name="allow_drafts" value="1" <?= $form['allow_drafts'] ? 'checked' : '' ?>>
            <label class="form-check-label text-muted" for="allow_drafts">Επιτρέπονται Προσχέδια (Drafts)</label>
        </div>

        <hr class="border-glass mb-4">
        <h5 class="font-heading text-white mb-3">Survey &amp; Analytics Settings</h5>

        <div class="mb-3">
            <label for="form_mode" class="form-label text-white">Τύπος Λειτουργίας Φόρμας</label>
            <select class="form-select" id="form_mode" name="form_mode">
                <option value="workflow" <?= ($form['form_mode'] ?? 'workflow') === 'workflow' ? 'selected' : '' ?>>Workflow Form</option>
                <option value="survey" <?= ($form['form_mode'] ?? 'workflow') === 'survey' ? 'selected' : '' ?>>Survey Form</option>
            </select>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="is_anonymous" name="is_anonymous" value="1" <?= ($form['is_anonymous'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label text-muted" for="is_anonymous">Enable Anonymous Submissions</label>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="survey_analytics" name="survey_analytics" value="1" <?= ($form['survey_analytics'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label text-muted" for="survey_analytics">Enable Survey Analytics</label>
        </div>

        <div class="mb-4 form-check">
            <input type="checkbox" class="form-check-input" id="survey_charts_pdf" name="survey_charts_pdf" value="1" <?= ($form['survey_charts_pdf'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label text-muted" for="survey_charts_pdf">Include Survey Analytics and Charts in PDF Report</label>
        </div>

        <hr class="border-glass mb-4">
        <h5 class="font-heading text-white mb-3">Availability &amp; Restrictions Settings</h5>

        <div class="mb-3">
            <label for="submission_starts_at" class="form-label text-white">Ημερομηνία &amp; Ώρα Έναρξης Υποβολών</label>
            <input type="datetime-local" class="form-control" id="submission_starts_at" name="submission_starts_at" value="<?= $form['submission_starts_at'] ? date('Y-m-d\TH:i', strtotime($form['submission_starts_at'])) : '' ?>">
        </div>

        <div class="mb-3">
            <label for="submission_expires_at" class="form-label text-white">Ημερομηνία &amp; Ώρα Λήξης Υποβολών</label>
            <input type="datetime-local" class="form-control" id="submission_expires_at" name="submission_expires_at" value="<?= $form['submission_expires_at'] ? date('Y-m-d\TH:i', strtotime($form['submission_expires_at'])) : '' ?>">
        </div>

        <div class="mb-3">
            <label for="expiration_message" class="form-label text-white">Μήνυμα Εκπνοής (Custom Expiration Message)</label>
            <textarea class="form-control" id="expiration_message" name="expiration_message" rows="2" placeholder="Η προθεσμία υποβολής της συγκεκριμένης φόρμας έχει λήξει."><?= \App\Core\View::escape($form['expiration_message'] ?? '') ?></textarea>
        </div>

        <div class="mb-4 form-check">
            <input type="checkbox" class="form-check-input" id="single_submission_enabled" name="single_submission_enabled" value="1" <?= ($form['single_submission_enabled'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label text-muted" for="single_submission_enabled">Allow only one submission per user (Περιορισμός μίας υποβολής ανά χρήστη)</label>
        </div>

        <div class="mb-3">
            <label for="maximum_submissions" class="form-label text-white">Μέγιστος Αριθμός Ολοκληρωμένων Υποβολών (Maximum Completed Submissions)</label>
            <input type="number" class="form-control" id="maximum_submissions" name="maximum_submissions" min="1" value="<?= $form['maximum_submissions'] ?: '' ?>">
            <div class="form-text text-muted">Η φόρμα θα κλείσει αυτόματα όταν συμπληρωθεί ο καθορισμένος αριθμός έγκυρων υποβολών.</div>
        </div>

        <hr class="border-glass mb-4">
        <h5 class="font-heading text-white mb-3">Δικαιώματα Υποβολής</h5>

        <div class="mb-3">
            <label for="submission_access_mode" class="form-label text-white">Ποιος μπορεί να υποβάλει τη φόρμα;</label>
            <select class="form-select" id="submission_access_mode" name="submission_access_mode">
                <option value="all_authenticated" <?= ($form['submission_access_mode'] ?? 'all_authenticated') === 'all_authenticated' ? 'selected' : '' ?>>Όλοι οι συνδεδεμένοι χρήστες με forms.submit</option>
                <option value="selected_roles" <?= ($form['submission_access_mode'] ?? 'all_authenticated') === 'selected_roles' ? 'selected' : '' ?>>Επιλεγμένοι Ρόλοι</option>
                <option value="selected_users" <?= ($form['submission_access_mode'] ?? 'all_authenticated') === 'selected_users' ? 'selected' : '' ?>>Επιλεγμένοι Χρήστες</option>
                <option value="public" <?= ($form['submission_access_mode'] ?? 'all_authenticated') === 'public' ? 'selected' : '' ?>>Δημόσια υποβολή χωρίς σύνδεση</option>
                <option value="nobody" <?= ($form['submission_access_mode'] ?? 'all_authenticated') === 'nobody' ? 'selected' : '' ?>>Κανένας</option>
            </select>
        </div>

        <!-- Roles section: hidden by default via inline style, toggled by JS -->
        <div id="section_selected_roles" style="display:none;" class="mb-3">
            <label class="form-label text-white">Επιλέξτε Ρόλους που επιτρέπεται να υποβάλουν:</label>
            <div class="d-flex flex-column gap-2 border border-glass rounded p-3 bg-dark bg-opacity-25" style="max-height: 200px; overflow-y: auto;">
                <?php
                $assignedRoles = array_column($roleAssignments ?? [], 'role_id');
                foreach ($roles as $role):
                ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="submission_roles[]" value="<?= $role['id'] ?>" id="sub_role_<?= $role['id'] ?>"
                            <?= in_array($role['id'], $assignedRoles) ? 'checked' : '' ?>>
                        <label class="form-check-label text-muted" for="sub_role_<?= $role['id'] ?>">
                            <?= \App\Core\View::escape($role['name']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Users section: hidden by default via inline style, toggled by JS -->
        <div id="section_selected_users" style="display:none;" class="mb-3">
            <label class="form-label text-white">Επιλέξτε Χρήστες που επιτρέπεται να υποβάλουν:</label>
            <div class="d-flex flex-column gap-2 border border-glass rounded p-3 bg-dark bg-opacity-25" style="max-height: 200px; overflow-y: auto;">
                <?php
                $assignedUsers = array_column($userAssignments ?? [], 'user_id');
                foreach ($users as $u):
                ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="submission_users[]" value="<?= $u['id'] ?>" id="sub_user_<?= $u['id'] ?>"
                            <?= in_array($u['id'], $assignedUsers) ? 'checked' : '' ?>>
                        <label class="form-check-label text-muted" for="sub_user_<?= $u['id'] ?>">
                            <?= \App\Core\View::escape($u['full_name']) ?> <small class="text-muted">(<?= \App\Core\View::escape($u['username']) ?>)</small>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        </div> <!-- /col-md-7 -->

        <div class="col-md-5 ps-4">
            <h5 class="font-heading text-white mb-3">Public Access &amp; Terms Settings</h5>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_public" name="is_public" value="1" <?= ($form['is_public'] ?? 0) ? 'checked' : '' ?>>
                <label class="form-check-label text-muted" for="is_public">Allow Public Submissions Without Login (Ελεύθερη Υποβολή χωρίς Σύνδεση)</label>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="require_terms_acceptance" name="require_terms_acceptance" value="1" <?= ($form['require_terms_acceptance'] ?? 0) ? 'checked' : '' ?>>
                <label class="form-check-label text-muted" for="require_terms_acceptance">Require Terms Acceptance (Απαιτείται Αποδοχή Όρων Χρήσης)</label>
            </div>

            <div class="mb-3">
                <label for="terms_checkbox_label" class="form-label text-white">Κείμενο Checkbox Όρων (Terms Checkbox Label)</label>
                <input type="text" class="form-control" id="terms_checkbox_label" name="terms_checkbox_label" value="<?= \App\Core\View::escape($form['terms_checkbox_label'] ?? 'Έχω διαβάσει και αποδέχομαι τους όρους χρήσης της φόρμας.') ?>">
            </div>

            <div class="mb-3">
                <label for="terms_link_label" class="form-label text-white">Κείμενο Συνδέσμου Όρων (Terms Link Label)</label>
                <input type="text" class="form-control" id="terms_link_label" name="terms_link_label" value="<?= \App\Core\View::escape($form['terms_link_label'] ?? 'Δείτε τους όρους χρήσης') ?>">
            </div>

            <div class="mb-4">
                <label for="terms_content" class="form-label text-white">Περιεχόμενο Όρων Χρήσης (Terms Content)</label>
                <textarea class="form-control" id="terms_content" name="terms_content" rows="6" placeholder="Εισάγετε εδώ το κείμενο των όρων χρήσης..."><?= \App\Core\View::escape($form['terms_content'] ?? '') ?></textarea>
            </div>

            <div class="alert alert-warning text-white-50 border-glass bg-transparent p-3 mb-4" id="single_submission_warning" style="font-size: 0.9rem;">
                <i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>
                Ο περιορισμός μίας υποβολής ανά χρήστη δεν μπορεί να εγγυηθεί μοναδική συμμετοχή για επισκέπτες χωρίς σύνδεση.
            </div>

            <!-- Public Link Panel -->
            <?php if (!empty($form['is_public']) && !empty($form['public_token'])):
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $publicUrl = $protocol . '://' . $host . '/f/' . $form['public_token'];

                $statusCheck = \App\Services\FormAvailabilityService::checkAvailability($form, null);
                $statusLabel = 'Open';
                $statusColor = 'text-success';

                if ($statusCheck === 'inactive') {
                    $statusLabel = 'Disabled';
                    $statusColor = 'text-danger';
                } elseif ($statusCheck === 'not_started') {
                    $statusLabel = 'Not Started';
                    $statusColor = 'text-warning';
                } elseif ($statusCheck === 'expired') {
                    $statusLabel = 'Closed by Date';
                    $statusColor = 'text-danger';
                } elseif ($statusCheck === 'capacity_reached') {
                    $statusLabel = 'Closed by Capacity';
                    $statusColor = 'text-danger';
                }
            ?>
            <div class="glass-panel p-3 mb-4 border border-glass" id="public_link_panel">
                <h6 class="text-white mb-2"><i class="fa-solid fa-link me-2 text-info"></i> Δημόσιος Σύνδεσμος (Public Link)</h6>
                <div class="mb-3">
                    <input type="text" class="form-control form-control-sm text-white bg-dark bg-opacity-50" id="publicUrlInput" value="<?= $publicUrl ?>" readonly>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" class="btn btn-outline-info btn-sm" id="btnCopyUrl"><i class="fa-solid fa-copy me-1"></i> Αντιγραφή URL</button>
                    <a href="<?= $publicUrl ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm"><i class="fa-solid fa-external-link me-1"></i> Άνοιγμα Φόρμας</a>
                    <button type="button" class="btn btn-outline-warning btn-sm" id="btnOpenQrModal"><i class="fa-solid fa-qrcode me-1"></i> QR Code</button>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top border-glass pt-2" style="font-size: 0.85rem;">
                    <span class="text-muted">Public Status: <strong class="<?= $statusColor ?>"><?= $statusLabel ?></strong></span>
                    <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none" id="btnRegenerateUrl"><i class="fa-solid fa-arrows-rotate me-1"></i> Ανανέωση Συνδέσμου</button>
                </div>
            </div>

            <!-- QR Code Modal -->
            <div id="qrModal" class="d-none" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; display: flex; align-items: center; justify-content: center;">
                <div class="glass-panel p-4 text-center" style="max-width: 450px; width: 90%;">
                    <h5 class="text-white mb-3"><?= \App\Core\View::escape($form['title']) ?></h5>
                    <div class="bg-white p-3 rounded d-inline-block mb-3" id="qrCanvasContainer">
                        <div id="qrcode"></div>
                    </div>
                    <div class="small text-muted mb-4"><?= $publicUrl ?></div>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-success btn-sm" id="btnDownloadQr"><i class="fa-solid fa-download me-1"></i> Download PNG</button>
                        <button type="button" class="btn btn-info btn-sm" id="btnPrintQr"><i class="fa-solid fa-print me-1"></i> Εκτύπωση</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="btnCloseQrModal">Κλείσιμο</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div> <!-- /col-md-5 -->
    </div> <!-- /row -->

    <button type="submit" class="btn btn-premium w-100 mt-4">Αποθήκευση Αλλαγών <i class="fa-solid fa-save ms-2"></i></button>
</form>
</div>

<!-- Load minimal JS QR Code Generator Library -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── Copy URL ──────────────────────────────────────────────────────────────
    var btnCopy  = document.getElementById('btnCopyUrl');
    var urlInput = document.getElementById('publicUrlInput');
    if (btnCopy && urlInput) {
        btnCopy.addEventListener('click', function () {
            navigator.clipboard.writeText(urlInput.value).then(function () {
                var orig = btnCopy.innerHTML;
                btnCopy.innerHTML = '<i class="fa-solid fa-check me-1"></i> Ο σύνδεσμος αντιγράφηκε.';
                setTimeout(function () { btnCopy.innerHTML = orig; }, 2000);
            }).catch(function () {
                urlInput.select();
                document.execCommand('copy');
                var orig = btnCopy.innerHTML;
                btnCopy.innerHTML = '<i class="fa-solid fa-check me-1"></i> Ο σύνδεσμος αντιγράφηκε.';
                setTimeout(function () { btnCopy.innerHTML = orig; }, 2000);
            });
        });
    }

    // ── Regenerate URL ────────────────────────────────────────────────────────
    var btnRegenerate = document.getElementById('btnRegenerateUrl');
    if (btnRegenerate) {
        btnRegenerate.addEventListener('click', function () {
            if (confirm('Η ανανέωση του δημόσιου συνδέσμου θα ακυρώσει άμεσα τον προηγούμενο σύνδεσμο. Θέλετε να συνεχίσετε;')) {
                var f = document.createElement('form');
                f.method = 'POST';
                f.action = '/admin/forms/<?= $form['id'] ?>/regenerate-token';
                var csrf = document.createElement('input');
                csrf.type  = 'hidden';
                csrf.name  = 'csrf_token';
                csrf.value = '<?= \App\Core\Csrf::token() ?>';
                f.appendChild(csrf);
                document.body.appendChild(f);
                f.submit();
            }
        });
    }

    // ── QR Code Modal ─────────────────────────────────────────────────────────
    var qrModal    = document.getElementById('qrModal');
    var btnOpenQr  = document.getElementById('btnOpenQrModal');
    var btnCloseQr = document.getElementById('btnCloseQrModal');
    var qrcodeDiv  = document.getElementById('qrcode');

    if (btnOpenQr && qrModal) {
        btnOpenQr.addEventListener('click', function () {
            qrModal.classList.remove('d-none');
            qrcodeDiv.innerHTML = '';
            new QRCode(qrcodeDiv, {
                text: '<?= $publicUrl ?? '' ?>',
                width: 200,
                height: 200,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        });

        var closeQr = function () { qrModal.classList.add('d-none'); };
        btnCloseQr.addEventListener('click', closeQr);
        qrModal.addEventListener('click', function (e) {
            if (e.target === qrModal) closeQr();
        });

        var btnDownloadQr = document.getElementById('btnDownloadQr');
        if (btnDownloadQr) {
            btnDownloadQr.addEventListener('click', function () {
                var img = qrcodeDiv.querySelector('img');
                if (img) {
                    var link = document.createElement('a');
                    link.href = img.src;
                    link.download = 'qrcode_<?= $form['slug'] ?>.png';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            });
        }

        var btnPrintQr = document.getElementById('btnPrintQr');
        if (btnPrintQr) {
            btnPrintQr.addEventListener('click', function () {
                var img = qrcodeDiv.querySelector('img');
                if (img) {
                    var pw = window.open('', '_blank');
                    pw.document.write('<html><body style="text-align:center;padding:50px;"><img src="' + img.src + '" style="width:300px;"><p style="font-family:sans-serif;margin-top:20px;"><?= addslashes(\App\Core\View::escape($form['title'])) ?></p></body></html>');
                    pw.document.close();
                    pw.print();
                }
            });
        }
    } // end QR modal block
});
</script>

<!-- ════════════════════════════════════════════════════════════════════════
     Submission Access Mode Toggle
     Completely standalone IIFE — no dependency on QR modal or any other code
     HTML IDs used: submission_access_mode | section_selected_roles | section_selected_users
     ════════════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    function initAccessToggle() {
        var sel   = document.getElementById('submission_access_mode');
        var roles = document.getElementById('section_selected_roles');
        var users = document.getElementById('section_selected_users');

        if (!sel || !roles || !users) return;

        function applyToggle() {
            var v = sel.value;
            roles.style.display = (v === 'selected_roles') ? 'block' : 'none';
            users.style.display = (v === 'selected_users') ? 'block' : 'none';
        }

        sel.addEventListener('change', applyToggle);
        applyToggle(); // run immediately on page load
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAccessToggle);
    } else {
        initAccessToggle(); // DOM already parsed
    }
}());
</script>
