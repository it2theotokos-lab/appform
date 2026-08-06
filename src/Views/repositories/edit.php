<div class="mb-4">
    <a href="/admin/repositories" class="btn btn-outline-secondary btn-sm mb-3"><i class="fa-solid fa-arrow-left"></i> Πίσω</a>
    <h3 class="font-heading text-white">Επεξεργασία Repository: <?= \App\Core\View::escape($repo['name']) ?></h3>
</div>

<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger"><?= \App\Core\View::escape($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="glass-panel p-4 mb-4">
            <h5 class="font-heading mb-4 text-white">Διαμόρφωση Στοιχείων</h5>
            <form action="/admin/repositories/<?= $repo['id'] ?>/edit" method="POST" id="repoForm">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="_method" value="PUT">

                <div class="mb-3">
                    <label for="name" class="form-label">Όνομα Repository</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= \App\Core\View::escape($repo['name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control" value="<?= \App\Core\View::escape($repo['slug']) ?>" disabled>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Περιγραφή</label>
                    <textarea class="form-control" id="description" name="description" rows="2"><?= \App\Core\View::escape($repo['description'] ?? '') ?></textarea>
                </div>

                <input type="hidden" id="columns_json" name="columns_json" value="<?= \App\Core\View::escape($repo['columns_json'] ?? '[]') ?>">
                <input type="hidden" id="data_json" name="data_json" value="<?= \App\Core\View::escape($repo['data_json'] ?? '[]') ?>">

                <button type="button" class="btn btn-outline-secondary btn-sm mb-3 w-100" onclick="toggleAdvancedMode()">
                    Advanced Mode (JSON Editor)
                </button>

                <div class="mb-4 d-none" id="advancedJsonGroup">
                    <label class="form-label small">Columns JSON</label>
                    <textarea class="form-control font-monospace mb-2" id="adv_columns_json" rows="4"></textarea>
                    <label class="form-label small">Data JSON</label>
                    <textarea class="form-control font-monospace" id="adv_data_json" rows="8"></textarea>
                    <button type="button" class="btn btn-warning btn-sm mt-2 w-100" onclick="applyAdvancedJson()">Εφαρμογή JSON</button>
                </div>

                <button type="button" onclick="submitRepoForm()" class="btn btn-premium w-100">Αποθήκευση Repository <i class="fa-solid fa-save ms-2"></i></button>
            </form>
        </div>
        
        <div class="glass-panel p-4">
            <h5 class="font-heading text-white mb-3">Διαχείριση Στηλών (Columns)</h5>
            <div id="columnsContainer" class="d-flex flex-column gap-2 mb-3">
                <!-- JS populated -->
            </div>
            
            <div class="bg-dark bg-opacity-25 p-3 rounded border border-secondary mt-3">
                <h6 class="text-white small mb-2">Προσθήκη Νέας Στήλης</h6>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <input type="text" id="newColKey" class="form-control form-control-sm" placeholder="Machine Key (π.χ. email)">
                    </div>
                    <div class="col-6">
                        <input type="text" id="newColLabel" class="form-control form-control-sm" placeholder="Label (π.χ. Email Address)">
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-8">
                        <select id="newColType" class="form-select form-select-sm">
                            <option value="text">Text (Κείμενο)</option>
                            <option value="email">Email</option>
                            <option value="phone">Phone (Τηλέφωνο)</option>
                            <option value="number">Number (Αριθμός)</option>
                            <option value="date">Date (Ημερομηνία)</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <button class="btn btn-primary btn-sm w-100" type="button" onclick="addColumn()">Προσθήκη</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Visual Options Editor Panel -->
    <div class="col-md-8">
        <div class="glass-panel p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="font-heading text-white mb-0">Δεδομένα Repository</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary btn-sm" onclick="addRow()"><i class="fa-solid fa-plus me-1"></i> <?= __('+ Νέα Εγγραφή') ?></button>
                    <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalImport"><i class="fa-solid fa-file-import me-1"></i> <?= __('Import') ?></button>
                    
                    <!-- Export Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-file-export me-1"></i> <?= __('Export') ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-content dropdown-item" href="/admin/repositories/<?= $repo['id'] ?>/export?format=csv"><i class="fa-solid fa-file-csv me-2 text-info"></i>CSV</a></li>
                            <li><a class="dropdown-content dropdown-item" href="/admin/repositories/<?= $repo['id'] ?>/export?format=xlsx"><i class="fa-solid fa-file-excel me-2 text-success"></i>Excel (.xlsx)</a></li>
                            <li><a class="dropdown-content dropdown-item" href="/admin/repositories/<?= $repo['id'] ?>/export?format=json"><i class="fa-solid fa-file-code me-2 text-warning"></i>JSON</a></li>
                        </ul>
                    </div>

                    <!-- Download Template Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-download me-1"></i> <?= __('Λήψη Template') ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-content dropdown-item" href="/admin/repositories/<?= $repo['id'] ?>/template?format=csv"><i class="fa-solid fa-file-csv me-2 text-info"></i>CSV Template</a></li>
                            <li><a class="dropdown-content dropdown-item" href="/admin/repositories/<?= $repo['id'] ?>/template?format=xlsx"><i class="fa-solid fa-file-excel me-2 text-success"></i>Excel (.xlsx) Template</a></li>
                            <li><a class="dropdown-content dropdown-item" href="/admin/repositories/<?= $repo['id'] ?>/template?format=json"><i class="fa-solid fa-file-code me-2 text-warning"></i>JSON Template</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="duplicateAlert" class="alert alert-warning d-none py-2 small">Προσοχή: Ανιχνεύθηκαν διπλότυπα Machine Keys στηλών ή εγγραφών!</div>

            <div class="table-responsive">
                <table class="table table-dark table-striped align-middle" id="dataTable">
                    <thead>
                        <tr id="dataTableHeader">
                            <!-- JS Populated -->
                        </tr>
                    </thead>
                    <tbody id="dataTableBody">
                        <!-- JS Populated -->
                    </tbody>
                </table>
            </div>
            
            <button class="btn btn-outline-primary btn-sm mt-2" id="addVisualRowBtn" onclick="addRow()"><i class="fa-solid fa-plus me-1"></i> Προσθήκη Εγγραφής</button>
        </div>
    </div>
</div>

<!-- Modal: Repository Import (Upload -> Preview -> Import) -->
<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content glass-panel">
        <div class="modal-header border-0">
            <h5 class="modal-title font-heading text-white"><i class="fa-solid fa-file-import me-2 text-info"></i><?= __('Import Δεδομένων Repository') ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <form id="importPreviewForm" enctype="multipart/form-data">
            <?= \App\Core\Csrf::field() ?>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-white"><?= __('Επιλογή Αρχείου (CSV, XLSX, JSON)') ?></label>
                        <input type="file" class="form-control" name="import_file" id="import_file" required accept=".csv,.xlsx,.json">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-white"><?= __('Στρατηγική Εισαγωγής') ?></label>
                        <select class="form-select" name="strategy" id="import_strategy">
                            <option value="upsert"><?= __('Upsert (Εισαγωγή Νέων + Ενημέρωση Υπαρχόντων)') ?></option>
                            <option value="insert"><?= __('Insert Only (Εισαγωγή Μόνο Νέων)') ?></option>
                            <option value="update"><?= __('Update Existing (Ενημέρωση Μόνο Υπαρχόντων)') ?></option>
                        </select>
                    </div>
                </div>

                <button type="button" class="btn btn-info btn-sm w-100 mb-3" onclick="runImportPreview()">
                    <i class="fa-solid fa-eye me-1"></i> <?= __('Έλεγχος & Προεπισκόπηση (Preview)') ?>
                </button>

                <!-- Preview Result Container -->
                <div id="importPreviewResults" class="d-none">
                    <hr class="border-secondary my-3">
                    <div id="previewStats" class="d-flex gap-2 flex-wrap mb-3"></div>
                    <div id="previewErrors" class="alert alert-danger d-none py-2 fs-7 max-h-200 overflow-auto"></div>
                    <div id="previewTableContainer" class="table-responsive max-h-300 overflow-auto">
                        <table class="table table-sm table-dark table-striped align-middle" id="previewTable">
                            <thead><tr id="previewTableHeader"></tr></thead>
                            <tbody id="previewTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>

        <form action="/admin/repositories/<?= $repo['id'] ?>/import/process" method="POST" id="importProcessForm">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="temp_token" id="temp_token" value="">
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('Ακύρωση') ?></button>
                <button type="submit" class="btn btn-success" id="btnConfirmImport" disabled>
                    <i class="fa-solid fa-check-circle me-1"></i> <?= __('Επιβεβαίωση & Εφαρμογή Import') ?>
                </button>
            </div>
        </form>
    </div></div>
</div>

<script>
async function runImportPreview() {
    const fileInput = document.getElementById('import_file');
    if (!fileInput.files || fileInput.files.length === 0) {
        alert("Παρακαλώ επιλέξτε ένα αρχείο.");
        return;
    }

    const formData = new FormData(document.getElementById('importPreviewForm'));
    const resultsContainer = document.getElementById('importPreviewResults');
    const statsDiv = document.getElementById('previewStats');
    const errorsDiv = document.getElementById('previewErrors');
    const btnConfirm = document.getElementById('btnConfirmImport');

    statsDiv.innerHTML = '<span class="spinner-border spinner-border-sm text-info"></span> Έλεγχος αρχείου...';
    resultsContainer.classList.remove('d-none');
    errorsDiv.classList.add('d-none');
    btnConfirm.disabled = true;

    try {
        const response = await fetch('/admin/repositories/<?= $repo['id'] ?>/import/preview', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (!data.success) {
            statsDiv.innerHTML = `<span class="badge text-bg-danger">Σφάλμα: ${data.message}</span>`;
            return;
        }

        document.getElementById('temp_token').value = data.temp_token;

        statsDiv.innerHTML = `
            <span class="badge text-bg-secondary">Σύνολο: ${data.total_rows}</span>
            <span class="badge text-bg-success">Έγκυρα: ${data.valid_rows}</span>
            <span class="badge text-bg-primary">Νέα: ${data.insert_rows}</span>
            <span class="badge text-bg-info">Ενημερώσεις: ${data.update_rows}</span>
            ${data.failed_rows > 0 ? `<span class="badge text-bg-danger">Σφάλματα: ${data.failed_rows}</span>` : ''}
        `;

        if (data.errors && data.errors.length > 0) {
            errorsDiv.classList.remove('d-none');
            errorsDiv.innerHTML = '<strong>Σφάλματα Validation:</strong><ul class="mb-0 ps-3">' +
                data.errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
        }

        // Render preview table
        if (data.preview_rows && data.preview_rows.length > 0) {
            const keys = Object.keys(data.preview_rows[0]);
            document.getElementById('previewTableHeader').innerHTML = keys.map(k => `<th>${k}</th>`).join('');
            document.getElementById('previewTableBody').innerHTML = data.preview_rows.map(row => 
                `<tr>${keys.map(k => `<td>${row[k] || ''}</td>`).join('')}</tr>`
            ).join('');
        }

        if (data.valid_rows > 0 && (!data.errors || data.errors.length === 0)) {
            btnConfirm.disabled = false;
        }
    } catch(e) {
        statsDiv.innerHTML = `<span class="badge text-bg-danger">Σφάλμα δικτύου ή απόκρισης: ${e.message}</span>`;
    }
}
</script>

<script>
let columnsData = <?= empty($repo['columns_json']) ? '[]' : $repo['columns_json'] ?>;
let optionsData = <?= empty($repo['data_json']) ? '[]' : $repo['data_json'] ?>;

const defaultColumns = [
    { key: 'value', label: 'Machine Key (Value)', type: 'text', required: true },
    { key: 'label', label: 'Display Label', type: 'text', required: true }
];

function toggleAdvancedMode() {
    const jsonGroup = document.getElementById('advancedJsonGroup');
    jsonGroup.classList.toggle('d-none');
    if (!jsonGroup.classList.contains('d-none')) {
        document.getElementById('adv_columns_json').value = JSON.stringify(columnsData, null, 2);
        document.getElementById('adv_data_json').value = JSON.stringify(optionsData, null, 2);
    }
}

function applyAdvancedJson() {
    try {
        columnsData = JSON.parse(document.getElementById('adv_columns_json').value);
        optionsData = JSON.parse(document.getElementById('adv_data_json').value);
        renderColumns();
        renderData();
        alert("JSON applied!");
    } catch(e) {
        alert("Invalid JSON format: " + e.message);
    }
}

function renderColumns() {
    const container = document.getElementById('columnsContainer');
    container.innerHTML = '';
    
    const allCols = [...defaultColumns, ...columnsData];
    
    allCols.forEach((col, idx) => {
        const isDefault = col.required;
        const row = document.createElement('div');
        row.className = 'd-flex justify-content-between align-items-center bg-dark bg-opacity-25 p-2 rounded border border-glass';
        
        let delBtn = '';
        if (!isDefault) {
            delBtn = `<button class="btn btn-outline-danger btn-sm" onclick="deleteColumn('${col.key}')" title="Διαγραφή Στήλης"><i class="fa-solid fa-trash"></i></button>`;
        }
        
        row.innerHTML = `
            <div>
                <span class="d-block small fw-bold">${col.label} ${isDefault ? '<span class="badge bg-secondary ms-1">Required</span>' : ''}</span>
                <span class="d-block text-muted" style="font-size: 10px;">Key: ${col.key} | Type: ${col.type}</span>
            </div>
            <div>
                ${delBtn}
            </div>
        `;
        container.appendChild(row);
    });
}

function addColumn() {
    const key = document.getElementById('newColKey').value.trim();
    const label = document.getElementById('newColLabel').value.trim();
    const type = document.getElementById('newColType').value;
    
    if (!key || !label) {
        alert("Key και Label είναι υποχρεωτικά.");
        return;
    }
    
    if (!/^[a-zA-Z0-9_]+$/.test(key)) {
        alert("Το Machine Key πρέπει να περιέχει μόνο λατινικούς χαρακτήρες, αριθμούς και underscore (_).");
        return;
    }
    
    if (key === 'value' || key === 'label' || columnsData.find(c => c.key === key)) {
        alert("Το Machine Key υπάρχει ήδη.");
        return;
    }
    
    columnsData.push({ key, label, type });
    
    document.getElementById('newColKey').value = '';
    document.getElementById('newColLabel').value = '';
    
    renderColumns();
    renderData();
}

function deleteColumn(key) {
    // Check if data exists for this column
    const hasData = optionsData.some(row => row[key] !== undefined && row[key] !== '');
    if (hasData) {
        if (!confirm(`ΠΡΟΣΟΧΗ: Υπάρχουν ήδη δεδομένα καταχωρημένα για τη στήλη "${key}". Αν διαγράψετε τη στήλη, τα δεδομένα θα παραμείνουν αλλά η στήλη δεν θα είναι διαθέσιμη στο Form Builder. Είστε σίγουροι;`)) {
            return;
        }
    }
    columnsData = columnsData.filter(c => c.key !== key);
    renderColumns();
    renderData();
}

function renderData() {
    const header = document.getElementById('dataTableHeader');
    const body = document.getElementById('dataTableBody');
    
    header.innerHTML = '';
    body.innerHTML = '';
    
    const allCols = [...defaultColumns, ...columnsData];
    
    // Headers
    allCols.forEach(col => {
        const th = document.createElement('th');
        th.innerText = col.label;
        header.appendChild(th);
    });
    const actionTh = document.createElement('th');
    actionTh.className = "text-end";
    actionTh.innerText = "Ενέργειες";
    header.appendChild(actionTh);
    
    // Rows
    optionsData.forEach((row, rowIndex) => {
        const tr = document.createElement('tr');
        
        allCols.forEach(col => {
            const td = document.createElement('td');
            const val = row[col.key] || '';
            let inputType = 'text';
            if (col.type === 'number') inputType = 'number';
            if (col.type === 'email') inputType = 'email';
            if (col.type === 'date') inputType = 'date';
            
            td.innerHTML = `<input type="${inputType}" class="form-control form-control-sm" value="${val}" onchange="updateData(${rowIndex}, '${col.key}', this.value)">`;
            tr.appendChild(td);
        });
        
        const actionTd = document.createElement('td');
        actionTd.className = "text-end";
        actionTd.innerHTML = `
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="moveRow(${rowIndex}, -1)"><i class="fa-solid fa-arrow-up"></i></button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="moveRow(${rowIndex}, 1)"><i class="fa-solid fa-arrow-down"></i></button>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteRow(${rowIndex})"><i class="fa-solid fa-trash"></i></button>
            </div>
        `;
        tr.appendChild(actionTd);
        
        body.appendChild(tr);
    });
    
    checkDuplicates();
}

function updateData(rowIndex, key, val) {
    if (!optionsData[rowIndex]) optionsData[rowIndex] = {};
    optionsData[rowIndex][key] = val;
    checkDuplicates();
}

function addRow() {
    optionsData.push({ value: '', label: '' });
    renderData();
}

function deleteRow(index) {
    optionsData.splice(index, 1);
    renderData();
}

function moveRow(index, direction) {
    const target = index + direction;
    if (target < 0 || target >= optionsData.length) return;
    const temp = optionsData[index];
    optionsData[index] = optionsData[target];
    optionsData[target] = temp;
    renderData();
}

function checkDuplicates() {
    const values = optionsData.map(o => o.value).filter(v => v !== '');
    const hasDuplicates = values.some((val, i) => values.indexOf(val) !== i);
    const alertBox = document.getElementById('duplicateAlert');
    if (hasDuplicates) {
        alertBox.classList.remove('d-none');
    } else {
        alertBox.classList.add('d-none');
    }
}

function submitRepoForm() {
    document.getElementById('columns_json').value = JSON.stringify(columnsData);
    document.getElementById('data_json').value = JSON.stringify(optionsData);
    document.getElementById('repoForm').submit();
}

document.addEventListener('DOMContentLoaded', () => {
    renderColumns();
    renderData();
});
</script>
