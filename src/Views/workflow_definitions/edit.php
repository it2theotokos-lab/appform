<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="h3 m-0 text-white"><i class="fa-solid fa-route me-2 text-primary"></i> Σχεδίαση Workflow: <?= \App\Core\View::escape($wf['name']) ?> <span class="badge bg-info ms-2" style="font-size:0.75rem;">Version v<?= (int)$wf['version'] ?></span></h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/workflows" class="btn btn-secondary me-2">
            <i class="fa-solid fa-arrow-left me-1"></i> Επιστροφή
        </a>
        <button type="button" class="btn btn-premium" id="save-workflow-btn">
            <i class="fa-solid fa-save me-1"></i> Αποθήκευση Workflow
        </button>
    </div>
</div>

<div class="row">
    <!-- Left Panel: Steps List & Reordering -->
    <div class="col-md-4">
        <div class="card p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-white m-0"><i class="fa-solid fa-list me-2 text-primary"></i> Βήματα Ροής</h6>
                <button type="button" class="btn btn-xs btn-outline-primary" id="add-step-btn">
                    <i class="fa-solid fa-plus me-1"></i> Προσθήκη
                </button>
            </div>
            
            <div id="steps-container" class="list-group list-group-dark">
                <!-- Draggable step list will render dynamically -->
            </div>
        </div>
    </div>

    <!-- Center Panel: Visual Flow Timeline -->
    <div class="col-md-4">
        <div class="card p-3 mb-4">
            <h6 class="text-white mb-3"><i class="fa-solid fa-diagram-next me-2 text-primary"></i> Οπτική Αναπαράσταση</h6>
            <div id="visual-flow" class="flow-timeline py-2">
                <!-- Visual flow timeline cards -->
            </div>
        </div>
    </div>

    <!-- Right Panel: Step Properties Editor Panel -->
    <div class="col-md-4">
        <div class="card p-3" id="step-properties-card">
            <div id="no-step-selected-msg" class="text-center py-4 text-muted">
                <i class="fa-solid fa-hand-pointer fa-2x mb-2"></i>
                <p class="m-0">Επιλέξτε ένα βήμα από τη λίστα για επεξεργασία των ιδιοτήτων του.</p>
            </div>
            
            <div id="step-editor-form" class="d-none">
                <h6 class="text-white mb-3"><i class="fa-solid fa-sliders me-2 text-primary"></i> Ιδιότητες Βήματος</h6>
                
                <div class="mb-3">
                    <label for="step-name" class="form-label text-white-50 small">Όνομα Βήματος</label>
                    <input type="text" class="form-control form-control-sm" id="step-name">
                </div>

                <div class="mb-3">
                    <label for="step-type" class="form-label text-white-50 small">Τύπος Ενέργειας</label>
                    <select class="form-select form-select-sm" id="step-type">
                        <option value="review">Review (Απλή ανασκόπηση)</option>
                        <option value="approval">Approval (Έγκριση χωρίς υπογραφή)</option>
                        <option value="signature">Signature (Έγκριση με υπογραφή)</option>
                        <option value="finalization">Finalization (Οριστικοποίηση)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="step-assignment-type" class="form-label text-white-50 small">Ανάθεση σε</label>
                    <select class="form-select form-select-sm" id="step-assignment-type">
                        <option value="user">Συγκεκριμένο Χρήστη</option>
                        <option value="role">Ρόλο Χρηστών</option>
                        <option value="document_creator">Δημιουργό Εγγράφου</option>
                        <option value="manager">Υπεύθυνο (Manager)</option>
                        <option value="administrator">Διαχειριστή (Admin)</option>
                    </select>
                </div>

                <div class="mb-3 d-none" id="user-select-group">
                    <label for="step-user-id" class="form-label text-white-50 small">Επιλογή Χρήστη</label>
                    <select class="form-select form-select-sm" id="step-user-id">
                        <option value="">-- Επιλέξτε Χρήστη --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int)$u['id'] ?>"><?= \App\Core\View::escape($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3 d-none" id="role-select-group">
                    <label for="step-role-id" class="form-label text-white-50 small">Επιλογή Ρόλου</label>
                    <select class="form-select form-select-sm" id="step-role-id">
                        <option value="">-- Επιλέξτε Ρόλο --</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int)$r['id'] ?>"><?= \App\Core\View::escape($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3 d-none" id="signature-field-group">
                    <label for="step-sig-field-key" class="form-label text-white-50 small">Field Key Υπογραφής (Designer)</label>
                    <input type="text" class="form-control form-control-sm" id="step-sig-field-key" placeholder="π.χ. signature_1">
                    <span class="text-muted small" style="font-size:0.65rem;">Το κλειδί του πεδίου υπογραφής στο visual designer.</span>
                </div>

                <div class="mb-3">
                    <label for="step-approval-mode" class="form-label text-white-50 small">Λειτουργία Έγκρισης</label>
                    <select class="form-select form-select-sm" id="step-approval-mode">
                        <option value="sequential">Διαδοχική (Sequential)</option>
                        <option value="parallel_all">Παράλληλη - Όλοι (Parallel All)</option>
                        <option value="parallel_any">Παράλληλη - Οποιοσδήποτε (Parallel Any)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="step-due-days" class="form-label text-white-50 small">Προθεσμία (Ημέρες)</label>
                    <input type="number" class="form-control form-control-sm" id="step-due-days" min="1" placeholder="Κανένα όριο">
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="step-allow-return" checked>
                        <label class="form-check-label text-white small" for="step-allow-return">Επιτρέπεται Επιστροφή για Διορθώσεις</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="step-allow-reject" checked>
                        <label class="form-check-label text-white small" for="step-allow-reject">Επιτρέπεται Απόρριψη</label>
                    </div>
                </div>

                <button type="button" class="btn btn-sm btn-outline-danger w-100 mt-3" id="delete-step-btn">
                    <i class="fa-solid fa-trash-can me-1"></i> Διαγραφή Βήματος
                </button>
            </div>
        </div>
    </div>
