<div class="mb-4">
    <?php if (!empty($isAdminPreview)): ?>
        <div class="alert alert-warning border-warning text-dark py-3 px-4 mb-4 rounded d-flex align-items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation fs-4 text-dark"></i>
            <div>
                <strong>Λειτουργία Προεπισκόπησης</strong> – Δεν θα δημιουργηθεί πραγματική υποβολή.
            </div>
        </div>
    <?php elseif (empty($isPublicView)): ?>
        <a href="/dashboard" class="btn btn-outline-secondary btn-sm mb-3"><i class="fa-solid fa-arrow-left"></i> Πίσω στο Portal</a>
    <?php endif; ?>
    <h3 class="font-heading text-white"><?= \App\Core\View::escape($form['title']) ?></h3>
    <?php if ($form['description']): ?>
        <p class="text-muted"><?= \App\Core\View::escape($form['description']) ?></p>
    <?php endif; ?>
</div>

<div class="glass-panel p-4" style="max-width: 800px;">
    <!-- AJAX submit container status boxes -->
    <div id="ajaxSubmitStatusContainer" class="d-none mb-3">
        <div class="alert alert-info d-flex align-items-center py-2" id="ajaxSubmitLoader">
            <i class="fa-solid fa-circle-notch fa-spin me-2"></i>
            <span>Γίνεται υποβολή της φόρμας, παρακαλώ περιμένετε...</span>
        </div>
        <div class="alert alert-success d-none py-2" id="ajaxSubmitSuccess">
            <i class="fa-solid fa-circle-check me-2"></i>
            <span id="ajaxSuccessMsg">Η φόρμα υποβλήθηκε επιτυχώς!</span>
        </div>
        <div class="alert alert-danger d-none py-2" id="ajaxSubmitError">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <span id="ajaxErrorMsg">Παρουσιάστηκε σφάλμα κατά την υποβολή.</span>
        </div>
    </div>

    <form id="ajaxForm" action="/forms/<?= htmlspecialchars($form['slug']) ?>/submit" method="POST" enctype="multipart/form-data">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="form_version_id" value="<?= $versionId ?>">
        <input type="hidden" name="submission_uuid" value="<?= htmlspecialchars($submissionUuid ?? '') ?>">

        <?php foreach ($schema['sections'] as $sec): ?>
            <div class="mb-5 border-bottom border-glass pb-4">
                <h5 class="font-heading text-white mb-2"><?= \App\Core\View::escape($sec['title']) ?></h5>
                <?php if (!empty($sec['description'])): ?>
                    <p class="text-muted small mb-4"><?= \App\Core\View::escape($sec['description']) ?></p>
                <?php endif; ?>

                <div class="row g-3">
                    <?php if (isset($sec['fields'])): ?>
                        <?php foreach ($sec['fields'] as $f): ?>
                            <?php 
                            $widthClass = 'col-12';
                            if (($f['width'] ?? 12) === 6) $widthClass = 'col-md-6';
                            elseif (($f['width'] ?? 12) === 4) $widthClass = 'col-md-4';
                            
                            $oldVal = $answers[$f['key']] ?? $_POST[$f['key']] ?? '';
                            
                            $wrapperStyle = '';
                            if (!empty($f['system_prefill_enabled']) && !empty($f['system_prefill_hidden'])) {
                                $wrapperStyle = 'style="display:none;"';
                            }
                            ?>
                            <div class="<?= $widthClass ?> mb-3 form-field-wrapper" id="wrapper_<?= $f['key'] ?>" data-field-key="<?= $f['key'] ?>" data-conditional-logic='<?= json_encode($f['conditional_logic'] ?? null) ?>' <?= $wrapperStyle ?>>
                                <?php if (empty($f['system_prefill_hidden'])): ?>
                                    <label for="<?= $f['key'] ?>" class="form-label">
                                        <?= \App\Core\View::escape($f['label']) ?>
                                        <?php if ($f['required'] ?? false): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>
                                <?php endif; ?>

                                <?php 
                                $readonlyAttr = '';
                                if (!empty($f['system_prefill_enabled']) && !empty($f['system_prefill_readonly'])) {
                                    $readonlyAttr = 'readonly';
                                }
                                ?>

                                <?php switch ($f['type']):
                                    case 'text':
                                    case 'email':
                                    case 'number':
                                    case 'date': ?>
                                        <input type="<?= $f['type'] ?>" class="form-control" id="<?= $f['key'] ?>" name="<?= $f['key'] ?>"
                                               placeholder="<?= \App\Core\View::escape($f['placeholder'] ?? '') ?>"
                                               value="<?= \App\Core\View::escape($oldVal) ?>"
                                               <?= ($f['required'] ?? false) ? 'required' : '' ?>
                                               <?= $readonlyAttr ?>>
                                        <?php break; ?>

                                    <?php case 'textarea': ?>
                                        <textarea class="form-control" id="<?= $f['key'] ?>" name="<?= $f['key'] ?>" rows="3"
                                                  placeholder="<?= \App\Core\View::escape($f['placeholder'] ?? '') ?>"
                                                  <?= ($f['required'] ?? false) ? 'required' : '' ?>><?= \App\Core\View::escape($oldVal) ?></textarea>
                                        <?php break; ?>

                                    <?php case 'select': ?>
                                        <select class="form-select" id="<?= $f['key'] ?>" name="<?= $f['key'] ?>" <?= ($f['required'] ?? false) ? 'required' : '' ?> <?= $readonlyAttr ?>>
                                            <option value=""><?= \App\Core\View::escape($f['placeholder'] ?? 'Επιλέξτε...') ?></option>
                                            <?php 
                                            $options = [];
                                            if (isset($f['dataSource']) && $f['dataSource'] === 'repository' && !empty($f['repositoryId'])) {
                                                $options = $repositories[$f['repositoryId']] ?? [];
                                            } else {
                                                $options = $f['options'] ?? [];
                                            }
                                            ?>
                                            <?php foreach ($options as $opt): ?>
                                                <option value="<?= \App\Core\View::escape($opt['value']) ?>" <?= $oldVal == $opt['value'] ? 'selected' : '' ?>><?= \App\Core\View::escape($opt['label']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php break; ?>

                                    <?php case 'radio': ?>
                                        <div class="d-flex flex-wrap gap-3 mt-2">
                                            <?php 
                                            $options = $f['options'] ?? [];
                                            if (isset($f['dataSource']) && $f['dataSource'] === 'repository' && !empty($f['repositoryId'])) {
                                                $options = $repositories[$f['repositoryId']] ?? [];
                                            }
                                            ?>
                                            <?php foreach ($options as $idx => $opt): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="<?= $f['key'] ?>" id="<?= $f['key'] ?>_<?= $idx ?>" value="<?= \App\Core\View::escape($opt['value']) ?>"
                                                        <?= $oldVal == $opt['value'] ? 'checked' : '' ?>
                                                        <?= (($f['required'] ?? false) && $idx === 0) ? 'required' : '' ?>
                                                        <?= $readonlyAttr ? 'disabled' : '' ?>>
                                                    <label class="form-check-label text-muted" for="<?= $f['key'] ?>_<?= $idx ?>">
                                                        <?= \App\Core\View::escape($opt['label']) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if ($readonlyAttr): ?>
                                                <!-- Send hidden variable value since disabled inputs are not sent via POST -->
                                                <input type="hidden" name="<?= $f['key'] ?>" value="<?= \App\Core\View::escape($oldVal) ?>">
                                            <?php endif; ?>
                                        </div>
                                        <?php break; ?>

                                    <?php case 'checkbox': ?>
                                        <div class="d-flex flex-column gap-2 mt-2">
                                            <?php 
                                            $hasOptions = false;
                                            $options = [];
                                            if (isset($f['dataSource']) && $f['dataSource'] === 'repository' && !empty($f['repositoryId'])) {
                                                $options = $repositories[$f['repositoryId']] ?? [];
                                                $hasOptions = true;
                                            } elseif (!empty($f['options'])) {
                                                $options = $f['options'];
                                                $hasOptions = true;
                                            }

                                            if ($hasOptions):
                                                $checkedVals = [];
                                                 if ($oldVal !== null && $oldVal !== '') {
                                                     if (is_array($oldVal)) {
                                                         $checkedVals = $oldVal;
                                                     } else {
                                                         $decoded = json_decode($oldVal, true);
                                                         $checkedVals = is_array($decoded) ? $decoded : [$oldVal];
                                                     }
                                                 } else {
                                                    if (!empty($f['default_values']) && is_array($f['default_values'])) {
                                                        $checkedVals = $f['default_values'];
                                                    } else {
                                                        foreach ($options as $opt) {
                                                            if (!empty($opt['default_checked'])) {
                                                                $checkedVals[] = $opt['value'];
                                                            }
                                                        }
                                                    }
                                                }
                                                ?>
                                                <?php foreach ($options as $idx => $opt): ?>
                                                    <?php 
                                                    $isOptChecked = in_array((string)$opt['value'], array_map('strval', $checkedVals));
                                                    $optId = $f['key'] . '_opt_' . $idx;
                                                    ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="<?= $f['key'] ?>[]" id="<?= $optId ?>" value="<?= \App\Core\View::escape($opt['value']) ?>"
                                                            <?= $isOptChecked ? 'checked' : '' ?>
                                                            <?= $readonlyAttr ? 'disabled' : '' ?>>
                                                        <label class="form-check-label text-muted" for="<?= $optId ?>">
                                                            <?= \App\Core\View::escape($opt['label']) ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if ($readonlyAttr): ?>
                                                    <?php foreach ($checkedVals as $cv): ?>
                                                        <input type="hidden" name="<?= $f['key'] ?>[]" value="<?= \App\Core\View::escape($cv) ?>">
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="<?= $f['key'] ?>" id="<?= $f['key'] ?>" value="1"
                                                        <?= $oldVal ? 'checked' : '' ?>
                                                        <?= ($f['required'] ?? false) ? 'required' : '' ?>
                                                        <?= $readonlyAttr ? 'disabled' : '' ?>>
                                                    <label class="form-check-label text-muted" for="<?= $f['key'] ?>">
                                                        <?= \App\Core\View::escape($f['placeholder'] ?? 'Αποδέχομαι') ?>
                                                    </label>
                                                </div>
                                                <?php if ($readonlyAttr): ?>
                                                    <input type="hidden" name="<?= $f['key'] ?>" value="<?= \App\Core\View::escape($oldVal) ?>">
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php break; ?>

                                     <?php case 'calculated': ?>
                                         <div class="input-group">
                                             <?php if (!empty($f['prefix'])): ?>
                                                 <span class="input-group-text"><?= \App\Core\View::escape($f['prefix']) ?></span>
                                             <?php endif; ?>
                                             <input type="text" class="form-control calculated-field-input" 
                                                    id="<?= $f['key'] ?>" 
                                                    name="<?= $f['key'] ?>"
                                                    data-formula="<?= \App\Core\View::escape($f['formula'] ?? '') ?>"
                                                    data-decimals="<?= \App\Core\View::escape($f['decimalPlaces'] ?? 2) ?>"
                                                    value="<?= \App\Core\View::escape($oldVal) ?>"
                                                    readonly
                                                    <?= ($f['hidden'] ?? false) ? 'style="display:none;"' : '' ?>>
                                             <?php if (!empty($f['suffix'])): ?>
                                                 <span class="input-group-text"><?= \App\Core\View::escape($f['suffix']) ?></span>
                                             <?php endif; ?>
                                         </div>
                                         <?php break; ?>

                                     <?php case 'phone': ?>
                                     <?php case 'url': ?>
                                     <?php case 'time': ?>
                                     <?php case 'password': ?>
                                         <input type="<?= $f['type'] === 'phone' ? 'tel' : ($f['type'] === 'url' ? 'url' : ($f['type'] === 'time' ? 'time' : 'password')) ?>" 
                                                class="form-control" id="<?= $f['key'] ?>" name="<?= $f['key'] ?>"
                                                placeholder="<?= \App\Core\View::escape($f['placeholder'] ?? '') ?>"
                                                value="<?= \App\Core\View::escape($oldVal) ?>"
                                                <?= ($f['required'] ?? false) ? 'required' : '' ?>>
                                         <?php break; ?>

                                     <?php case 'hidden': ?>
                                         <input type="hidden" id="<?= $f['key'] ?>" name="<?= $f['key'] ?>" value="<?= \App\Core\View::escape($oldVal ?: ($f['defaultValue'] ?? '')) ?>">
                                         <?php break; ?>

                                     <?php case 'rich_text': ?>
                                         <textarea class="form-control rich-text-editor" id="<?= $f['key'] ?>" name="<?= $f['key'] ?>" rows="4"
                                                   placeholder="<?= \App\Core\View::escape($f['placeholder'] ?? '') ?>"
                                                   <?= ($f['required'] ?? false) ? 'required' : '' ?>><?= \App\Core\View::escape($oldVal) ?></textarea>
                                         <?php break; ?>

                                     <?php case 'html': ?>
                                         <div class="html-content-block <?= \App\Core\View::escape($f['cssClass'] ?? '') ?>">
                                             <?= $f['htmlContent'] ?? '' ?>
                                         </div>
                                         <?php break; ?>

                                     <?php case 'rating': ?>
                                         <div class="rating-input-container d-flex gap-2" id="rating_container_<?= $f['key'] ?>">
                                             <?php $max = $f['ratingMax'] ?? 5; ?>
                                             <?php for ($i = 1; $i <= $max; $i++): ?>
                                                 <span class="rating-star-icon fs-4 cursor-pointer text-muted" data-val="<?= $i ?>" style="cursor:pointer;">★</span>
                                             <?php endfor; ?>
                                             <input type="hidden" id="<?= $f['key'] ?>" name="<?= $f['key'] ?>" value="<?= \App\Core\View::escape($oldVal) ?>" <?= ($f['required'] ?? false) ? 'required' : '' ?>>
                                         </div>
                                         <?php break; ?>

                                     <?php case 'range': ?>
                                         <div class="range-slider-container">
                                             <input type="range" class="form-range" id="<?= $f['key'] ?>" name="<?= $f['key'] ?>"
                                                    min="<?= $f['rangeMin'] ?? 0 ?>" max="<?= $f['rangeMax'] ?? 100 ?>" step="<?= $f['rangeStep'] ?? 5 ?>"
                                                    value="<?= \App\Core\View::escape($oldVal ?: ($f['rangeMin'] ?? 0)) ?>">
                                             <span class="small text-muted" id="range_val_<?= $f['key'] ?>"><?= $oldVal ?: ($f['rangeMin'] ?? 0) ?></span>
                                         </div>
                                         <?php break; ?>

                                     <?php case 'consent': ?>
                                         <div class="form-check mt-2">
                                             <input class="form-check-input" type="checkbox" name="<?= $f['key'] ?>" id="<?= $f['key'] ?>" value="1"
                                                 <?= $oldVal ? 'checked' : '' ?>
                                                 <?= ($f['required'] ?? false) ? 'required' : '' ?>>
                                             <label class="form-check-label text-muted" for="<?= $f['key'] ?>">
                                                 <?= $f['consentText'] ?? 'Αποδέχομαι τους όρους χρήσης' ?>
                                             </label>
                                         </div>
                                         <?php break; ?>

                                     <?php case 'address': ?>
                                         <div class="address-composite-container border border-glass rounded p-2">
                                             <input type="text" class="form-control mb-1 form-control-sm" name="<?= $f['key'] ?>[line1]" placeholder="Διεύθυνση 1" required>
                                             <input type="text" class="form-control mb-1 form-control-sm" name="<?= $f['key'] ?>[line2]" placeholder="Διεύθυνση 2">
                                             <div class="row g-1">
                                                 <div class="col-6"><input type="text" class="form-control form-control-sm" name="<?= $f['key'] ?>[city]" placeholder="Πόλη" required></div>
                                                 <div class="col-6"><input type="text" class="form-control form-control-sm" name="<?= $f['key'] ?>[postal_code]" placeholder="Τ.Κ." required></div>
                                             </div>
                                         </div>
                                         <?php break; ?>

                                     <?php case 'currency': ?>
                                         <div class="input-group">
                                             <span class="input-group-text"><?= $f['currencyCode'] === 'USD' ? '$' : ($f['currencyCode'] === 'GBP' ? '£' : '€') ?></span>
                                             <input type="number" step="0.01" class="form-control currency-field-input" id="<?= $f['key'] ?>" name="<?= $f['key'] ?>"
                                                    placeholder="0.00" value="<?= \App\Core\View::escape($oldVal) ?>"
                                                    <?= ($f['required'] ?? false) ? 'required' : '' ?>>
                                         </div>
                                         <?php break; ?>

                                     <?php case 'repeater': ?>
                                         <div class="repeater-field-container border border-glass rounded p-2" id="repeater_<?= $f['key'] ?>">
                                             <div class="repeater-rows-list">
                                                 <div class="repeater-row border-bottom border-glass pb-2 mb-2 d-flex gap-2 align-items-center">
                                                     <input type="text" class="form-control form-control-sm" name="<?= $f['key'] ?>[][item]" placeholder="Καταχώρηση">
                                                     <button type="button" class="btn btn-outline-danger btn-xs remove-repeater-row"><i class="fa-solid fa-trash"></i></button>
                                                 </div>
                                             </div>
                                             <button type="button" class="btn btn-outline-success btn-xs add-repeater-row"><i class="fa-solid fa-plus me-1"></i>Προσθήκη Σειράς</button>
                                         </div>
                                         <?php break; ?>

                                     <?php case 'signature': ?>
                                         <div class="signature-field-container">
                                             <canvas class="signature-canvas border border-glass rounded bg-dark bg-opacity-50" style="width:100%; height:150px;"></canvas>
                                             <div class="mt-1 d-flex gap-1">
                                                 <button type="button" class="btn btn-outline-secondary btn-xs clear-signature-btn">Clear</button>
                                                 <button type="button" class="btn btn-outline-secondary btn-xs undo-signature-btn">Undo</button>
                                             </div>
                                             <input type="hidden" id="<?= $f['key'] ?>" name="<?= $f['key'] ?>">
                                         </div>
                                         <?php break; ?>

                                     <?php case 'likert': ?>
                                         <div class="likert-table-container table-responsive">
                                             <table class="table table-bordered table-sm">
                                                 <thead>
                                                     <tr class="text-white-50">
                                                         <th>Κριτήριο</th>
                                                         <th>Διαφωνώ</th>
                                                         <th>Ουδέτερο</th>
                                                         <th>Συμφωνώ</th>
                                                     </tr>
                                                 </thead>
                                                 <tbody>
                                                     <tr>
                                                         <td class="text-white">Ποιότητα Υπηρεσίας</td>
                                                         <td><input type="radio" name="<?= $f['key'] ?>[quality]" value="disagree"></td>
                                                         <td><input type="radio" name="<?= $f['key'] ?>[quality]" value="neutral" checked></td>
                                                         <td><input type="radio" name="<?= $f['key'] ?>[quality]" value="agree"></td>
                                                     </tr>
                                                 </tbody>
                                             </table>
                                         </div>
                                         <?php break; ?>

                                     <?php case 'nps': ?>
                                         <div class="nps-input-container">
                                             <div class="d-flex justify-content-between gap-1">
                                                 <?php for ($i = 0; $i <= 10; $i++): ?>
                                                     <button type="button" class="btn btn-outline-primary btn-sm nps-btn" data-val="<?= $i ?>"><?= $i ?></button>
                                                 <?php endfor; ?>
                                             </div>
                                             <input type="hidden" id="<?= $f['key'] ?>" name="<?= $f['key'] ?>" value="8">
                                         </div>
                                         <?php break; ?>

                                     <?php case 'image_choice': ?>
                                         <div class="row g-2 image-choice-container">
                                             <?php foreach ($f['imageChoices'] ?? [] as $choice): ?>
                                                 <div class="col-md-4">
                                                     <label class="card bg-dark bg-opacity-50 border border-glass p-2 text-center cursor-pointer">
                                                         <input type="radio" name="<?= $f['key'] ?>" value="<?= \App\Core\View::escape($choice['value']) ?>" class="d-none">
                                                         <img src="<?= \App\Core\View::escape($choice['url']) ?>" class="img-fluid rounded mb-2" style="max-height:80px; object-fit:cover;">
                                                         <span class="small text-white d-block"><?= \App\Core\View::escape($choice['label']) ?></span>
                                                     </label>
                                                 </div>
                                             <?php endforeach; ?>
                                         </div>
                                         <?php break; ?>

                                     <?php case 'dynamic_select': ?>
                                         <select class="form-select dynamic-select-field" id="<?= $f['key'] ?>" name="<?= $f['key'] ?>" data-source-id="<?= $f['dynamicSourceId'] ?? '' ?>">
                                             <option value="">Επιλέξτε επιλογή...</option>
                                         </select>
                                         <?php break; ?>

                                     <?php case 'page_break': ?>
                                         <?php break; ?>

                                    <?php case 'file': ?>
                                        <input type="file" class="form-control" id="<?= $f['key'] ?>" name="<?= $f['key'] ?>"
                                               <?= ($f['required'] ?? false) ? 'required' : '' ?>>
                                        <small class="text-muted d-block mt-1">Επιτρεπόμενα αρχεία: <?= implode(', ', $f['acceptedTypes'] ?? ['pdf', 'jpg', 'png']) ?> (Μέγιστο: <?= $f['maxSize'] ?? 5 ?>MB)</small>
                                        <?php break; ?>

                                    <?php case 'heading': ?>
                                        <h5 class="text-white mt-3"><?= \App\Core\View::escape($f['label']) ?></h5>
                                        <?php break; ?>

                                    <?php case 'divider': ?>
                                        <hr class="border-glass my-3">
                                        <?php break; ?>
                                <?php endswitch; ?>

                                <?php if ($f['helpText'] ?? null): ?>
                                    <small class="text-muted d-block mt-1"><?= \App\Core\View::escape($f['helpText']) ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Terms of Use Acceptance Checkbox Section -->
        <?php if (!empty($form['require_terms_acceptance'])): ?>
            <div class="mb-4 form-check bg-dark bg-opacity-25 p-3 rounded border border-glass">
                <input type="checkbox" class="form-check-input" id="terms_acceptance" name="terms_acceptance" value="1" required style="margin-left: 0.5em; margin-right: 0.5em;">
                <label class="form-check-label text-muted ms-2" for="terms_acceptance" style="user-select: none;">
                    <?= \App\Core\View::escape($form['terms_checkbox_label'] ?: 'Έχω διαβάσει και αποδέχομαι τους όρους χρήσης της φόρμας.') ?>
                    <a href="#" class="text-info text-decoration-underline ms-1" id="openTermsLink"><?= \App\Core\View::escape($form['terms_link_label'] ?: 'Δείτε τους όρους χρήσης') ?></a>
                </label>
            </div>

            <!-- Terms of Use Modal -->
            <div id="termsModal" class="d-none" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1050; display: flex; align-items: center; justify-content: center;">
                <div class="glass-panel p-4" style="max-width: 600px; width: 90%; max-height: 80%; overflow-y: auto; position: relative;">
                    <h4 class="font-heading text-white mb-3">Όροι Χρήσης</h4>
                    <hr class="border-glass mb-3">
                    <div class="text-white-50 mb-4" style="white-space: pre-wrap; line-height: 1.6; font-size: 0.95rem;"><?= \App\Core\View::escape($form['terms_content'] ?? '') ?></div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-outline-secondary" id="closeTermsBtn">Κλείσιμο</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-3 justify-content-end">
            <?php if ($form['allow_drafts'] && empty($isPublicView) && empty($isAdminPreview)): ?>
                <button type="submit" name="status" value="draft" class="btn btn-outline-secondary">Αποθήκευση Προσχεδίου <i class="fa-solid fa-floppy-disk ms-1"></i></button>
            <?php endif; ?>
            <?php if (!empty($isAdminPreview)): ?>
                <button type="button" class="btn btn-premium" onclick="alert('Προσοχή: Βρίσκεστε σε Λειτουργία Προεπισκόπησης. Δεν επιτρέπονται υποβολές.')">Υποβολή Φόρμας <i class="fa-solid fa-paper-plane ms-1"></i></button>
            <?php else: ?>
                <button type="submit" name="status" value="submitted" class="btn btn-premium">Υποβολή Φόρμας <i class="fa-solid fa-paper-plane ms-1"></i></button>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    const calcInputs = document.querySelectorAll('.calculated-field-input');
    const schema = <?= json_encode($schema) ?>;

    // Gather field configurations
    const fieldsMap = {};
    if (schema.sections) {
        schema.sections.forEach(sec => {
            if (sec.fields) {
                sec.fields.forEach(f => {
                    fieldsMap[f.key] = f;
                });
            }
        });
    }

    function getFieldValue(key) {
        const fieldDef = fieldsMap[key];
        if (!fieldDef) return 0;

        const input = document.getElementById(key);
        if (!input) {
            // Check for radio groups
            const radio = document.querySelector(`input[name="${key}"]:checked`);
            if (radio) {
                return getNumericChoiceValue(fieldDef, radio.value);
            }
            return 0;
        }

        if (input.type === 'checkbox') {
            if (!input.checked) return 0;
            return getNumericChoiceValue(fieldDef, input.value || '1');
        }

        if (input.tagName === 'SELECT') {
            return getNumericChoiceValue(fieldDef, input.value);
        }

        const parsed = parseFloat(input.value);
        return isNaN(parsed) ? 0 : parsed;
    }

    function getNumericChoiceValue(fieldDef, choiceVal) {
        if (!choiceVal) return 0;
        // Standard choices calculation value checks
        if (fieldDef.options) {
            const opt = fieldDef.options.find(o => String(o.value) === String(choiceVal));
            if (opt && opt.calcValue !== undefined && opt.calcValue !== null && opt.calcValue !== '') {
                return parseFloat(opt.calcValue);
            }
        }
        // Fallback to option value itself if numeric
        const parsed = parseFloat(choiceVal);
        return isNaN(parsed) ? 0 : parsed;
    }

    function evaluateFormula(formula) {
        // Resolve field placeholders {key}
        let resolved = formula.replace(/\{([a-zA-Z0-9_]+)\}/g, (match, key) => {
            return getFieldValue(key);
        });

        // Normalize decimals
        resolved = resolved.replace(/,/g, '.');

        // Safe evaluation helper functions: round, abs, min, max, sum, floor, ceil
        resolved = resolved.replace(/(round|abs|min|max|sum|floor|ceil)\(([^)]+)\)/gi, (match, func, argsStr) => {
            const args = argsStr.split(',').map(arg => {
                try {
                    return evalSimpleMath(arg);
                } catch(e) {
                    return 0;
                }
            });
            const f = func.toLowerCase();
            if (f === 'abs') return Math.abs(args[0]);
            if (f === 'ceil') return Math.ceil(args[0]);
            if (f === 'floor') return Math.floor(args[0]);
            if (f === 'round') return roundTo(args[0], args[1] || 0);
            if (f === 'min') return Math.min(...args);
            if (f === 'max') return Math.max(...args);
            if (f === 'sum') return args.reduce((a, b) => a + b, 0);
            return 0;
        });

        try {
            return evalSimpleMath(resolved);
        } catch (e) {
            return 0;
        }
    }

    function roundTo(num, decimals) {
        const factor = Math.pow(10, decimals);
        return Math.round(num * factor) / factor;
    }

    function evalSimpleMath(expr) {
        // Clean and validate arithmetic string
        const clean = expr.replace(/[^-()\d/*+%.]/g, '');
        if (clean.includes('/0')) {
            return 0; // Guard division by zero
        }
        // Safe evaluation of pure arithmetic expression using Function constructor instead of eval
        return new Function(`return (${clean})`)() || 0;
    }

    const wrappers = document.querySelectorAll('.form-field-wrapper');

    function evaluateRule(rule) {
        const sourceKey = rule.field;
        const operator = rule.operator;
        const compVal = rule.value;

        const val = getFieldValue(sourceKey);
        
        // Handle checklist rules
        const sourceDef = fieldsMap[sourceKey];
        const isCheckbox = sourceDef && sourceDef.type === 'checkbox';

        switch (operator) {
            case 'is_empty':
                return (val === null || String(val).trim() === '');
            case 'is_not_empty':
                return (val !== null && String(val).trim() !== '');
            case 'is_checked':
                return isCheckbox && (val == 1 || val === true || val === '1');
            case 'is_not_checked':
                return isCheckbox && (val === null || val == 0 || val === false || val === '0');
        }

        const valStr = String(val).toLowerCase();
        const compStr = String(compVal).toLowerCase();

        // Numeric checks
        const isNumeric = !isNaN(parseFloat(valStr)) && !isNaN(parseFloat(compStr));
        if (isNumeric) {
            const numVal = parseFloat(valStr);
            const numComp = parseFloat(compStr);
            switch (operator) {
                case 'equals': return numVal === numComp;
                case 'not_equals': return numVal !== numComp;
                case 'greater_than': return numVal > numComp;
                case 'greater_than_or_equal': return numVal >= numComp;
                case 'less_than': return numVal < numComp;
                case 'less_than_or_equal': return numVal <= numComp;
            }
        }

        switch (operator) {
            case 'equals': return valStr === compStr;
            case 'not_equals': return valStr !== compStr;
            case 'contains': return valStr.includes(compStr);
            case 'not_contains': return !valStr.includes(compStr);
            case 'starts_with': return valStr.startsWith(compStr);
            case 'ends_with': return valStr.endsWith(compStr);
        }
        return false;
    }

    function evaluateFieldVisibility(wrapper) {
        const logicRaw = wrapper.dataset.conditionalLogic;
        if (!logicRaw) return;
        try {
            const logic = JSON.parse(logicRaw);
            if (!logic || !logic.enabled) return;

            const action = logic.action || 'show';
            const match = logic.match || 'all';
            const rules = logic.rules || [];

            if (rules.length === 0) return;

            const results = rules.map(evaluateRule);
            let matched = false;
            if (match === 'all') {
                matched = !results.includes(false);
            } else {
                matched = results.includes(true);
            }

            const visible = (action === 'show') ? matched : !matched;
            const input = wrapper.querySelector('input, textarea, select');
            
            if (visible) {
                wrapper.style.display = '';
                wrapper.removeAttribute('aria-hidden');
                if (input) {
                    input.removeAttribute('disabled');
                    // Restore required attribute if field has required configuration
                    const key = wrapper.dataset.fieldKey;
                    const fieldDef = fieldsMap[key];
                    if (fieldDef && fieldDef.required) {
                        input.setAttribute('required', 'required');
                    }
                }
            } else {
                wrapper.style.display = 'none';
                wrapper.setAttribute('aria-hidden', 'true');
                if (input) {
                    input.setAttribute('disabled', 'disabled');
                    input.removeAttribute('required');
                }
            }
        } catch(e) {}
    }

    function recalculateAll() {
        // Run multiple times (e.g. 5 passes) to resolve cascade calculations and conditional dependencies
        for (let pass = 0; pass < 5; pass++) {
            let changed = false;
            
            // Recalculate field formulas
            calcInputs.forEach(input => {
                const formula = input.dataset.formula;
                const decimals = parseInt(input.dataset.decimals || '2');
                if (!formula) return;

                const result = evaluateFormula(formula);
                const roundedResult = roundTo(result, decimals).toFixed(decimals);
                if (input.value !== roundedResult) {
                    input.value = roundedResult;
                    changed = true;
                }
            });

            // Re-evaluate visibility
            wrappers.forEach(evaluateFieldVisibility);
            
            if (!changed) break;
        }
    }

    // Bind interactive triggers for Rating stars
    document.querySelectorAll('.rating-input-container').forEach(container => {
        const stars = container.querySelectorAll('.rating-star-icon');
        const hiddenInput = container.querySelector('input[type="hidden"]');
        
        stars.forEach(star => {
            star.addEventListener('click', () => {
                const val = star.dataset.val;
                hiddenInput.value = val;
                
                // Highlight active stars
                stars.forEach(s => {
                    if (parseInt(s.dataset.val) <= parseInt(val)) {
                        s.classList.remove('text-muted');
                        s.classList.add('text-warning');
                    } else {
                        s.classList.remove('text-warning');
                        s.classList.add('text-muted');
                    }
                });
                recalculateAll();
            });
        });
    });

    // Bind interactive triggers for Range Sliders
    document.querySelectorAll('.range-slider-container').forEach(container => {
        const rangeInput = container.querySelector('input[type="range"]');
        const valSpan = container.querySelector('.small.text-muted');
        if (rangeInput && valSpan) {
            rangeInput.addEventListener('input', () => {
                valSpan.textContent = rangeInput.value;
                recalculateAll();
            });
        }
    });

    // Bind interactive triggers for NPS buttons
    document.querySelectorAll('.nps-input-container').forEach(container => {
        const buttons = container.querySelectorAll('.nps-btn');
        const hiddenInput = container.querySelector('input[type="hidden"]');
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const val = btn.dataset.val;
                hiddenInput.value = val;
                buttons.forEach(b => {
                    b.classList.remove('btn-primary');
                    b.classList.add('btn-outline-primary');
                });
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-primary');
                recalculateAll();
            });
        });
    });

    // Bind interactive triggers for Image Choice
    document.querySelectorAll('.image-choice-container label').forEach(lbl => {
        const radio = lbl.querySelector('input[type="radio"]');
        if (radio) {
            radio.addEventListener('change', () => {
                lbl.closest('.image-choice-container').querySelectorAll('label').forEach(l => {
                    l.classList.remove('border-primary');
                });
                if (radio.checked) {
                    lbl.classList.add('border-primary');
                }
                recalculateAll();
            });
        }
    });

    // Bind interactive triggers for Repeater Rows addition and removal
    document.querySelectorAll('.repeater-field-container').forEach(container => {
        const list = container.querySelector('.repeater-rows-list');
        const addBtn = container.querySelector('.add-repeater-row');
        
        if (addBtn && list) {
            addBtn.addEventListener('click', () => {
                const template = list.firstElementChild;
                if (template) {
                    const clone = template.cloneNode(true);
                    clone.querySelectorAll('input').forEach(input => input.value = '');
                    list.appendChild(clone);
                    bindRowRemovers(clone);
                    recalculateAll();
                }
            });
            list.querySelectorAll('.repeater-row').forEach(row => bindRowRemovers(row));
        }

        function bindRowRemovers(row) {
            const rmBtn = row.querySelector('.remove-repeater-row');
            if (rmBtn) {
                rmBtn.addEventListener('click', () => {
                    if (list.children.length > 1) {
                        row.remove();
                        recalculateAll();
                    }
                });
            }
        }
    });

    const ajaxForm = document.getElementById('ajaxForm');

    // Bind listeners to all inputs to trigger recalculations live
    ajaxForm.addEventListener('input', recalculateAll);
    ajaxForm.addEventListener('change', recalculateAll);

    // Intercept form submissions via AJAX
    let submitterStatus = 'submitted';
    ajaxForm.querySelectorAll('button[type="submit"]').forEach(btn => {
        btn.addEventListener('click', function() {
            submitterStatus = this.value || 'submitted';
        });
    });

    ajaxForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const submitButtons = ajaxForm.querySelectorAll('button[type="submit"]');
        submitButtons.forEach(btn => btn.disabled = true);

        const statusContainer = document.getElementById('ajaxSubmitStatusContainer');
        const loader = document.getElementById('ajaxSubmitLoader');
        const successBox = document.getElementById('ajaxSubmitSuccess');
        const errorBox = document.getElementById('ajaxSubmitError');

        statusContainer.classList.remove('d-none');
        loader.classList.remove('d-none');
        successBox.classList.add('d-none');
        errorBox.classList.add('d-none');

        const formData = new FormData(ajaxForm);
        formData.set('status', submitterStatus);

        const targetUrl = submitterStatus === 'draft' 
            ? '/forms/<?= htmlspecialchars($form['slug']) ?>/draft' 
            : '/forms/<?= htmlspecialchars($form['slug']) ?>/submit';

        fetch(targetUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(async response => {
            const contentType = response.headers.get('content-type') || '';
            let data = null;
            if (contentType.includes('application/json')) {
                data = await response.json();
            }

            if (response.ok && data && data.success) {
                loader.classList.add('d-none');
                successBox.classList.remove('d-none');
                const successMsg = data.message ? data.message : 'Η ενέργεια ολοκληρώθηκε επιτυχώς!';
                document.getElementById('ajaxSuccessMsg').textContent = successMsg;
                setTimeout(() => {
                    window.location.href = data.redirect ? data.redirect : '/my-submissions';
                }, 1000);
            } else {
                submitButtons.forEach(btn => btn.disabled = false);
                let errorMsg = (data && data.message) ? data.message : 'Παρουσιάστηκε σφάλμα κατά την υποβολή.';
                if (data && data.errors && typeof data.errors === 'object') {
                    const errList = [];
                    for (const k in data.errors) {
                        if (Array.isArray(data.errors[k])) errList.push(data.errors[k].join(', '));
                        else errList.push(data.errors[k]);
                    }
                    if (errList.length > 0) errorMsg += ' (' + errList.join(' | ') + ')';
                }
                throw new Error(errorMsg);
            }
        })
        .catch(err => {
            submitButtons.forEach(btn => btn.disabled = false);
            loader.classList.add('d-none');
            errorBox.classList.remove('d-none');
            document.getElementById('ajaxErrorMsg').textContent = err.message || 'Παρουσιάστηκε σφάλμα κατά την υποβολή.';
        });
    });

    // Terms of Use Modal Interactive Logic
    const openTermsLink = document.getElementById('openTermsLink');
    const termsModal = document.getElementById('termsModal');
    const closeTermsBtn = document.getElementById('closeTermsBtn');

    if (openTermsLink && termsModal) {
        openTermsLink.addEventListener('click', (e) => {
            e.preventDefault();
            termsModal.classList.remove('d-none');
            closeTermsBtn.focus();
        });

        const closeTerms = () => {
            termsModal.classList.add('d-none');
            openTermsLink.focus();
        };

        closeTermsBtn.addEventListener('click', closeTerms);
        termsModal.addEventListener('click', (e) => {
            if (e.target === termsModal) {
                closeTerms();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !termsModal.classList.contains('d-none')) {
                closeTerms();
            }
        });
    }

    // Multi-Step Form Navigation Engine
    let currentStep = 1;
    const stepContainers = [];
    const pageBreaks = [];

    // Parse steps based on page_break fields in schema
    let currentStepFields = [];
    const fieldsInSteps = {};

    if (schema.sections) {
        schema.sections.forEach(sec => {
            if (sec.fields) {
                sec.fields.forEach(f => {
                    if (f.type === 'page_break') {
                        pageBreaks.push(f);
                        fieldsInSteps[pageBreaks.length] = currentStepFields;
                        currentStepFields = [];
                    } else {
                        currentStepFields.push(f.key);
                    }
                });
            }
        });
    }
    fieldsInSteps[pageBreaks.length + 1] = currentStepFields;
    const totalSteps = pageBreaks.length + 1;

    // Create steps wrapper structures in DOM dynamically if multi-step is active
    if (totalSteps > 1) {
        const formFieldsWrappers = Array.from(ajaxForm.querySelectorAll('.form-field-wrapper, .mb-5.border-bottom.border-glass.pb-4'));
        
        // Build progressive progress indicator container
        const progressWrapper = document.createElement('div');
        progressWrapper.className = 'mb-4';
        progressWrapper.id = 'formProgressContainer';
        ajaxForm.parentNode.insertBefore(progressWrapper, ajaxForm);

        // Group fields visually inside step content elements
        let stepNum = 1;
        let currentStepEl = document.createElement('div');
        currentStepEl.setAttribute('data-form-step', stepNum);
        currentStepEl.className = 'form-step-container';
        if (stepNum > 1) currentStepEl.classList.add('d-none');
        ajaxForm.insertBefore(currentStepEl, ajaxForm.firstChild);
        stepContainers.push(currentStepEl);

        const formChildren = Array.from(ajaxForm.children);
        formChildren.forEach(child => {
            if (child.tagName === 'INPUT' || child.tagName === 'DIV' && child.id === 'ajaxSubmitStatusContainer' || child.className === 'd-flex gap-3 justify-content-end') {
                return; // Keep submit buttons and hidden inputs top-level
            }
            if (child === currentStepEl) return;

            // Detect if contains a page break element inside sections or as a sibling
            const hasPageBreakInside = child.querySelector('[id*="page_break"]') || (child.dataset && child.dataset.fieldKey && fieldsInSteps[stepNum] && !fieldsInSteps[stepNum].includes(child.dataset.fieldKey));
            
            // Move child to current step
            currentStepEl.appendChild(child);

            // If we crossed a page boundary, open next step wrapper
            const lastFieldKey = fieldsInSteps[stepNum] ? fieldsInSteps[stepNum][fieldsInSteps[stepNum].length - 1] : null;
            const reachedEnd = lastFieldKey && child.querySelector(`#wrapper_${lastFieldKey}, [id*="${lastFieldKey}"]`);
            if (reachedEnd && stepNum < totalSteps) {
                stepNum++;
                currentStepEl = document.createElement('div');
                currentStepEl.setAttribute('data-form-step', stepNum);
                currentStepEl.className = 'form-step-container d-none';
                ajaxForm.insertBefore(currentStepEl, ajaxForm.querySelector('.d-flex.gap-3.justify-content-end'));
                stepContainers.push(currentStepEl);
            }
        });

        // Add Next / Previous controls to footer container
        const footerContainer = ajaxForm.querySelector('.d-flex.gap-3.justify-content-end');
        
        const btnPrev = document.createElement('button');
        btnPrev.type = 'button';
        btnPrev.className = 'btn btn-outline-secondary d-none';
        btnPrev.id = 'btnPrevStep';
        btnPrev.innerHTML = '<i class="fa-solid fa-arrow-left me-1"></i> ΠΡΟΗΓΟΥΜΕΝΟ';
        
        const btnNext = document.createElement('button');
        btnNext.type = 'button';
        btnNext.className = 'btn btn-premium';
        btnNext.id = 'btnNextStep';
        btnNext.innerHTML = 'ΕΠΟΜΕΝΟ <i class="fa-solid fa-arrow-right ms-1"></i>';

        footerContainer.insertBefore(btnPrev, footerContainer.firstChild);
        footerContainer.insertBefore(btnNext, footerContainer.querySelector('.btn-premium'));

        // Update progress bar helper
        function updateProgress() {
            // Find current active step titles
            const activePageBreak = pageBreaks[currentStep - 2];
            const nextPageBreak = pageBreaks[currentStep - 1];
            const currentTitle = (currentStep === 1) ? (pageBreaks[0]?.pageTitle || 'Βήμα 1') : (activePageBreak?.pageTitle || `Βήμα ${currentStep}`);

            progressWrapper.innerHTML = `
                <div class="d-flex justify-content-between mb-1" style="font-size: 0.85rem;">
                    <span class="text-white-50">Βήμα <strong>${currentStep}</strong> από <strong>${totalSteps}</strong>: <span class="text-white">${currentTitle}</span></span>
                    <span class="text-muted">${Math.round(((currentStep - 1) / (totalSteps - 1 || 1)) * 100)}%</span>
                </div>
                <div class="progress" style="height: 6px; background: rgba(255,255,255,0.1);">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: ${((currentStep - 1) / (totalSteps - 1 || 1)) * 100}%" aria-valuenow="${currentStep}" aria-valuemin="1" aria-valuemax="${totalSteps}"></div>
                </div>
            `;

            // Adjust navigation buttons visibility
            btnPrev.classList.toggle('d-none', currentStep === 1);
            btnNext.classList.toggle('d-none', currentStep === totalSteps);

            const submitBtn = footerContainer.querySelector('button[type="submit"], button[onclick*="alert"]');
            if (submitBtn) {
                submitBtn.classList.toggle('d-none', currentStep !== totalSteps);
            }

            // Keyboard accessibility adjustments: hide non-active steps from focus
            stepContainers.forEach((container, idx) => {
                const isActive = (idx + 1) === currentStep;
                container.classList.toggle('d-none', !isActive);
                container.querySelectorAll('input, select, textarea, button').forEach(el => {
                    if (isActive) {
                        el.removeAttribute('tabindex');
                    } else {
                        el.setAttribute('tabindex', '-1');
                    }
                });
            });
        }

        // Validate visible step inputs helper
        function isStepValid(step) {
            const keys = fieldsInSteps[step] || [];
            let isValid = true;
            let firstInvalid = null;

            keys.forEach(key => {
                const wrapper = document.getElementById(`wrapper_${key}`);
                // Only validate if wrapper is visible (conditional logic check)
                if (wrapper && !wrapper.classList.contains('d-none') && wrapper.style.display !== 'none') {
                    const inputs = wrapper.querySelectorAll('input[required], select[required], textarea[required]');
                    inputs.forEach(input => {
                        // Check checkbox
                        if (input.type === 'checkbox' && !input.checked) {
                            isValid = false;
                            input.classList.add('is-invalid');
                            if (!firstInvalid) firstInvalid = input;
                        } else if (input.type === 'radio') {
                            const name = input.name;
                            const checked = wrapper.querySelector(`input[name="${name}"]:checked`);
                            if (!checked) {
                                isValid = false;
                                input.classList.add('is-invalid');
                                if (!firstInvalid) firstInvalid = input;
                            }
                        } else if (!input.value.trim()) {
                            isValid = false;
                            input.classList.add('is-invalid');
                            if (!firstInvalid) firstInvalid = input;
                        } else {
                            input.classList.remove('is-invalid');
                        }
                    });
                }
            });

            if (firstInvalid) {
                firstInvalid.focus();
            }
            return isValid;
        }

        // Check if all fields in a step are hidden dynamically (auto-skip check)
        function isStepEmpty(step) {
            const keys = fieldsInSteps[step] || [];
            let allHidden = true;
            keys.forEach(key => {
                const wrapper = document.getElementById(`wrapper_${key}`);
                if (wrapper && !wrapper.classList.contains('d-none') && wrapper.style.display !== 'none') {
                    allHidden = false;
                }
            });
            return allHidden;
        }

        btnNext.addEventListener('click', () => {
            if (isStepValid(currentStep)) {
                // Determine next non-empty step
                let targetStep = currentStep + 1;
                while (targetStep < totalSteps && isStepEmpty(targetStep)) {
                    targetStep++;
                }
                currentStep = targetStep;
                updateProgress();
                window.scrollTo({ top: ajaxForm.offsetTop - 20, behavior: 'smooth' });
            }
        });

        btnPrev.addEventListener('click', () => {
            // Determine previous non-empty step
            let targetStep = currentStep - 1;
            while (targetStep > 1 && isStepEmpty(targetStep)) {
                targetStep--;
            }
            currentStep = targetStep;
            updateProgress();
            window.scrollTo({ top: ajaxForm.offsetTop - 20, behavior: 'smooth' });
        });

        // Initialize indicator bar
        updateProgress();
    }

    // Initial run
    recalculateAll();
});
</script>
