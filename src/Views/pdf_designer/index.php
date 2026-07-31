<?php
// Build design-status map: formId => bool
$designMap = [];
foreach ($forms as $form) {
    $designMap[$form['id']] = (bool)\App\Models\PdfDesign::getByFormId((int)$form['id']);
}
?>
<!-- Scoped PDF Designer Custom Styles for Index Page -->
<style>
.pdf-designer-index-wrapper .designer-title {
    color: var(--color-text, #1E293B) !important;
}
.pdf-designer-index-wrapper .designer-subtitle {
    color: var(--color-text-muted, #64748B) !important;
}
.pdf-designer-index-wrapper .glass-panel {
    background: var(--color-surface, #FFFFFF) !important;
    border: 1px solid var(--color-border, #E2E8F0) !important;
    box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.05)) !important;
}
.pdf-designer-index-wrapper table {
    color: var(--color-text, #1E293B) !important;
}
.pdf-designer-index-wrapper table thead th {
    background-color: var(--table-header-bg, #F1F5F9) !important;
    color: var(--table-header-text, #374151) !important;
    border-bottom: 2px solid var(--color-border, #CBD5E1) !important;
}
.pdf-designer-index-wrapper table tbody tr {
    background-color: var(--table-row-bg, #FFFFFF) !important;
    color: var(--table-text, #111827) !important;
    border-bottom: 1px solid var(--color-border, #E5E7EB) !important;
}
.pdf-designer-index-wrapper table tbody tr:hover {
    background-color: var(--table-row-hover, #EEF2FF) !important;
}
.pdf-designer-index-wrapper table tbody td {
    color: var(--color-text, #1E293B) !important;
}

/* Dark Mode Overrides for Index Page */
[data-theme="dark"] .pdf-designer-index-wrapper .designer-title { color: #F8FAFC !important; }
[data-theme="dark"] .pdf-designer-index-wrapper .designer-subtitle { color: #94A3B8 !important; }
[data-theme="dark"] .pdf-designer-index-wrapper .glass-panel {
    background: #1E293B !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .pdf-designer-index-wrapper table thead th {
    background-color: #0F172A !important;
    color: #CBD5E1 !important;
    border-bottom-color: #334155 !important;
}
[data-theme="dark"] .pdf-designer-index-wrapper table tbody tr {
    background-color: #1E293B !important;
    color: #F8FAFC !important;
    border-bottom-color: #334155 !important;
}
[data-theme="dark"] .pdf-designer-index-wrapper table tbody tr:hover {
    background-color: #334155 !important;
}
[data-theme="dark"] .pdf-designer-index-wrapper table tbody td {
    color: #F8FAFC !important;
}
</style>

<!-- PDF Form Designer - Forms Selection List -->
<div class="content-wrapper pdf-designer-index-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="font-heading designer-title mb-1"><i class="fa-solid fa-file-pdf text-primary me-2"></i> <?= __('PDF Form Designer') ?></h2>
            <p class="designer-subtitle small mb-0"><?= __('Select a form to create or edit its visual A4 PDF layout design.') ?></p>
        </div>
    </div>

    <!-- Hint Banner -->
    <div class="alert alert-info alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="fa-solid fa-circle-info flex-shrink-0"></i>
        <span><?= __('Create first a Form and then select it to design its PDF.') ?></span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="<?= __('Close') ?>"></button>
    </div>

    <div class="glass-panel p-4">
        <?php if (empty($forms)): ?>
            <div class="text-center py-4">
                <i class="fa-solid fa-folder-open fa-2x mb-2 text-secondary"></i>
                <p class="designer-subtitle"><?= __('No forms available. Create a form first.') ?></p>
                <a href="/admin/forms" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> <?= __('Create Form') ?>
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?= __('Form Title') ?></th>
                            <th><?= __('Created Date') ?></th>
                            <th><?= __('Status') ?></th>
                            <th class="text-end"><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($forms as $form): ?>
                            <?php $hasDesign = $designMap[$form['id']] ?? false; ?>
                            <tr>
                                <td><code>#<?= $form['id'] ?></code></td>
                                <td class="fw-bold"><?= htmlspecialchars($form['title']) ?></td>
                                <td><?= htmlspecialchars($form['created_at'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($hasDesign): ?>
                                        <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i><?= __('Has Design') ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="fa-solid fa-xmark me-1"></i><?= __('No Design') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="/admin/pdf-designer/editor/<?= $form['id'] ?>" class="btn btn-sm <?= $hasDesign ? 'btn-primary' : 'btn-success' ?>">
                                        <?php if ($hasDesign): ?>
                                            <i class="fa-solid fa-pen-ruler me-1"></i> <?= __('Design PDF') ?>
                                        <?php else: ?>
                                            <i class="fa-solid fa-plus me-1"></i> <?= __('New PDF Design') ?>
                                        <?php endif; ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
