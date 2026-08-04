<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <a href="/admin/forms" class="btn btn-outline-secondary btn-sm mb-2"><i class="fa-solid fa-arrow-left"></i> <?= __('Back to Forms') ?></a>
        <h3 class="font-heading text-white mb-0"><?= \App\Core\View::escape($title) ?></h3>
    </div>
    <div class="btn-group">
        <a href="/admin/forms/<?= $form['id'] ?>/edit" class="btn btn-outline-primary"><i class="fa-solid fa-gear me-1"></i> <?= __('General Settings') ?></a>
        <a href="/admin/forms/<?= $form['id'] ?>/notifications" class="btn btn-primary active"><i class="fa-solid fa-bell me-1"></i> <?= __('Notifications') ?></a>
    </div>
</div>

<?php if ($success = \App\Core\Session::flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= \App\Core\View::escape($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= __('Close') ?>"></button>
    </div>
<?php endif; ?>

<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= \App\Core\View::escape($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= __('Close') ?>"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Left column: notifications list -->
    <div class="col-md-7">
        <div class="card p-4">
            <h5 class="font-heading text-white mb-4"><i class="fa-solid fa-bell text-primary me-2"></i><?= __('Notifications') ?></h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <th><?= __('Recipients') ?></th>
                            <th><?= __('Status') ?></th>
                            <th class="text-end"><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notifications)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4"><?= __('No notifications defined.') ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): ?>
                                <tr>
                                    <td>
                                        <strong><?= \App\Core\View::escape($n['name']) ?></strong>
                                        <?php 
                                        $cond = json_decode($n['conditional_logic_json'] ?? '{}', true);
                                        if ($cond && !empty($cond['enabled'])): ?>
                                            <span class="badge bg-warning text-dark ms-2" style="font-size:9px;"><?= __('Conditional') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= \App\Core\View::escape($n['to_recipients']) ?></td>
                                    <td>
                                        <span class="badge <?= $n['is_enabled'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $n['is_enabled'] ? __('Active') : __('Inactive') ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                                            <form action="/admin/forms/<?= $form['id'] ?>/notifications/<?= $n['id'] ?>/move-up" method="POST" style="display:inline;">
                                                <?= \App\Core\Csrf::field() ?>
                                                <button type="submit" class="btn btn-outline-secondary btn-xs" title="<?= __('Move Up') ?>"><i class="fa-solid fa-arrow-up"></i></button>
                                            </form>
                                            <form action="/admin/forms/<?= $form['id'] ?>/notifications/<?= $n['id'] ?>/move-down" method="POST" style="display:inline;">
                                                <?= \App\Core\Csrf::field() ?>
                                                <button type="submit" class="btn btn-outline-secondary btn-xs" title="<?= __('Move Down') ?>"><i class="fa-solid fa-arrow-down"></i></button>
                                            </form>

                                            <button type="button" class="btn btn-outline-info btn-xs edit-notif-btn" data-notif='<?= json_encode($n) ?>'><i class="fa-solid fa-edit"></i></button>
                                            
                                            <form action="/admin/forms/<?= $form['id'] ?>/notifications/<?= $n['id'] ?>/duplicate" method="POST">
                                                <?= \App\Core\Csrf::field() ?>
                                                <button type="submit" class="btn btn-outline-success btn-xs" title="<?= __('Copy') ?>"><i class="fa-solid fa-clone"></i></button>
                                            </form>

                                            <button type="button" class="btn btn-outline-warning btn-xs test-notif-btn" data-id="<?= $n['id'] ?>"><i class="fa-solid fa-paper-plane"></i></button>

                                            <form action="/admin/forms/<?= $form['id'] ?>/notifications/<?= $n['id'] ?>/delete" method="POST" class="js-confirm-action" data-confirm-title="<?= __('Delete Notification') ?>" data-confirm-message="<?= __('Do you want to delete this notification?') ?>">
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
            <h5 class="font-heading text-white mb-3"><i class="fa-solid fa-history text-primary me-2"></i><?= __('Notification Logs') ?></h5>
            <div class="table-responsive">
                <table class="table align-middle table-sm">
                    <thead>
                        <tr>
                            <th><?= __('Date') ?></th>
                            <th><?= __('Notification') ?></th>
                            <th><?= __('Recipients') ?></th>
                            <th><?= __('Status') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-2"><?= __('No logs record.') ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $l): ?>
                                <tr>
                                    <td class="small"><?= $l['created_at'] ?></td>
                                    <td><?= \App\Core\View::escape($l['notification_name'] ?? __('Test')) ?></td>
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
            <h5 class="font-heading text-white mb-4" id="formActionTitle"><i class="fa-solid fa-plus text-primary me-2"></i><?= __('New Notification') ?></h5>
            
            <form id="notificationForm" action="/admin/forms/<?= $form['id'] ?>/notifications/create" method="POST">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" id="notificationId" name="notification_id">

                <div class="mb-3">
                    <label for="notif_name" class="form-label"><?= __('Notification Name') ?></label>
                    <input type="text" class="form-control" id="notif_name" name="name" required placeholder="<?= __('e.g. IT Support Ticket') ?>">
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="notif_enabled" name="is_enabled" checked>
                    <label class="form-check-label text-white" for="notif_enabled"><?= __('Active') ?></label>
                </div>

                <div class="mb-3">
                    <label for="notif_trigger" class="form-label"><?= __('Trigger Event') ?></label>
                    <select class="form-select" id="notif_trigger" name="trigger_event">
                        <option value="draft"><?= __('On Draft Save') ?></option>
                        <option value="submit"><?= __('On Submit') ?></option>
                        <option value="start_review"><?= __('On Start Review') ?></option>
                        <option value="return"><?= __('On Return') ?></option>
                        <option value="resubmit"><?= __('On Resubmit') ?></option>
                        <option value="approve"><?= __('On Approve') ?></option>
                        <option value="reject"><?= __('On Reject') ?></option>
                        <option value="workflow_step"><?= __('On Workflow Step') ?></option>
                        <option value="final_approval"><?= __('On Final Approval') ?></option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="notif_recipient_type" class="form-label"><?= __('Recipient Type') ?></label>
                    <select class="form-select" id="notif_recipient_type" name="recipient_type">
                        <option value="fixed"><?= __('Fixed Recipients') ?></option>
                        <option value="submitter"><?= __('Submitter') ?></option>
                        <option value="manager"><?= __('Manager of Submitter') ?></option>
                        <option value="role"><?= __('Role') ?></option>
                        <option value="dept"><?= __('Department') ?></option>
                    </select>
                </div>

                <div class="mb-3 d-none" id="recipient_role_wrapper">
                    <label for="notif_recipient_role" class="form-label"><?= __('Select Role') ?></label>
                    <select class="form-select" id="notif_recipient_role" name="recipient_role_id">
                        <option value=""><?= __('Select Role') ?>...</option>
                        <?php 
                        $roles = \App\Core\Database::getInstance()->query("SELECT * FROM roles ORDER BY name ASC")->fetchAll();
                        foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3 d-none" id="recipient_dept_wrapper">
                    <label for="notif_recipient_dept" class="form-label"><?= __('Select Department') ?></label>
                    <select class="form-select" id="notif_recipient_dept" name="recipient_dept_id">
                        <option value=""><?= __('Select Department') ?>...</option>
                        <?php 
                        $depts = \App\Core\Database::getInstance()->query("SELECT DISTINCT directory_department FROM users WHERE directory_department != '' ORDER BY directory_department ASC")->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($depts as $dIdx => $dName): ?>
                            <option value="<?= $dIdx ?>"><?= htmlspecialchars($dName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3" id="notif_to_wrapper">
                    <label for="notif_to" class="form-label"><?= __('To (static emails / {field:email})') ?></label>
                    <input type="text" class="form-control" id="notif_to" name="to_recipients" placeholder="it@company.com, {field:email}">
                </div>

                <div class="mb-3">
                    <label for="notif_subject" class="form-label"><?= __('Subject Template') ?></label>
                    <input type="text" class="form-control" id="notif_subject" name="subject_template" required placeholder="<?= __('New submission: {form_name}') ?>">
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="notif_body" class="form-label mb-0"><?= __('HTML Body Template') ?></label>
                        <button type="button" class="btn btn-outline-info btn-xs" id="previewHtmlBtn"><i class="fa-solid fa-eye me-1"></i><?= __('Preview HTML') ?></button>
                    </div>
                    <textarea class="form-control" id="notif_body" name="body_template" rows="5" required placeholder="<p>Hello,</p><p>A new submission was received for <strong>{form_name}</strong>.</p>{all_fields}"></textarea>
                </div>

                <div class="mb-3">
                    <label for="notif_body_text" class="form-label"><?= __('Plain Text Fallback') ?></label>
                    <textarea class="form-control" id="notif_body_text" name="body_text_template" rows="3" placeholder="<?= __('Hello, A new submission was received for {form_name}.') ?>"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small"><i class="fa-solid fa-tags me-1"></i> <?= __('Available Smart Tags') ?></label>
                    <div class="glass-panel p-2 small text-white" style="font-size: 11px; max-height: 140px; overflow-y: auto; background: rgba(255,255,255,0.05);">
                        <div class="mb-1"><strong><?= __('Basic') ?>:</strong>
                            <button type="button" class="btn btn-outline-secondary btn-xs insert-tag-btn" data-tag="{form_name}">{form_name}</button>
                            <button type="button" class="btn btn-outline-secondary btn-xs insert-tag-btn" data-tag="{form_id}">{form_id}</button>
                            <button type="button" class="btn btn-outline-secondary btn-xs insert-tag-btn" data-tag="{submission_id}">{submission_id}</button>
                            <button type="button" class="btn btn-outline-secondary btn-xs insert-tag-btn" data-tag="{submission_uuid}">{submission_uuid}</button>
                            <button type="button" class="btn btn-outline-secondary btn-xs insert-tag-btn" data-tag="{submission_status}">{submission_status}</button>
                            <button type="button" class="btn btn-outline-secondary btn-xs insert-tag-btn" data-tag="{submitted_at}">{submitted_at}</button>
                            <button type="button" class="btn btn-outline-secondary btn-xs insert-tag-btn" data-tag="{all_fields}">{all_fields}</button>
                        </div>
                        <div class="mb-1"><strong><?= __('User Details') ?>:</strong>
                            <button type="button" class="btn btn-outline-secondary btn-xs insert-tag-btn" data-tag="{submitter_name}">{submitter_name}</button>
                            <button type="button" class="btn btn-outline-secondary btn-xs insert-tag-btn" data-tag="{submitter_email}">{submitter_email}</button>
                            <button type="button" class="btn btn-outline-secondary btn-xs insert-tag-btn" data-tag="{manager_name}">{manager_name}</button>
                            <button type="button" class="btn btn-outline-secondary btn-xs insert-tag-btn" data-tag="{manager_email}">{manager_email}</button>
                            <button type="button" class="btn btn-outline-secondary btn-xs insert-tag-btn" data-tag="{manager_comments}">{manager_comments}</button>
                        </div>
                        <div><strong><?= __('Form Fields') ?>:</strong>
                            <?php if (empty($fields)): ?>
                                <span class="text-muted"><?= __('No dynamic fields') ?></span>
                            <?php else: ?>
                                <?php foreach ($fields as $f): ?>
                                    <button type="button" class="btn btn-outline-primary btn-xs insert-tag-btn me-1 mb-1" data-tag="{field:<?= htmlspecialchars($f['key']) ?>}"><?= htmlspecialchars($f['label']) ?> (<code>{field:<?= htmlspecialchars($f['key']) ?>}</code>)</button>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Conditional Logic Setup Box -->
                <div class="border border-glass rounded p-3 mb-4">
                    <h6 class="text-white small font-heading mb-2"><?= __('Conditional Logic') ?></h6>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="cond_enabled" name="cond_enabled">
                        <label class="form-check-label text-white" for="cond_enabled"><?= __('Enable') ?></label>
                    </div>
                    <div class="d-none" id="cond_body">
                        <div class="mb-2">
                            <label class="form-label small text-white-50"><?= __('Action') ?></label>
                            <select class="form-select form-select-sm" name="cond_action" id="cond_action">
                                <option value="show"><?= __('Send') ?></option>
                                <option value="hide"><?= __('Do not send') ?></option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small text-white-50"><?= __('Match Mode') ?></label>
                            <select class="form-select form-select-sm" name="cond_match" id="cond_match">
                                <option value="all"><?= __('All') ?></option>
                                <option value="any"><?= __('Any') ?></option>
                            </select>
                        </div>
                        <div id="cond_rules_list" class="mb-2">
                            <!-- Rules will list here dynamically -->
                        </div>
                        <button type="button" class="btn btn-outline-success btn-xs" id="addRuleBtn"><i class="fa-solid fa-plus me-1"></i><?= __('Add Rule') ?></button>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-premium w-100" id="saveNotifBtn"><?= __('Save') ?></button>
                    <button type="button" class="btn btn-outline-secondary w-50 d-none" id="cancelEditBtn"><?= __('Cancel') ?></button>
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
                    <h5 class="modal-title"><i class="fa-solid fa-paper-plane text-primary me-2"></i><?= __('Send Test Email') ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= __('Close') ?>"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="test_email" class="form-label"><?= __('Test Email') ?></label>
                        <input type="email" class="form-control" id="test_email" name="test_email" required placeholder="admin@example.local">
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('Close') ?></button>
                    <button type="submit" class="btn btn-premium"><?= __('Send') ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Safe HTML Preview Modal -->
<div class="modal fade" id="previewHtmlModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark border border-secondary text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="fa-solid fa-eye text-primary me-2"></i><?= __('Preview HTML') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= __('Close') ?>"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="previewIframe" style="width:100%; height:400px; border:none; background:#ffffff;"></iframe>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('Close') ?></button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const fields = <?= json_encode($fields) ?>;
    const condEnabledChk = document.getElementById('cond_enabled');
    const condBody = document.getElementById('cond_body');
    const addRuleBtn = document.getElementById('addRuleBtn');
    const rulesList = document.getElementById('cond_rules_list');
    let activeInput = document.getElementById('notif_body');

    // Track active input for Smart Tag insertion
    const subjectInput = document.getElementById('notif_subject');
    const bodyInput = document.getElementById('notif_body');
    const bodyTextInput = document.getElementById('notif_body_text');

    [subjectInput, bodyInput, bodyTextInput].forEach(input => {
        if (input) {
            input.addEventListener('focus', () => { activeInput = input; });
        }
    });

    // Handle Smart Tag insertion at cursor position
    document.querySelectorAll('.insert-tag-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const tag = btn.dataset.tag;
            if (!activeInput) activeInput = bodyInput;

            const start = activeInput.selectionStart || 0;
            const end = activeInput.selectionEnd || 0;
            const val = activeInput.value;
            activeInput.value = val.substring(0, start) + tag + val.substring(end);
            activeInput.selectionStart = activeInput.selectionEnd = start + tag.length;
            activeInput.focus();
        });
    });

    // Safe HTML preview handler
    const previewModalEl = new bootstrap.Modal(document.getElementById('previewHtmlModal'));
    const previewBtn = document.getElementById('previewHtmlBtn');
    if (previewBtn) {
        previewBtn.addEventListener('click', () => {
            const rawHtml = bodyInput.value || '<p class="text-muted">Empty message.</p>';
            const iframe = document.getElementById('previewIframe');
            // Write sanitized content into iframe with scripts disabled
            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write(`
                <!DOCTYPE html>
                <html>
                <head><meta charset="utf-8"><style>body { font-family: sans-serif; padding: 20px; color: #333; line-height: 1.6; }</style></head>
                <body>${rawHtml}</body>
                </html>
            `);
            doc.close();
            // Ensure scripts inside iframe cannot run
            iframe.setAttribute('sandbox', 'allow-same-origin');
            previewModalEl.show();
        });
    }
    
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
                    <span class="small text-muted">Rule #${idx + 1}</span>
                    <button type="button" class="btn btn-outline-danger btn-xs border-0 remove-rule-btn" data-idx="${idx}"><i class="fa-solid fa-times"></i></button>
                </div>
                <div class="mb-1">
                    <select class="form-select form-select-sm rule-field-select" name="rules[${idx}][field]" required>
                        <option value="">Select field...</option>
                        ${fields.map(f => `<option value="${f.key}" ${f.key === r.field ? 'selected' : ''}>${f.label} (${f.key})</option>`).join('')}
                    </select>
                </div>
                <div class="mb-1">
                    <select class="form-select form-select-sm rule-operator-select" name="rules[${idx}][operator]" required>
                        <option value="equals" ${r.operator === 'equals' ? 'selected' : ''}><?= __('Equals') ?></option>
                        <option value="not_equals" ${r.operator === 'not_equals' ? 'selected' : ''}><?= __('Not Equals') ?></option>
                        <option value="contains" ${r.operator === 'contains' ? 'selected' : ''}><?= __('Contains') ?></option>
                        <option value="not_contains" ${r.operator === 'not_contains' ? 'selected' : ''}><?= __('Does not contain') ?></option>
                        <option value="greater_than" ${r.operator === 'greater_than' ? 'selected' : ''}><?= __('Greater than') ?></option>
                        <option value="less_than" ${r.operator === 'less_than' ? 'selected' : ''}><?= __('Less than') ?></option>
                        <option value="is_empty" ${r.operator === 'is_empty' ? 'selected' : ''}><?= __('Is empty') ?></option>
                        <option value="is_not_empty" ${r.operator === 'is_not_empty' ? 'selected' : ''}><?= __('Is not empty') ?></option>
                        <option value="checkbox_contains" ${r.operator === 'checkbox_contains' ? 'selected' : ''}><?= __('Checkbox contains option') ?></option>
                    </select>
                </div>
                <div>
                    <input type="text" class="form-control form-control-sm rule-val-input" name="rules[${idx}][value]" value="${r.value || ''}" placeholder="Value">
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
            formActionTitle.innerHTML = '<i class="fa-solid fa-edit text-primary me-2"></i><?= __('Edit Notification') ?>';
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
        formActionTitle.innerHTML = '<i class="fa-solid fa-plus text-primary me-2"></i><?= __('New Notification') ?>';
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
