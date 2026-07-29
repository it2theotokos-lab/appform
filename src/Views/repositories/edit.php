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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="font-heading text-white mb-0">Δεδομένα Repository</h5>
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
