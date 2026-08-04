<div class="row">
    <!-- Filters & Settings Left Card -->
    <div class="col-md-4 mb-4">
        <div class="card p-4">
            <h5 class="card-title mb-3" style="color:var(--color-text);"><i class="fa-solid fa-gears me-2 text-primary" aria-hidden="true"></i> <?= __('Options & Filters') ?></h5>
            <hr class="my-2" style="border-color: var(--color-border);">
            
            <form action="/admin/data-exchange/report" method="POST">
                <?= \App\Core\Csrf::field() ?>
                
                <div class="mb-3">
                    <label for="form_id" class="form-label font-medium text-soft" style="color:var(--color-text-soft);"><?= __('Select Form') ?></label>
                    <select class="form-select" id="form_id" name="form_id" required style="background-color:var(--color-input-bg); color:var(--color-text); border-color:var(--color-border);">
                        <option value="">-- <?= __('Select Form') ?> --</option>
                        <?php foreach ($forms as $f): ?>
                            <option value="<?= $f['id'] ?>" <?= ($selectedForm == $f['id']) ? 'selected' : '' ?>><?= \App\Core\View::escape($f['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="department" class="form-label font-medium text-soft" style="color:var(--color-text-soft);"><?= __('Department Filter (LDAP)') ?></label>
                    <select class="form-select" id="department" name="department" style="background-color:var(--color-input-bg); color:var(--color-text); border-color:var(--color-border);">
                        <option value=""><?= __('All Departments') ?></option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="date_from" class="form-label font-medium text-soft" style="color:var(--color-text-soft);"><?= __('From Date') ?></label>
                    <input type="date" class="form-control" id="date_from" name="date_from" style="background-color:var(--color-input-bg); color:var(--color-text); border-color:var(--color-border);">
                </div>

                <div class="mb-3">
                    <label for="date_to" class="form-label font-medium text-soft" style="color:var(--color-text-soft);"><?= __('To Date') ?></label>
                    <input type="date" class="form-control" id="date_to" name="date_to" style="background-color:var(--color-input-bg); color:var(--color-text); border-color:var(--color-border);">
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="preview" class="btn btn-premium">
                        <i class="fa-solid fa-magnifying-glass-chart me-1" aria-hidden="true"></i> <?= __('View Statistics') ?>
                    </button>
                    <button type="submit" name="download_pdf" class="btn btn-outline-danger">
                        <i class="fa-solid fa-file-pdf me-1" aria-hidden="true"></i> <?= __('Export PDF Report') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Import / Export Tabs Right Card -->
    <div class="col-md-8">
        <div class="card p-4">
            <h5 class="card-title mb-4" style="color:var(--color-text);"><i class="fa-solid fa-arrow-right-arrow-left me-2 text-primary" aria-hidden="true"></i> <?= __('Data Exchange & Imports') ?></h5>
            
            <ul class="nav nav-tabs mb-4" id="dataExchangeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="export-tab" data-bs-toggle="tab" data-bs-target="#export-pane" type="button" role="tab"><?= __('Export Data') ?></button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="import-tab" data-bs-toggle="tab" data-bs-target="#import-pane" type="button" role="tab"><?= __('Import Data') ?></button>
                </li>
            </ul>

            <div class="tab-content" id="dataExchangeTabsContent">
                <!-- Export Tab Panel -->
                <div class="tab-pane fade show active" id="export-pane" role="tabpanel" aria-labelledby="export-tab">
                    <form action="/admin/data-exchange/export" method="POST">
                        <?= \App\Core\Csrf::field() ?>
                        
                        <div class="mb-3">
                            <label for="export_entity" class="form-label font-medium text-soft" style="color:var(--color-text-soft);"><?= __('Module / Table to Export') ?></label>
                            <select class="form-select" id="export_entity" name="entity" required style="background-color:var(--color-input-bg); color:var(--color-text); border-color:var(--color-border);">
                                <?php foreach ($entities as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= ($selectedEntity == $k) ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-medium text-soft" style="color:var(--color-text-soft);"><?= __('File Format') ?></label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format" id="fmt_csv" value="csv" checked>
                                    <label class="form-check-label text-soft" for="fmt_csv" style="color:var(--color-text);">CSV (Unicode BOM)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format" id="fmt_xlsx" value="xlsx">
                                    <label class="form-check-label text-soft" for="fmt_xlsx" style="color:var(--color-text);">Excel (XLSX)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format" id="fmt_json" value="json">
                                    <label class="form-check-label text-soft" for="fmt_json" style="color:var(--color-text);">JSON Schema</label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-premium mt-2">
                            <i class="fa-solid fa-download me-1" aria-hidden="true"></i> <?= __('Execute Export') ?>
                        </button>
                    </form>

                    <h6 class="mt-4 mb-3 font-semibold text-soft" style="color:var(--color-text-soft);"><?= __('Export History') ?></h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>UUID</th>
                                    <th><?= __('Module') ?></th>
                                    <th>Format</th>
                                    <th><?= __('File') ?></th>
                                    <th><?= __('Records') ?></th>
                                    <th><?= __('Date') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($exportJobs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3" style="color:var(--color-text-muted)!important;"><?= __('No exports recorded.') ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($exportJobs as $job): ?>
                                        <tr>
                                            <td><small class="text-muted" style="color:var(--color-text-muted);"><?= htmlspecialchars($job['uuid']) ?></small></td>
                                            <td style="color:var(--color-text);"><?= htmlspecialchars($entities[$job['entity_type']] ?? $job['entity_type']) ?></td>
                                            <td><span class="badge bg-secondary"><?= strtoupper($job['format']) ?></span></td>
                                            <td style="color:var(--color-text);"><?= htmlspecialchars($job['file_name']) ?></td>
                                            <td style="color:var(--color-text);"><?= $job['record_count'] ?></td>
                                            <td style="color:var(--color-text);"><?= date('d/m/Y H:i', strtotime($job['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Import Tab Panel -->
                <div class="tab-pane fade" id="import-pane" role="tabpanel" aria-labelledby="import-tab">
                    
                    <!-- Download Import Template Section -->
                    <div class="p-3 mb-4 rounded border" style="background-color:var(--color-bg); border-color:var(--color-border)!important;">
                        <h6 class="font-bold text-soft mb-2" style="color:var(--color-text-soft);"><i class="fa-solid fa-file-arrow-down me-1 text-primary"></i> <?= __('Download Template File') ?></h6>
                        <p class="text-muted small mb-3" style="color:var(--color-text-muted);">
                            <?= __('Download template, fill in data without changing headers and upload the completed file.') ?>
                        </p>
                        
                        <div class="row align-items-end g-3">
                            <div class="col-md-6">
                                <label for="template_entity" class="form-label font-medium text-soft small" style="color:var(--color-text-soft);"><?= __('Select Module') ?></label>
                                <select class="form-select" id="template_entity" style="background-color:var(--color-input-bg); color:var(--color-text); border-color:var(--color-border);">
                                    <option value="">-- <?= __('Select Module') ?> --</option>
                                    <?php foreach ($entities as $k => $v): ?>
                                        <?php if ($k !== 'audit_logs'): ?>
                                            <option value="<?= $k ?>"><?= htmlspecialchars($v) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex gap-2">
                                <button type="button" id="btn-dl-csv" class="btn btn-sm btn-outline-secondary w-100" disabled>
                                    <i class="fa-solid fa-file-csv me-1"></i> <?= __('Download CSV') ?>
                                </button>
                                <button type="button" id="btn-dl-xlsx" class="btn btn-sm btn-outline-primary w-100" disabled>
                                    <i class="fa-solid fa-file-excel me-1"></i> <?= __('Download Excel') ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <form action="/admin/data-exchange/import" method="POST" enctype="multipart/form-data">
                        <?= \App\Core\Csrf::field() ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="import_entity" class="form-label font-medium text-soft" style="color:var(--color-text-soft);"><?= __('Import Destination') ?></label>
                                <select class="form-select" id="import_entity" name="entity" required style="background-color:var(--color-input-bg); color:var(--color-text); border-color:var(--color-border);">
                                    <?php foreach ($entities as $k => $v): ?>
                                        <?php if ($k !== 'audit_logs'): ?>
                                            <option value="<?= $k ?>"><?= htmlspecialchars($v) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="matching_field" class="form-label font-medium text-soft" style="color:var(--color-text-soft);"><?= __('Matching Field') ?></label>
                                <select class="form-select" id="matching_field" name="matching_field" required style="background-color:var(--color-input-bg); color:var(--color-text); border-color:var(--color-border);">
                                    <option value="id">Primary Key (id)</option>
                                    <option value="uuid">UUID (uuid)</option>
                                    <option value="username">Username (<?= __('for users') ?>)</option>
                                    <option value="email">Email (<?= __('for users') ?>)</option>
                                    <option value="slug">Slug (<?= __('for forms/roles') ?>)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="strategy" class="form-label font-medium text-soft" style="color:var(--color-text-soft);"><?= __('Duplicate Strategy') ?></label>
                                <select class="form-select" id="strategy" name="strategy" required style="background-color:var(--color-input-bg); color:var(--color-text); border-color:var(--color-border);">
                                    <option value="skip"><?= __('Skip') ?></option>
                                    <option value="update"><?= __('Update') ?></option>
                                    <option value="duplicate"><?= __('Duplicate') ?></option>
                                    <option value="stop"><?= __('Stop') ?></option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="import_file" class="form-label font-medium text-soft" style="color:var(--color-text-soft);"><?= __('Select File (CSV, JSON)') ?></label>
                                <input class="form-control" type="file" id="import_file" name="import_file" accept=".csv,.json" required style="background-color:var(--color-input-bg); color:var(--color-text); border-color:var(--color-border);">
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="dry_run" name="dry_run" value="1">
                            <label class="form-check-label text-soft" for="dry_run" style="color:var(--color-text-soft);"><?= __('Dry Run (Simulation without database change)') ?></label>
                        </div>

                        <button type="submit" class="btn btn-premium">
                            <i class="fa-solid fa-upload me-1" aria-hidden="true"></i> <?= __('Execute Import') ?>
                        </button>
                    </form>

                    <h6 class="mt-4 mb-3 font-semibold text-soft" style="color:var(--color-text-soft);"><?= __('Import History') ?></h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><?= __('File') ?></th>
                                    <th><?= __('Module') ?></th>
                                    <th><?= __('Strategy') ?></th>
                                    <th><?= __('Total') ?></th>
                                    <th><?= __('Successful') ?></th>
                                    <th><?= __('Failures') ?></th>
                                    <th>Dry Run</th>
                                    <th><?= __('Date') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($importJobs)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-3" style="color:var(--color-text-muted)!important;"><?= __('No imports recorded.') ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($importJobs as $job): ?>
                                        <tr>
                                            <td style="color:var(--color-text);"><?= htmlspecialchars($job['filename']) ?></td>
                                            <td style="color:var(--color-text);"><?= htmlspecialchars($entities[$job['entity_type']] ?? $job['entity_type']) ?></td>
                                            <td><span class="badge bg-info"><?= strtoupper($job['duplicate_strategy']) ?></span></td>
                                            <td style="color:var(--color-text);"><?= $job['total_rows'] ?></td>
                                            <td><span class="text-success"><?= $job['successful_rows'] ?></span></td>
                                            <td><span class="text-danger"><?= $job['failed_rows'] ?></span></td>
                                            <td style="color:var(--color-text);"><?= $job['is_dry_run'] ? __('Yes') : __('No') ?></td>
                                            <td style="color:var(--color-text);"><?= date('d/m/Y H:i', strtotime($job['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const templateEntitySelect = document.getElementById('template_entity');
    const btnDlCsv = document.getElementById('btn-dl-csv');
    const btnDlXlsx = document.getElementById('btn-dl-xlsx');

    templateEntitySelect.addEventListener('change', function() {
        const val = this.value;
        if (val) {
            btnDlCsv.removeAttribute('disabled');
            btnDlXlsx.removeAttribute('disabled');
        } else {
            btnDlCsv.setAttribute('disabled', 'true');
            btnDlXlsx.setAttribute('disabled', 'true');
        }
    });

    btnDlCsv.addEventListener('click', function() {
        const entity = templateEntitySelect.value;
        if (entity) {
            window.location.href = `/admin/data-exchange/template?entity=${entity}&format=csv`;
        }
    });

    btnDlXlsx.addEventListener('click', function() {
        const entity = templateEntitySelect.value;
        if (entity) {
            window.location.href = `/admin/data-exchange/template?entity=${entity}&format=xlsx`;
        }
    });
});
</script>
