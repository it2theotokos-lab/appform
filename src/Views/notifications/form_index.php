<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <a href="/admin/forms" class="btn btn-outline-secondary btn-sm mb-2"><i class="fa-solid fa-arrow-left"></i> Πίσω στις Φόρμες</a>
        <h3 class="font-heading text-white mb-0"><?= \App\Core\View::escape($title) ?></h3>
    </div>
    <div class="btn-group">
        <a href="/admin/forms/<?= $form['id'] ?>/edit" class="btn btn-outline-primary"><i class="fa-solid fa-gear me-1"></i> Γενικές Ρυθμίσεις</a>
        <a href="/admin/forms/<?= $form['id'] ?>/notifications" class="btn btn-primary active"><i class="fa-solid fa-bell me-1"></i> Ειδοποιήσεις</a>
    </div>
</div>

<?php if ($success = \App\Core\Session::flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= \App\Core\View::escape($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= \App\Core\View::escape($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Left column: notifications list -->
    <div class="col-md-7">
        <div class="card p-4">
            <h5 class="font-heading text-white mb-4"><i class="fa-solid fa-bell text-primary me-2"></i>Ειδοποιήσεις</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Όνομα</th>
                            <th>Recipients</th>
                            <th>Κατάσταση</th>
                            <th class="text-end">Ενέργειες</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notifications)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Δεν έχουν οριστεί ειδοποιήσεις.</td></tr>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): ?>
                                <tr>
                                    <td>
                                        <strong><?= \App\Core\View::escape($n['name']) ?></strong>
                                        <?php 
                                        $cond = json_decode($n['conditional_logic_json'] ?? '{}', true);
                                        if ($cond && !empty($cond['enabled'])): ?>
                                            <span class="badge bg-warning text-dark ms-2" style="font-size:9px;">Conditional</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= \App\Core\View::escape($n['to_recipients']) ?></td>
                                    <td>
                                        <span class="badge <?= $n['is_enabled'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $n['is_enabled'] ? 'Ενεργή' : 'Ανενεργή' ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                                            <form action="/admin/forms/<?= $form['id'] ?>/notifications/<?= $n['id'] ?>/move-up" method="POST" style="display:inline;">
                                                <?= \App\Core\Csrf::field() ?>
                                                <button type="submit" class="btn btn-outline-secondary btn-xs" title="Μετακίνηση Πάνω"><i class="fa-solid fa-arrow-up"></i></button>
                                            </form>
                                            <form action="/admin/forms/<?= $form['id'] ?>/notifications/<?= $n['id'] ?>/move-down" method="POST" style="display:inline;">
                                                <?= \App\Core\Csrf::field() ?>
                                                <button type="submit" class="btn btn-outline-secondary btn-xs" title="Μετακίνηση Κάτω"><i class="fa-solid fa-arrow-down"></i></button>
                                            </form>

                                            <button type="button" class="btn btn-outline-info btn-xs edit-notif-btn" data-notif='<?= json_encode($n) ?>'><i class="fa-solid fa-edit"></i></button>
                                            
                                            <form action="/admin/forms/<?= $form['id'] ?>/notifications/<?= $n['id'] ?>/duplicate" method="POST">
                                                <?= \App\Core\Csrf::field() ?>
                                                <button type="submit" class="btn btn-outline-success btn-xs" title="Αντιγραφή"><i class="fa-solid fa-clone"></i></button>
                                            </form>

                                            <button type="button" class="btn btn-outline-warning btn-xs test-notif-btn" data-id="<?= $n['id'] ?>"><i class="fa-solid fa-paper-plane"></i></button>

                                            <form action="/admin/forms/<?= $form['id'] ?>/notifications/<?= $n['id'] ?>/delete" method="POST" class="js-confirm-action" data-confirm-title="Διαγραφή Ειδοποίησης" data-confirm-message="Θέλετε να διαγράψετε αυτή την ειδοποίηση;">
                                                <?= \App\Core\Csrf::field() ?>
                                                <button type="submit" class="btn btn-outline-danger btn-xs"><i class="fa-solid fa-trash"></i></button>
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

        <!-- Logs Box -->
        <div class="card p-4 mt-4">
            <h5 class="font-heading text-white mb-3"><i class="fa-solid fa-history text-primary me-2"></i>Ιστορικό Ειδοποιήσεων</h5>
            <div class="table-responsive">
                <table class="table align-middle table-sm">
                    <thead>
                        <tr>
                            <th>Ημερομηνία</th>
                            <th>Ειδοποίηση</th>
                            <th>Recipients</th>
                            <th>Κατάσταση</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-2">Δεν υπάρχει ιστορικό καταγραφών.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $l): ?>
                                <tr>
                                    <td class="small"><?= $l['created_at'] ?></td>
                                    <td><?= \App\Core\View::escape($l['notification_name'] ?? 'Δοκιμή') ?></td>
                                    <td class="small text-muted"><?= \App\Core\View::escape($l['recipient_summary']) ?></td>
                                    <td>
                                        <span class="badge <?= $l['status'] === 'sent' ? 'bg-success' : ($l['status'] === 'skipped' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                            <?= \App\Core\View::escape($l['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right column: Notification Add/Edit form -->
    <div class="col-md-5">
        <div class="card p-4">
            <h5 class="font-heading text-white mb-4" id="formActionTitle"><i class="fa-solid fa-plus text-primary me-2"></i>Νέα Ειδοποίηση</h5>
            
            <form id="notificationForm" action="/admin/forms/<?= $form['id'] ?>/notifications/create" method="POST">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" id="notificationId" name="notification_id">

                <div class="mb-3">
                    <label for="notif_name" class="form-label">Όνομα Ειδοποίησης</label>
                    <input type="text" class="form-control" id="notif_name" name="name" required placeholder="π.χ. IT Support Ticket">
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="notif_enabled" name="is_enabled" checked>
                    <label class="form-check-label text-white" for="notif_enabled">Ενεργή</label>
                </div>

                <div class="mb-3">
                    <label for="notif_trigger" class="form-label">Γεγονός Ενεργοποίησης (Trigger Event)</label>
                    <select class="form-select" id="notif_trigger" name="trigger_event">
                        <option value="draft">On Draft Save</option>
                        <option value="submit">On Submit</option>
                        <option value="start_review">On Start Review</option>
                        <option value="return">On Return</option>
                        <option value="resubmit">On Resubmit</option>
                        <option value="approve">On Approve</option>
                        <option value="reject">On Reject</option>
                        <option value="workflow_step">On Workflow Step</option>
                        <option value="final_approval">On Final Approval</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="notif_recipient_type" class="form-label">Τύπος Παραλήπτη (Recipient Type)</label>
                    <select class="form-select" id="notif_recipient_type" name="recipient_type">
                        <option value="fixed">Fixed Recipients (Emails specified below)</option>
                        <option value="submitter">Submitter (Author)</option>
                        <option value="manager">Manager of Submitter</option>
                        <option value="role">Users of Specific Role</option>
                        <option value="dept">Users of Specific Department</option>
                    </select>
                </div>

                <div class="mb-3 d-none" id="recipient_role_wrapper">
                    <label for="notif_recipient_role" class="form-label">Επιλογή Ρόλου</label>
                    <select class="form-select" id="notif_recipient_role" name="recipient_role_id">
                        <option value="">Επιλέξτε ρόλο...</option>
                        <?php 
                        $roles = \App\Core\Database::getInstance()->query("SELECT * FROM roles ORDER BY name ASC")->fetchAll();
                        foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3 d-none" id="recipient_dept_wrapper">
                    <label for="notif_recipient_dept" class="form-label">Επιλογή Τμήματος</label>
                    <select class="form-select" id="notif_recipient_dept" name="recipient_dept_id">
                        <option value="">Επιλέξτε τμήμα...</option>
                        <?php 
                        $depts = \App\Core\Database::getInstance()->query("SELECT DISTINCT directory_department FROM users WHERE directory_department != '' ORDER BY directory_department ASC")->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($depts as $dIdx => $dName): ?>
                            <option value="<?= $dIdx ?>"><?= htmlspecialchars($dName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3" id="notif_to_wrapper">
                    <label for="notif_to" class="form-label">Προς (To - static emails / {field:email})</label>
                    <input type="text" class="form-control" id="notif_to" name="to_recipients" placeholder="it@company.com, {field:email}">
                </div>

                <div class="mb-3">
                    <label for="notif_subject" class="form-label">Θέμα (Subject Template)</label>
                    <input type="text" class="form-control" id="notif_subject" name="subject_template" required placeholder="Νέα υποβολή: {form_name}">
                </div>

                <div class="mb-3">
                    <label for="notif_body" class="form-label">Μήνυμα (Body Template)</label>
                    <textarea class="form-control" id="notif_body" name="body_template" rows="5" required placeholder="Υποβλήθηκε νέο ticket... {all_fields}"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small"><i class="fa-solid fa-tags me-1"></i> Υποστηριζόμενα Smart Tags</label>
                    <div class="glass-panel p-2 small text-white-50" style="font-size: 11px; max-height: 120px; overflow-y: auto; background: rgba(255,255,255,0.03);">
                        <div><strong>Βασικά:</strong> <code>{form_name}</code>, <code>{form_id}</code>, <code>{submission_id}</code>, <code>{submission_uuid}</code>, <code>{submission_status}</code>, <code>{submitted_at}</code></div>
                        <div class="mt-1"><strong>Στοιχεία Χρήστη:</strong> <code>{submitter_name}</code>, <code>{submitter_email}</code>, <code>{manager_name}</code>, <code>{manager_email}</code>, <code>{manager_comments}</code></div>
                        <div class="mt-1"><strong>Πεδία Φόρμας:</strong>
                            <?php if (empty($fields)): ?>
                                <span class="text-muted">Καμία δυναμική επιλογή πεδίου.</span>
                            <?php else: ?>
                                <?php foreach ($fields as $f): ?>
                                    <code>{field:<?= htmlspecialchars($f['key']) ?>}</code>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Conditional Logic Setup Box -->
                <div class="border border-glass rounded p-3 mb-4">
                    <h6 class="text-white small font-heading mb-2">Κριτήρια Εμφάνισης (Conditional Logic)</h6>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="cond_enabled" name="cond_enabled">
                        <label class="form-check-label text-white" for="cond_enabled">Ενεργοποίηση</label>
                    </div>
                    <div class="d-none" id="cond_body">
                        <div class="mb-2">
                            <label class="form-label small text-white-50">Ενέργεια (Action)</label>
                            <select class="form-select form-select-sm" name="cond_action" id="cond_action">
                                <option value="show">Αποστολή (Send)</option>
                                <option value="hide">Μη αποστολή (Do not send)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small text-white-50">Συνθήκη (Match Mode)</label>
                            <select class="form-select form-select-sm" name="cond_match" id="cond_match">
                                <option value="all">Όλα τα κριτήρια (All)</option>
                                <option value="any">Οποιοδήποτε κριτήριο (Any)</option>
                            </select>
                        </div>
                        <div id="cond_rules_list" class="mb-2">
                            <!-- Rules will list here dynamically -->
                        </div>
                        <button type="button" class="btn btn-outline-success btn-xs" id="addRuleBtn"><i class="fa-solid fa-plus me-1"></i>Προσθήκη Κανόνα</button>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-premium w-100" id="saveNotifBtn">Αποθήκευση</button>
                    <button type="button" class="btn btn-outline-secondary w-50 d-none" id="cancelEditBtn">Ακύρωση</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Send Test Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="testEmailForm" method="POST">
            <?= \App\Core\Csrf::field() ?>
            <div class="modal-content bg-dark border border-secondary text-white">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title"><i class="fa-solid fa-paper-plane text-primary me-2"></i>Αποστολή Δοκιμής</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="test_email" class="form-label">Email Δοκιμής</label>
                        <input type="email" class="form-control" id="test_email" name="test_email" required placeholder="admin@example.local">
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Κλείσιμο</button>
                    <button type="submit" class="btn btn-premium">Αποστολή</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const fields = <?= json_encode($fields) ?>;
    const condEnabledChk = document.getElementById('cond_enabled');
    const condBody = document.getElementById('cond_body');
    const addRuleBtn = document.getElementById('addRuleBtn');
    const rulesList = document.getElementById('cond_rules_list');
    
    let currentRules = [];

    condEnabledChk.addEventListener('change', (e) => {
        if (e.target.checked) {
            condBody.classList.remove('d-none');
            if (currentRules.length === 0) {
                addRule();
            }
        } else {
            condBody.classList.add('d-none');
        }
    });

    addRuleBtn.addEventListener('click', () => {
        addRule();
    });

    function addRule(rule = { field: '', operator: 'equals', value: '' }) {
        currentRules.push(rule);
        renderRules();
    }

    function renderRules() {
        rulesList.innerHTML = '';
        currentRules.forEach((r, idx) => {
            const ruleDiv = document.createElement('div');
            ruleDiv.className = 'border border-glass rounded p-2 mb-2 cond-rule-item';
            ruleDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small text-muted">Κανόνας #${idx + 1}</span>
                    <button type="button" class="btn btn-outline-danger btn-xs border-0 remove-rule-btn" data-idx="${idx}"><i class="fa-solid fa-times"></i></button>
                </div>
                <div class="mb-1">
                    <select class="form-select form-select-sm rule-field-select" name="rules[${idx}][field]" required>
                        <option value="">Επιλέξτε πεδίο...</option>
                        ${fields.map(f => `<option value="${f.key}" ${f.key === r.field ? 'selected' : ''}>${f.label} (${f.key})</option>`).join('')}
                    </select>
                </div>
                <div class="mb-1">
                    <select class="form-select form-select-sm rule-operator-select" name="rules[${idx}][operator]" required>
                        <option value="equals" ${r.operator === 'equals' ? 'selected' : ''}>Ισούται με</option>
                        <option value="not_equals" ${r.operator === 'not_equals' ? 'selected' : ''}>Δεν ισούται με</option>
                        <option value="contains" ${r.operator === 'contains' ? 'selected' : ''}>Περιέχει</option>
                        <option value="not_contains" ${r.operator === 'not_contains' ? 'selected' : ''}>Δεν περιέχει</option>
                        <option value="greater_than" ${r.operator === 'greater_than' ? 'selected' : ''}>Μεγαλύτερο από</option>
                        <option value="less_than" ${r.operator === 'less_than' ? 'selected' : ''}>Μικρότερο από</option>
                        <option value="is_empty" ${r.operator === 'is_empty' ? 'selected' : ''}>Είναι κενό</option>
                        <option value="is_not_empty" ${r.operator === 'is_not_empty' ? 'selected' : ''}>Δεν είναι κενό</option>
                        <option value="checkbox_contains" ${r.operator === 'checkbox_contains' ? 'selected' : ''}>Checkbox contains option</option>
                    </select>
                </div>
                <div>
                    <input type="text" class="form-control form-control-sm rule-val-input" name="rules[${idx}][value]" value="${r.value || ''}" placeholder="Τιμή">
                </div>
            `;
            rulesList.appendChild(ruleDiv);
        });

        // Hide/show values input on specific operator selections
        rulesList.querySelectorAll('.rule-operator-select').forEach((sel, selIdx) => {
            const valInput = sel.closest('.cond-rule-item').querySelector('.rule-val-input');
            const toggleVal = () => {
                if (sel.value === 'is_empty' || sel.value === 'is_not_empty') {
                    valInput.classList.add('d-none');
                } else {
                    valInput.classList.remove('d-none');
                }
            };
            sel.addEventListener('change', toggleVal);
            toggleVal();
        });

        // Bind remove actions
        rulesList.querySelectorAll('.remove-rule-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const idx = parseInt(btn.dataset.idx);
                currentRules.splice(idx, 1);
                renderRules();
            });
        });
    }

    // Recipient Type dynamic fields visibility toggle
    const recType = document.getElementById('notif_recipient_type');
    const wrapperRole = document.getElementById('recipient_role_wrapper');
    const wrapperDept = document.getElementById('recipient_dept_wrapper');
    const wrapperTo = document.getElementById('notif_to_wrapper');

    const toggleRecipientFields = () => {
        wrapperRole.classList.add('d-none');
        wrapperDept.classList.add('d-none');
        wrapperTo.classList.add('d-none');

        if (recType.value === 'fixed') {
            wrapperTo.classList.remove('d-none');
        } else if (recType.value === 'role') {
            wrapperRole.classList.remove('d-none');
        } else if (recType.value === 'dept') {
            wrapperDept.classList.remove('d-none');
        }
    };
    recType.addEventListener('change', toggleRecipientFields);
    toggleRecipientFields();

    // Edit Notification Action Bindings
    const editButtons = document.querySelectorAll('.edit-notif-btn');
    const form = document.getElementById('notificationForm');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const formActionTitle = document.getElementById('formActionTitle');

    editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const data = JSON.parse(btn.dataset.notif);
            formActionTitle.innerHTML = '<i class="fa-solid fa-edit text-primary me-2"></i>Επεξεργασία Ειδοποίησης';
            form.action = `/admin/forms/<?= $form['id'] ?>/notifications/${data.id}/update`;
            document.getElementById('notif_name').value = data.name;
            document.getElementById('notif_enabled').checked = parseInt(data.is_enabled) === 1;
            document.getElementById('notif_to').value = data.to_recipients || '';
            document.getElementById('notif_subject').value = data.subject_template;
            document.getElementById('notif_body').value = data.body_template;

            if (document.getElementById('notif_trigger')) {
                document.getElementById('notif_trigger').value = data.trigger_event || 'submit';
            }
            if (document.getElementById('notif_recipient_type')) {
                document.getElementById('notif_recipient_type').value = data.recipient_type || 'fixed';
                toggleRecipientFields();
            }
            if (document.getElementById('notif_recipient_role')) {
                document.getElementById('notif_recipient_role').value = data.recipient_role_id || '';
            }
            if (document.getElementById('notif_recipient_dept')) {
                document.getElementById('notif_recipient_dept').value = data.recipient_dept_id || '';
            }

            // Load Conditional Logic
            const cond = JSON.parse(data.conditional_logic_json || '{}');
            if (cond && cond.enabled) {
                condEnabledChk.checked = true;
                condBody.classList.remove('d-none');
                document.getElementById('cond_action').value = cond.action || 'show';
                document.getElementById('cond_match').value = cond.match || 'all';
                currentRules = cond.rules || [];
                renderRules();
            } else {
                condEnabledChk.checked = false;
                condBody.classList.add('d-none');
                currentRules = [];
                rulesList.innerHTML = '';
            }

            cancelEditBtn.classList.remove('d-none');
        });
    });

    cancelEditBtn.addEventListener('click', () => {
        formActionTitle.innerHTML = '<i class="fa-solid fa-plus text-primary me-2"></i>Νέα Ειδοποίηση';
        form.action = '/admin/forms/<?= $form['id'] ?>/notifications/create';
        form.reset();
        toggleRecipientFields();
        condEnabledChk.checked = false;
        condBody.classList.add('d-none');
        currentRules = [];
        rulesList.innerHTML = '';
        cancelEditBtn.classList.add('d-none');
    });

    // Test Notification Modal setup
    const testModalEl = new bootstrap.Modal(document.getElementById('testEmailModal'));
    document.querySelectorAll('.test-notif-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const action = `/admin/forms/<?= $form['id'] ?>/notifications/${id}/test`;
            document.getElementById('testEmailForm').action = action;
            testModalEl.show();
        });
    });
});
</script>
