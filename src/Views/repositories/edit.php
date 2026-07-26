<div class="mb-4">
    <a href="/admin/repositories" class="btn btn-outline-secondary btn-sm mb-3"><i class="fa-solid fa-arrow-left"></i> Πίσω</a>
    <h3 class="font-heading text-white">Επεξεργασία Repository: <?= \App\Core\View::escape($repo['name']) ?></h3>
</div>

<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger"><?= \App\Core\View::escape($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-7">
        <div class="glass-panel p-4">
            <h5 class="font-heading mb-4 text-white">Διαμόρφωση Στοιχείων</h5>
            <form action="/admin/repositories/<?= $repo['id'] ?>/edit" method="POST">
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

                <!-- Advanced toggle button -->
                <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="toggleAdvancedMode()">
                    Advanced Mode (JSON Editor)
                </button>

                <div class="mb-4 d-none" id="advancedJsonGroup">
                    <label for="data_json" class="form-label">JSON Επιλογές</label>
                    <textarea class="form-control font-monospace" id="data_json" name="data_json" rows="8"><?= \App\Core\View::escape($repo['data_json']) ?></textarea>
                </div>

                <button type="submit" class="btn btn-premium w-100">Αποθήκευση Repository <i class="fa-solid fa-save ms-2"></i></button>
            </form>
        </div>
    </div>

    <!-- Visual Options Editor Panel -->
    <div class="col-md-5">
        <div class="glass-panel p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="font-heading text-white mb-0">Οπτικός Επεξεργαστής</h5>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-outline-info btn-xs" onclick="exportCsv()"><i class="fa-solid fa-file-export"></i></button>
                    <button type="button" class="btn btn-outline-info btn-xs" onclick="document.getElementById('csvInput').click()"><i class="fa-solid fa-file-import"></i></button>
                    <input type="file" id="csvInput" class="d-none" accept=".csv" onchange="importCsv(event)">
                </div>
            </div>

            <div id="duplicateAlert" class="alert alert-warning d-none py-2 small">Προσοχή: Ανιχνεύθηκαν διπλότυπες τιμές!</div>

            <div id="visualOptionsContainer" class="d-flex flex-column gap-2 mb-3">
                <!-- JS will populate rows dynamically -->
            </div>
            
            <button class="btn btn-outline-primary btn-sm w-100" id="addVisualOptionBtn"><i class="fa-solid fa-plus me-1"></i> Προσθήκη Επιλογής</button>
        </div>
    </div>
</div>

<script>
let optionsData = <?= $repo['data_json'] ?? '[]' ?>;

function toggleAdvancedMode() {
    const jsonGroup = document.getElementById('advancedJsonGroup');
    jsonGroup.classList.toggle('d-none');
}

function renderVisual() {
    const container = document.getElementById('visualOptionsContainer');
    container.innerHTML = '';
    
    // Check duplicates
    const values = optionsData.map(o => o.value);
    const hasDuplicates = values.some((val, i) => values.indexOf(val) !== i);
    const alertBox = document.getElementById('duplicateAlert');
    if (hasDuplicates) {
        alertBox.classList.remove('d-none');
    } else {
        alertBox.classList.add('d-none');
    }

    optionsData.forEach((item, index) => {
        const row = document.createElement('div');
        row.className = 'd-flex gap-2 align-items-center mb-2 bg-dark bg-opacity-25 p-2 rounded border border-glass';
        row.innerHTML = `
            <input type="text" class="form-control form-control-sm opt-val" style="width: 30%;" placeholder="Value" value="${item.value || ''}" onchange="updateItem(${index}, 'value', this.value)">
            <input type="text" class="form-control form-control-sm opt-lbl" style="width: 45%;" placeholder="Label" value="${item.label || ''}" onchange="updateItem(${index}, 'label', this.value)">
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="moveItem(${index}, -1)"><i class="fa-solid fa-arrow-up"></i></button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="moveItem(${index}, 1)"><i class="fa-solid fa-arrow-down"></i></button>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteItem(${index})"><i class="fa-solid fa-trash"></i></button>
            </div>
        `;
        container.appendChild(row);
    });

    // Update the hidden/advanced textarea
    document.getElementById('data_json').value = JSON.stringify(optionsData, null, 2);
}

function updateItem(index, key, val) {
    optionsData[index][key] = val;
    renderVisual();
}

function deleteItem(index) {
    optionsData.splice(index, 1);
    renderVisual();
}

function moveItem(index, direction) {
    const target = index + direction;
    if (target < 0 || target >= optionsData.length) return;
    const temp = optionsData[index];
    optionsData[index] = optionsData[target];
    optionsData[target] = temp;
    renderVisual();
}

function exportCsv() {
    let csv = 'value,label\n';
    optionsData.forEach(item => {
        csv += `"${item.value}","${item.label}"\n`;
    });
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.setAttribute("download", "options_export.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function importCsv(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const text = e.target.result;
        const lines = text.split('\n');
        optionsData = [];
        for (let i = 1; i < lines.length; i++) {
            const line = lines[i].trim();
            if (line) {
                const parts = line.split(',');
                const val = parts[0].replace(/"/g, '');
                const lbl = (parts[1] || parts[0]).replace(/"/g, '');
                optionsData.push({ value: val, label: lbl });
            }
        }
        renderVisual();
    };
    reader.readAsText(file);
}

document.getElementById('addVisualOptionBtn').addEventListener('click', () => {
    optionsData.push({ value: '', label: '' });
    renderVisual();
});

document.addEventListener('DOMContentLoaded', () => {
    renderVisual();
});
</script>
