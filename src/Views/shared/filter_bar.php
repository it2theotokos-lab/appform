<?php
/**
 * Shared Standard Filter Bar Component
 * Expected variables:
 * - $actionUrl (string) Form submit GET URL
 * - $filtersConfig (array) Active filters list with configurations
 * - $totalRecords (int|null) Optional total matching records count
 */

$actionUrl = $actionUrl ?? '';
$filtersConfig = $filtersConfig ?? [];
$totalRecords = $totalRecords ?? null;
?>
<div class="card p-3 mb-4 border-0 shadow-sm" style="background: var(--color-surface, #ffffff); border: 1px solid var(--color-border, #e2e8f0);">
    <form method="GET" action="<?= htmlspecialchars($actionUrl) ?>" class="row g-3 align-items-end">
        <?php foreach ($filtersConfig as $filter): ?>
            <?php
            $type = $filter['type'] ?? 'text';
            $name = $filter['name'] ?? '';
            $label = $filter['label'] ?? '';
            $value = $_GET[$name] ?? ($filter['default'] ?? '');
            $options = $filter['options'] ?? [];
            $colClass = $filter['col'] ?? 'col-md-3';
            $placeholder = $filter['placeholder'] ?? 'Αναζήτηση...';
            ?>

            <div class="<?= $colClass ?>">
                <?php if (!empty($label)): ?>
                    <label class="form-label small text-muted font-semibold mb-1"><?= htmlspecialchars($label) ?></label>
                <?php endif; ?>

                <?php if ($type === 'select'): ?>
                    <select name="<?= htmlspecialchars($name) ?>" class="form-select form-select-sm">
                        <?php foreach ($options as $optVal => $optLabel): ?>
                            <option value="<?= htmlspecialchars((string)$optVal) ?>" <?= (string)$value === (string)$optVal ? 'selected' : '' ?>>
                                <?= htmlspecialchars($optLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'date'): ?>
                    <input type="date" name="<?= htmlspecialchars($name) ?>" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$value) ?>">
                <?php else: ?>
                    <input type="text" name="<?= htmlspecialchars($name) ?>" class="form-control form-control-sm" placeholder="<?= htmlspecialchars($placeholder) ?>" value="<?= htmlspecialchars((string)$value) ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="col-md-auto d-flex gap-2 ms-auto">
            <button type="submit" class="btn btn-premium btn-sm"><i class="fa-solid fa-filter me-1" aria-hidden="true"></i> Φίλτρο</button>
            <a href="<?= htmlspecialchars(strtok($actionUrl, '?')) ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-rotate-left me-1" aria-hidden="true"></i> Καθαρισμός</a>
        </div>
    </form>

    <?php if ($totalRecords !== null): ?>
        <div class="mt-2 pt-2 border-top border-secondary-subtle d-flex justify-content-between align-items-center small text-muted">
            <span>Βρέθηκαν <strong><?= (int)$totalRecords ?></strong> εγγραφές</span>
        </div>
    <?php endif; ?>
</div>