</div>

<form id="workflow-save-form" action="/admin/workflows/<?= (int)$wf['id'] ?>" method="POST" class="d-none">
    <?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="name" value="<?= \App\Core\View::escape($wf['name']) ?>">
    <input type="hidden" name="description" value="<?= \App\Core\View::escape($wf['description'] ?? '') ?>">
    <input type="hidden" name="entity_type" value="<?= \App\Core\View::escape($wf['entity_type'] ?? 'document') ?>">
    <input type="hidden" name="entity_id" value="<?= (int)($wf['entity_id'] ?? 0) ?>">
    <input type="hidden" name="document_template_id" value="<?= (int)($wf['document_template_id'] ?? 0) ?>">
    <input type="hidden" name="form_id" value="<?= (int)(($wf['entity_type'] ?? 'document') === 'form' ? ($wf['entity_id'] ?? 0) : 0) ?>">
    <input type="hidden" name="steps_data" id="steps-data-input">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let steps = <?= json_encode($steps) ?> || [];
    let selectedStepIndex = null;

    const stepsContainer = document.getElementById('steps-container');
    const visualFlow = document.getElementById('visual-flow');
    const noStepSelectedMsg = document.getElementById('no-step-selected-msg');
    const stepEditorForm = document.getElementById('step-editor-form');

    // Form inputs mapping
    const inputName = document.getElementById('step-name');
    const inputType = document.getElementById('step-type');
    const inputAssignmentType = document.getElementById('step-assignment-type');
    const inputUserId = document.getElementById('step-user-id');
    const inputRoleId = document.getElementById('step-role-id');
    const inputSigFieldKey = document.getElementById('step-sig-field-key');
    const inputApprovalMode = document.getElementById('step-approval-mode');
    const inputDueDays = document.getElementById('step-due-days');
    const inputAllowReturn = document.getElementById('step-allow-return');
    const inputAllowReject = document.getElementById('step-allow-reject');

    function renderSteps() {
        stepsContainer.innerHTML = '';
        visualFlow.innerHTML = '';

        if (steps.length === 0) {
            stepsContainer.innerHTML = '<div class="text-center text-muted py-3 small">Δεν υπάρχουν βήματα.</div>';
            visualFlow.innerHTML = '<div class="text-center text-muted py-3 small">Κενή ροή.</div>';
            return;
        }

        steps.forEach((step, idx) => {
            // Left list
            let item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center text-start ' + (selectedStepIndex === idx ? 'active' : '');
            item.innerHTML = `
                <div>
                    <span class="badge bg-secondary me-2">${idx + 1}</span>
                    <span class="text-white">${escapeHtml(step.name)}</span>
                    <div class="small text-muted mt-1" style="font-size:0.7rem;">${step.step_type} | Assigned: ${step.assignment_type}</div>
                </div>
                <div class="d-flex">
                    <span class="btn-drag me-2" style="cursor:grab;" onclick="event.stopPropagation()"><i class="fa-solid fa-grip-lines text-muted"></i></span>
                </div>
            `;
            item.addEventListener('click', () => selectStep(idx));
            stepsContainer.appendChild(item);

            // Visual Flow timeline card
            let card = document.createElement('div');
            card.className = 'flow-step-card p-2 mb-3 bg-dark border border-secondary rounded position-relative';
            card.innerHTML = `
                <div class="text-white font-weight-bold" style="font-size:0.85rem;">${idx + 1}. ${escapeHtml(step.name)}</div>
                <div class="text-muted small mt-1" style="font-size:0.75rem;">
                    <i class="fa-solid fa-gears me-1"></i> ${step.step_type}<br>
                    <i class="fa-solid fa-user me-1"></i> ${step.assignment_type}
                    ${step.due_days ? `<br><i class="fa-solid fa-clock me-1"></i> ${step.due_days} ημ.` : ''}
                </div>
            `;
            visualFlow.appendChild(card);
            
            if (idx < steps.length - 1) {
                let arrow = document.createElement('div');
                arrow.className = 'text-center my-2 text-muted';
                arrow.innerHTML = '<i class="fa-solid fa-arrow-down"></i>';
                visualFlow.appendChild(arrow);
            }
        });
    }

    function selectStep(idx) {
        selectedStepIndex = idx;
        renderSteps();
        
        noStepSelectedMsg.classList.add('d-none');
        stepEditorForm.classList.remove('d-none');

        const step = steps[idx];
        inputName.value = step.name || '';
        inputType.value = step.step_type || 'approval';
        inputAssignmentType.value = step.assignment_type || 'role';
        inputUserId.value = step.assigned_user_id || '';
        inputRoleId.value = step.assigned_role_id || '';
        inputSigFieldKey.value = step.signature_field_key || '';
        inputApprovalMode.value = step.approval_mode || 'sequential';
        inputDueDays.value = step.due_days || '';
        inputAllowReturn.checked = parseInt(step.allow_return) !== 0;
        inputAllowReject.checked = parseInt(step.allow_reject) !== 0;

        toggleAssignmentGroups();
    }

    function toggleAssignmentGroups() {
        const type = inputAssignmentType.value;
        document.getElementById('user-select-group').classList.toggle('d-none', type !== 'user');
        document.getElementById('role-select-group').classList.toggle('d-none', type !== 'role');
        document.getElementById('signature-field-group').classList.toggle('d-none', inputType.value !== 'signature');
    }

    // Input listeners mapping updates
    inputName.addEventListener('input', function() {
        if (selectedStepIndex !== null) {
            steps[selectedStepIndex].name = this.value;
            renderSteps();
        }
    });

    [inputType, inputAssignmentType, inputUserId, inputRoleId, inputSigFieldKey, inputApprovalMode, inputDueDays].forEach(input => {
        input.addEventListener('change', function() {
            if (selectedStepIndex !== null) {
                const prop = this.id.replace('step-', '').replace('-', '_').replace('sig_field_key', 'signature_field_key');
                steps[selectedStepIndex][prop] = this.value;
                toggleAssignmentGroups();
                renderSteps();
            }
        });
    });

    [inputAllowReturn, inputAllowReject].forEach(chk => {
        chk.addEventListener('change', function() {
            if (selectedStepIndex !== null) {
                const prop = this.id.replace('step-', '').replace('-', '_');
                steps[selectedStepIndex][prop] = this.checked ? 1 : 0;
            }
        });
    });

    document.getElementById('add-step-btn').addEventListener('click', function() {
        steps.push({
            name: 'Νέο Βήμα ' + (steps.length + 1),
            step_type: 'approval',
            assignment_type: 'role',
            assigned_user_id: '',
            assigned_role_id: '',
            signature_field_key: '',
            approval_mode: 'sequential',
            due_days: '',
            allow_return: 1,
            allow_reject: 1
        });
        selectStep(steps.length - 1);
    });

    document.getElementById('delete-step-btn').addEventListener('click', function() {
        if (selectedStepIndex !== null) {
            steps.splice(selectedStepIndex, 1);
            selectedStepIndex = null;
            stepEditorForm.classList.add('d-none');
            noStepSelectedMsg.classList.remove('d-none');
            renderSteps();
        }
    });

    document.getElementById('save-workflow-btn').addEventListener('click', function() {
        document.getElementById('steps-data-input').value = JSON.stringify(steps);
        document.getElementById('workflow-save-form').submit();
    });

    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    renderSteps();
});
</script>
