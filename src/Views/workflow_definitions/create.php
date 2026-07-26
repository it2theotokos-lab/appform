<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h1 class="h3 m-0 text-soft"><i class="fa-solid fa-diagram-project me-2 text-primary"></i> Δημιουργία Workflow</h1>
        <p class="text-muted m-0">Εισάγετε τα βασικά στοιχεία της ροής έγκρισης.</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="/admin/workflows" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Επιστροφή
        </a>
    </div>
</div>

<div class="card p-4" style="max-width: 600px;">
    <form action="/admin/workflows" method="POST">
        <?= \App\Core\Csrf::field() ?>
        
        <div class="mb-3">
            <label for="name" class="form-label text-soft">Όνομα Workflow</label>
            <input type="text" class="form-control" id="name" name="name" required placeholder="π.χ. Έγκριση Υπεύθυνης Δήλωσης">
        </div>

        <div class="mb-3">
            <label for="description" class="form-label text-soft">Περιγραφή</label>
            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Σύντομη περιγραφή του σκοπού της ροής..."></textarea>
        </div>

        <div class="mb-3">
            <label for="entity_type" class="form-label text-soft">Τύπος Οντότητας (Entity Type)</label>
            <select class="form-select" id="entity_type" name="entity_type" onchange="toggleEntitySelects()">
                <option value="document">Document (Έγγραφο)</option>
                <option value="form">Form (Φόρμα Υποβολής)</option>
            </select>
        </div>

        <div class="mb-3" id="doc_template_group">
            <label for="document_template_id" class="form-label text-soft">Ανάθεση σε Πρότυπο Έγγραφο</label>
            <select class="form-select" id="document_template_id" name="document_template_id">
                <option value="">-- Επιλέξτε Πρότυπο --</option>
                <?php foreach ($templates as $tpl): ?>
                    <option value="<?= (int)$tpl['id'] ?>"><?= \App\Core\View::escape($tpl['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3 d-none" id="form_template_group">
            <label for="form_id" class="form-label text-soft">Ανάθεση σε Φόρμα (Form)</label>
            <select class="form-select" id="form_id" name="form_id">
                <option value="">-- Επιλέξτε Φόρμα --</option>
                <?php foreach ($forms as $frm): ?>
                    <option value="<?= (int)$frm['id'] ?>"><?= \App\Core\View::escape($frm['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <script>
        function toggleEntitySelects() {
            const et = document.getElementById('entity_type').value;
            const docGrp = document.getElementById('doc_template_group');
            const frmGrp = document.getElementById('form_template_group');
            if (et === 'form') {
                docGrp.classList.add('d-none');
                frmGrp.classList.remove('d-none');
            } else {
                docGrp.classList.remove('d-none');
                frmGrp.classList.add('d-none');
            }
        }
        </script>

        <button type="submit" class="btn btn-premium w-100 mt-2">
            <i class="fa-solid fa-circle-check me-1"></i> Δημιουργία & Σχεδίαση Βημάτων
        </button>
    </form>
</div>
