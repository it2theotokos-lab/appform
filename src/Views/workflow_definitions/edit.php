<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="h3 m-0 text-white"><i class="fa-solid fa-route me-2 text-primary"></i> <?= __('Edit Workflow') ?>: <?= \App\Core\View::escape($wf['name']) ?> <span class="badge bg-info ms-2" style="font-size:0.75rem;">Version v<?= (int)$wf['version'] ?></span></h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/workflows" class="btn btn-secondary me-2">
            <i class="fa-solid fa-arrow-left me-1"></i> <?= __('Back') ?>
        </a>
        <button type="button" class="btn btn-premium" id="save-workflow-btn">
            <i class="fa-solid fa-save me-1"></i> <?= __('Save Workflow') ?>
        </button>
    </div>
</div>

<div class="row">
    <!-- Left Panel: Steps List & Reordering -->
    <div class="col-md-4">
        <div class="card p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-white m-0"><i class="fa-solid fa-list me-2 text-primary"></i> <?= __('Workflow Steps') ?></h6>
                <button type="button" class="btn btn-xs btn-outline-primary" id="add-step-btn">
                    <i class="fa-solid fa-plus me-1"></i> <?= __('Add') ?>
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
            <h6 class="text-white mb-3"><i class="fa-solid fa-diagram-next me-2 text-primary"></i> <?= __('Visual Flow') ?></h6>
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
                <p class="m-0"><?= __('Select a step from the list to edit its properties.') ?></p>
            </div>
            
            <div id="step-editor-form" class="d-none">
                <h6 class="text-white mb-3"><i class="fa-solid fa-sliders me-2 text-primary"></i> <?= __('Step Properties') ?></h6>
                
                <div class="mb-3">
                    <label for="step-name" class="form-label text-white-50 small"><?= __('Step Name') ?></label>
                    <input type="text" class="form-control form-control-sm" id="step-name">
                </div>

                <div class="mb-3">
                    <label for="step-type" class="form-label text-white-50 small"><?= __('Step Type') ?></label>
                    <select class="form-select form-select-sm" id="step-type">
                        <option value="review"><?= __('Review (Simple inspection)') ?></option>
                        <option value="approval"><?= __('Approval (no signature)') ?></option>
                        <option value="signature"><?= __('Signature (approval with signature)') ?></option>
                        <option value="finalization"><?= __('Finalization') ?></option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="step-assignment-type" class="form-label text-white-50 small"><?= __('Assign To') ?></label>
                    <select class="form-select form-select-sm" id="step-assignment-type">
                        <option value="user"><?= __('Specific User') ?></option>
                        <option value="role"><?= __('User Role') ?></option>
                        <option value="document_creator"><?= __('Document Creator') ?></option>
                        <option value="manager"><?= __('Responsible (Manager)') ?></option>
                        <option value="administrator"><?= __('Administrator (Admin)') ?></option>
                    </select>
                </div>

                <div class="mb-3 d-none" id="user-select-group">
                    <label for="step-user-id" class="form-label text-white-50 small"><?= __('Select User') ?></label>
                    <select class="form-select form-select-sm" id="step-user-id">
                        <option value="">-- <?= __('Select User') ?> --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int)$u['id'] ?>"><?= \App\Core\View::escape($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3 d-none" id="role-select-group">
                    <label for="step-role-id" class="form-label text-white-50 small"><?= __('Select Role') ?></label>
                    <select class="form-select form-select-sm" id="step-role-id">
                        <option value="">-- <?= __('Select Role') ?> --</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int)$r['id'] ?>"><?= \App\Core\View::escape($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3 d-none" id="signature-field-group">
                    <label for="step-sig-field-key" class="form-label text-white-50 small"><?= __('Signature Field Key (Designer)') ?></label>
                    <input type="text" class="form-control form-control-sm" id="step-sig-field-key" placeholder="<?= __('e.g. signature_1') ?>">
                    <span class="text-muted small" style="font-size:0.65rem;"><?= __('The field key of the signature in the visual designer.') ?></span>
                </div>

                <div class="mb-3">
                    <label for="step-approval-mode" class="form-label text-white-50 small"><?= __('Approval Mode') ?></label>
                    <select class="form-select form-select-sm" id="step-approval-mode">
                        <option value="sequential"><?= __('Sequential') ?></option>
                        <option value="parallel_all"><?= __('Parallel All') ?></option>
                        <option value="parallel_any"><?= __('Parallel Any') ?></option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="step-due-days" class="form-label text-white-50 small"><?= __('Deadline (Days)') ?></label>
                    <input type="number" class="form-control form-control-sm" id="step-due-days" min="1" placeholder="<?= __('No limit') ?>">
                </div>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="step-allow-return">
                    <label class="form-check-label text-white small" for="step-allow-return"><?= __('Allow Return for Corrections') ?></label>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="step-allow-reject">
                    <label class="form-check-label text-white small" for="step-allow-reject"><?= __('Allow Rejection') ?></label>
                </div>

                <div class="d-grid">
                    <button type="button" class="btn btn-sm btn-outline-danger" id="delete-step-btn">
                        <i class="fa-solid fa-trash-can me-1"></i> <?= __('Delete Step') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let steps = <?= json_encode($steps ?? []) ?>;
    let selectedStepIndex = null;

    const stepsContainer = document.getElementById('steps-container');
    const visualFlow = document.getElementById('visual-flow');
    const noStepMsg = document.getElementById('no-step-selected-msg');
    const stepForm = document.getElementById('step-editor-form');

    const inputName = document.getElementById('step-name');
    const selectType = document.getElementById('step-type');
    const selectAssignType = document.getElementById('step-assignment-type');
    const groupUser = document.getElementById('user-select-group');
    const selectUser = document.getElementById('step-user-id');
    const groupRole = document.getElementById('role-select-group');
    const selectRole = document.getElementById('step-role-id');
    const groupSig = document.getElementById('signature-field-group');
    const inputSigKey = document.getElementById('step-sig-field-key');
    const selectMode = document.getElementById('step-approval-mode');
    const inputDue = document.getElementById('step-due-days');
    const chkReturn = document.getElementById('step-allow-return');
    const chkReject = document.getElementById('step-allow-reject');

    function renderList() {
        stepsContainer.innerHTML = '';
        visualFlow.innerHTML = '';

        if (steps.length === 0) {
            stepsContainer.innerHTML = '<div class="text-center text-muted py-3 small"><?= __('No steps defined.') ?></div>';
            visualFlow.innerHTML = '<div class="text-center text-muted py-3 small"><?= __('Empty workflow.') ?></div>';
            deselectStep();
            return;
        }

        steps.forEach((step, index) => {
            // Render list item
            const item = document.createElement('div');
            item.className = `list-group-item list-group-item-action d-flex justify-content-between align-items-center ${selectedStepIndex === index ? 'active' : ''}`;
            item.style.cursor = 'pointer';
            item.innerHTML = `
                <div>
                    <i class="fa-solid fa-grip-vertical text-muted me-2 drag-handle" style="cursor:grab;"></i>
                    <span class="font-weight-bold">${escapeHtml(step.name)}</span>
                    <small class="d-block text-muted" style="font-size:0.7rem;">${step.step_type.toUpperCase()} | ${step.assignment_type}</small>
                </div>
                <span class="badge bg-secondary rounded-pill">${index + 1}</span>
            `;
            item.addEventListener('click', function(e) {
                if (!e.target.classList.contains('drag-handle')) {
                    selectStep(index);
                }
            });
            stepsContainer.appendChild(item);

            // Render visual timeline card
            const flowCard = document.createElement('div');
            flowCard.className = 'card p-2 mb-2 bg-dark text-white border-primary position-relative';
            flowCard.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-primary me-2">Step ${index + 1}</span>
                    <strong class="small">${escapeHtml(step.name)}</strong>
                </div>
                <div class="text-muted mt-1" style="font-size:0.75rem;">
                    <i class="fa-solid fa-user-tag me-1"></i> ${step.assignment_type}
                    ${step.due_days ? `<br><i class="fa-solid fa-clock me-1"></i> ${step.due_days} d.` : ''}
                </div>
            `;
            visualFlow.appendChild(flowCard);

            if (index < steps.length - 1) {
                const connector = document.createElement('div');
                connector.className = 'text-center text-primary my-1';
                connector.innerHTML = '<i class="fa-solid fa-arrow-down"></i>';
                visualFlow.appendChild(connector);
            }
        });
    }

    function selectStep(index) {
        selectedStepIndex = index;
        const s = steps[index];

        noStepMsg.classList.add('d-none');
        stepForm.classList.remove('d-none');

        inputName.value = s.name || '';
        selectType.value = s.step_type || 'approval';
        selectAssignType.value = s.assignment_type || 'user';
        selectUser.value = s.assigned_user_id || '';
        selectRole.value = s.assigned_role_id || '';
        inputSigKey.value = s.signature_field_key || '';
        selectMode.value = s.approval_mode || 'sequential';
        inputDue.value = s.due_days || '';
        chkReturn.checked = s.allow_return == 1;
        chkReject.checked = s.allow_reject == 1;

        toggleAssignmentInputs();
        renderList();
    }

    function deselectStep() {
        selectedStepIndex = null;
        noStepMsg.classList.remove('d-none');
        stepForm.classList.add('d-none');
    }

    function toggleAssignmentInputs() {
        const at = selectAssignType.value;
        groupUser.classList.toggle('d-none', at !== 'user');
        groupRole.classList.toggle('d-none', at !== 'role');
        groupSig.classList.toggle('d-none', selectType.value !== 'signature');
    }

    selectAssignType.addEventListener('change', toggleAssignmentInputs);
    selectType.addEventListener('change', toggleAssignmentInputs);

    // Form Live Sync Back to state
    [inputName, selectType, selectAssignType, selectUser, selectRole, inputSigKey, selectMode, inputDue].forEach(el => {
        el.addEventListener('input', updateCurrentStepState);
        el.addEventListener('change', updateCurrentStepState);
    });
    [chkReturn, chkReject].forEach(el => {
        el.addEventListener('change', updateCurrentStepState);
    });

    function updateCurrentStepState() {
        if (selectedStepIndex === null) return;
        steps[selectedStepIndex] = {
            name: inputName.value,
            step_type: selectType.value,
            assignment_type: selectAssignType.value,
            assigned_user_id: selectUser.value ? parseInt(selectUser.value) : null,
            assigned_role_id: selectRole.value ? parseInt(selectRole.value) : null,
            signature_field_key: inputSigKey.value,
            approval_mode: selectMode.value,
            due_days: inputDue.value ? parseInt(inputDue.value) : null,
            allow_return: chkReturn.checked ? 1 : 0,
            allow_reject: chkReject.checked ? 1 : 0
        };
        renderList();
    }

    document.getElementById('add-step-btn').addEventListener('click', function() {
        steps.push({
            name: '<?= __('Step Name') ?> ' + (steps.length + 1),
            step_type: 'approval',
            assignment_type: 'role',
            assigned_user_id: null,
            assigned_role_id: null,
            signature_field_key: '',
            approval_mode: 'sequential',
            due_days: null,
            allow_return: 1,
            allow_reject: 1
        });
        selectStep(steps.length - 1);
    });

    document.getElementById('delete-step-btn').addEventListener('click', function() {
        if (selectedStepIndex !== null) {
            steps.splice(selectedStepIndex, 1);
            deselectStep();
            renderList();
        }
    });

    // Save JSON back to Backend
    document.getElementById('save-workflow-btn').addEventListener('click', function() {
        fetch('/admin/workflows/<?= (int)$wf['id'] ?>/steps', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= \App\Core\Csrf::token() ?>'
            },
            body: JSON.stringify({ steps: steps })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('<?= __('Changes saved successfully.') ?>');
            } else {
                alert('Error: ' + res.message);
            }
        });
    });

    // Initialize SortableJS
    if (typeof Sortable !== 'undefined') {
        new Sortable(stepsContainer, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function(evt) {
                const movedItem = steps.splice(evt.oldIndex, 1)[0];
                steps.splice(evt.newIndex, 0, movedItem);
                selectedStepIndex = evt.newIndex;
                renderList();
            }
        });
    }

    function escapeHtml(str) {
        return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }

    renderList();
});
</script>
