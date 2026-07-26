<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="h3 m-0 text-white"><i class="fa-solid fa-plus me-2 text-primary" aria-hidden="true"></i> Δημιουργία Προτύπου</h1>
        <p class="text-muted m-0">Ανεβάστε ένα αρχείο PDF ή DOCX για να ξεκινήσετε τον σχεδιασμό των πεδίων.</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/document-templates" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i> Επιστροφή
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card p-4">
            <?php if ($error = \App\Core\Session::flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>
                    <?= \App\Core\View::escape($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="/admin/document-templates" method="POST" enctype="multipart/form-data">
                <?= \App\Core\Csrf::field() ?>

                <div class="mb-3">
                    <label for="title" class="form-label">Τίτλος Προτύπου</label>
                    <input type="text" class="form-control" id="title" name="title" required placeholder="π.χ. Αίτηση Equipment Εργαζομένου">
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" class="form-control" id="slug" name="slug" required placeholder="π.χ. employee-equipment-declaration">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Περιγραφή</label>
                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Σύντομη περιγραφή του προτύπου εγγράφου..."></textarea>
                </div>

                <div class="mb-4">
                    <label for="document_file" class="form-label">Αρχείο Προτύπου (PDF ή DOCX)</label>
                    <input type="file" class="form-control" id="document_file" name="document_file" required accept=".pdf,.docx">
                    <div class="form-text text-muted">Επιτρέπονται μόνο αρχεία .pdf και .docx έως 10MB.</div>
                </div>

                <button type="submit" class="btn btn-premium w-100 py-2">
                    Δημιουργία & Σχεδιασμός <i class="fa-solid fa-chevron-right ms-1" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>
</div>
