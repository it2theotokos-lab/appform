<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="/admin/forms" class="btn btn-outline-secondary btn-sm mb-2"><i class="fa-solid fa-arrow-left"></i> Πίσω</a>
        <h3 class="font-heading mb-0 text-soft"><?= \App\Core\View::escape($title) ?></h3>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" id="togglePropertiesBtn"><i class="fa-solid fa-eye-slash me-1"></i> Show / Hide Properties</button>
        <button class="btn btn-premium btn-sm" id="saveSchemaBtn">Αποθήκευση Σχεδίου <i class="fa-solid fa-save ms-1"></i></button>
    </div>
</div>

<div id="schemaAlert" class="alert alert-danger d-none" role="alert"></div>

<div class="row g-4">
    <!-- Left Panel: Field Palette -->
    <div class="col-xl-3 col-lg-4 col-md-12">
        <div class="glass-panel p-3">
            <h6 class="font-heading mb-3 border-bottom pb-2 text-soft" style="border-color: var(--color-border) !important;">Στοιχεία Φόρμας</h6>
            
            <div class="accordion" id="paletteAccordion">
                <!-- Βασικά Πεδία -->
                <div class="accordion-item bg-transparent" style="border-color: var(--color-border) !important;">
                    <h2 class="accordion-header" id="headingBasic">
                        <button class="accordion-button bg-transparent collapsed font-heading py-2 text-soft" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBasic" aria-expanded="false" aria-controls="collapseBasic">
                            Βασικά Πεδία
                        </button>
                    </h2>
                    <div id="collapseBasic" class="accordion-collapse collapse" aria-labelledby="headingBasic" data-bs-parent="#paletteAccordion">
                        <div class="accordion-body d-flex flex-column gap-2 p-2">
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="text"><i class="fa-solid fa-font me-2"></i> Κείμενο (Text)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="textarea"><i class="fa-solid fa-align-left me-2"></i> Παράγραφος (Textarea)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="number"><i class="fa-solid fa-hashtag me-2"></i> Αριθμός (Number)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="email"><i class="fa-solid fa-envelope me-2"></i> Email</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="date"><i class="fa-solid fa-calendar me-2"></i> Ημερομηνία</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="file"><i class="fa-solid fa-file-arrow-up me-2"></i> Ανέβασμα Αρχείου</button>
                        </div>
                    </div>
                </div>

                <!-- Προηγμένα Πεδία -->
                <div class="accordion-item bg-transparent" style="border-color: var(--color-border) !important;">
                    <h2 class="accordion-header" id="headingAdvanced">
                        <button class="accordion-button bg-transparent collapsed font-heading py-2 text-soft" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdvanced" aria-expanded="false" aria-controls="collapseAdvanced">
                            Προηγμένα Πεδία
                        </button>
                    </h2>
                    <div id="collapseAdvanced" class="accordion-collapse collapse" aria-labelledby="headingAdvanced" data-bs-parent="#paletteAccordion">
                        <div class="accordion-body d-flex flex-column gap-2 p-2">
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="phone"><i class="fa-solid fa-phone me-2"></i> Τηλέφωνο (Phone)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="url"><i class="fa-solid fa-globe me-2"></i> Ιστοσελίδα (URL)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="time"><i class="fa-solid fa-clock me-2"></i> Ώρα (Time)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="password"><i class="fa-solid fa-lock me-2"></i> Κωδικός (Password)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="hidden"><i class="fa-solid fa-eye-slash me-2"></i> Κρυφό (Hidden)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="rich_text"><i class="fa-solid fa-file-signature me-2"></i> Rich Text</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="rating"><i class="fa-solid fa-star me-2"></i> Αξιολόγηση (Rating)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="range"><i class="fa-solid fa-sliders me-2"></i> Range Slider</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="address"><i class="fa-solid fa-map-location-dot me-2"></i> Διεύθυνση (Address)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="currency"><i class="fa-solid fa-coins me-2"></i> Νόμισμα (Currency)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="calculated"><i class="fa-solid fa-calculator me-2"></i> Υπολογιζόμενο (Calculated)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="repeater"><i class="fa-solid fa-layer-group me-2"></i> Repeater Field</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="signature"><i class="fa-solid fa-pen-nib me-2"></i> Υπογραφή (Signature)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="likert"><i class="fa-solid fa-list-check me-2"></i> Κλίμακα Likert</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="nps"><i class="fa-solid fa-gauge-high me-2"></i> Net Promoter Score (NPS)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="dynamic_select"><i class="fa-solid fa-database me-2"></i> Dynamic Select</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="repository_autocomplete"><i class="fa-solid fa-search me-2"></i> Autocomplete από Repository</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="repository_tags"><i class="fa-solid fa-tags me-2"></i> Tags από Repository</button>
                        </div>
                    </div>
                </div>

                <!-- Πεδία Επιλογών -->
                <div class="accordion-item bg-transparent" style="border-color: var(--color-border) !important;">
                    <h2 class="accordion-header" id="headingChoices">
                        <button class="accordion-button bg-transparent collapsed font-heading py-2 text-soft" type="button" data-bs-toggle="collapse" data-bs-target="#collapseChoices" aria-expanded="false" aria-controls="collapseChoices">
                            Πεδία Επιλογών
                        </button>
                    </h2>
                    <div id="collapseChoices" class="accordion-collapse collapse" aria-labelledby="headingChoices" data-bs-parent="#paletteAccordion">
                        <div class="accordion-body d-flex flex-column gap-2 p-2">
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="select"><i class="fa-solid fa-square-caret-down me-2"></i> Dropdown Select</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="radio"><i class="fa-solid fa-circle-dot me-2"></i> Επιλογή Radio</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="checkbox"><i class="fa-solid fa-square-check me-2"></i> Checkbox</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="consent"><i class="fa-solid fa-shield-halved me-2"></i> GDPR Consent</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="image_choice"><i class="fa-solid fa-image me-2"></i> Image Choice</button>
                        </div>
                    </div>
                </div>

                <!-- Πεδία Διάταξης -->
                <div class="accordion-item bg-transparent" style="border-color: var(--color-border) !important;">
                    <h2 class="accordion-header" id="headingLayout">
                        <button class="accordion-button bg-transparent collapsed font-heading py-2 text-soft" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLayout" aria-expanded="false" aria-controls="collapseLayout">
                            Πεδία Διάταξης
                        </button>
                    </h2>
                    <div id="collapseLayout" class="accordion-collapse collapse" aria-labelledby="headingLayout" data-bs-parent="#paletteAccordion">
                        <div class="accordion-body d-flex flex-column gap-2 p-2">
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="heading"><i class="fa-solid fa-heading me-2"></i> Τίτλος (Heading)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="html"><i class="fa-solid fa-code me-2"></i> HTML / Περιεχόμενο</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="divider"><i class="fa-solid fa-minus me-2"></i> Διαχωριστικό (Divider)</button>
                            <button class="btn btn-outline-primary btn-sm text-start palette-item" data-type="page_break"><i class="fa-solid fa-forward me-2"></i> Αλλαγή Σελίδας (Page Break)</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Central Panel: Form canvas -->
    <div class="col-xl-6 col-lg-8 col-md-12">
        <div class="glass-panel p-4 mb-4">
            <h5 class="font-heading mb-3 border-bottom pb-2 text-soft" style="border-color: var(--color-border) !important;">Σχεδίαση Φόρμας</h5>
            <div id="canvasSections" class="builder-canvas">
                <!-- Javascript will load sections and fields -->
            </div>
            <button class="btn btn-outline-success btn-sm w-100 mt-3" id="addSectionBtn"><i class="fa-solid fa-folder-plus me-1"></i> Προσθήκη Ενότητας (Section)</button>
        </div>
    </div>

    <!-- Right Panel: Properties configuration -->
    <div class="col-xl-3 col-lg-12 col-md-12">
        <div class="glass-panel p-3 d-none" id="propertiesPanel">
            <h6 class="font-heading mb-3 border-bottom pb-2 text-soft" style="border-color: var(--color-border) !important;">Ρυθμίσεις Πεδίου</h6>
            <form id="propertiesForm" onsubmit="return false;">
                <input type="hidden" id="propFieldId">
                <input type="hidden" id="propSectionId">
                
                <div class="mb-2">
                    <label class="form-label small">Κλειδί Πεδίου (Key - Unique)</label>
                    <input type="text" class="form-control form-control-sm" id="propKey" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Ετικέτα (Label)</label>
                    <input type="text" class="form-control form-control-sm" id="propLabel" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Placeholder</label>
                    <input type="text" class="form-control form-control-sm" id="propPlaceholder">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Help Text</label>
                    <input type="text" class="form-control form-control-sm" id="propHelp">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Πλάτος (Width - Columns 1-12)</label>
                    <select class="form-select form-select-sm" id="propWidth">
                        <option value="12">Full Width (12)</option>
                        <option value="6">Half Width (6)</option>
                        <option value="4">One Third (4)</option>
                    </select>
                </div>

                <div class="mb-2 form-check">
                    <input type="checkbox" class="form-check-input" id="propRequired">
                    <label class="form-check-label small" for="propRequired">Υποχρεωτικό πεδίο</label>
                </div>

                <!-- DataSource settings (select/radio) -->
                <div class="d-none" id="dataSourceGroup">
                    <div class="mb-2">
                        <label class="form-label small">Πηγή Δεδομένων</label>
                        <select class="form-select form-select-sm" id="propDataSource">
                            <option value="static">Στατικές Επιλογές</option>
                            <option value="repository">Repository (Βάση)</option>
                        </select>
                    </div>
                    <div class="mb-2 d-none" id="propStaticOptionsGroup">
                        <label class="form-label small">Στατικές Επιλογές (τιμή,ετικέτα ανά γραμμή)</label>
                        <textarea class="form-control form-control-sm" id="propStaticOptions" rows="3" placeholder="key,value"></textarea>
                    </div>
                    <div class="mb-2 d-none" id="propRepositoryGroup">
                        <label class="form-label small">Επιλογή Repository</label>
                        <select class="form-select form-select-sm" id="propRepositoryId">
                            <option value="">Επιλέξτε Repository...</option>
                            <?php foreach ($repositories as $repo): ?>
                                <option value="<?= $repo['id'] ?>"><?= \App\Core\View::escape($repo['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2" id="propDefaultValuesGroup">
                        <label class="form-label small">Προεπιλεγμένες Τιμές (comma-separated default_values)</label>
                        <input type="text" class="form-control form-control-sm" id="propDefaultValues" placeholder="e.g. 1, 2, 8">
                    </div>
                </div>

                <!-- Calculated Field Properties -->
                <div class="d-none" id="calculatedGroup">
                    <div class="mb-2">
                        <label class="form-label small">Μαθηματικός Τύπος (Formula)</label>
                        <input type="text" class="form-control form-control-sm" id="propFormula" placeholder="{field_a} * {field_b}">
                        <small class="text-muted" style="font-size: 10px;">Υποστηρίζει: +, -, *, /, %, sum(), min(), max(), round(), abs(), κλπ.</small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Δεκαδικά Ψηφία (Decimal Places)</label>
                        <input type="number" class="form-control form-control-sm" id="propDecimalPlaces" value="2">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Πρόθεμα (Prefix)</label>
                        <input type="text" class="form-control form-control-sm" id="propPrefix" placeholder="$">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Επίθεμα (Suffix)</label>
                        <input type="text" class="form-control form-control-sm" id="propSuffix" placeholder="€">
                    </div>
                    <div class="mb-2 form-check">
                        <input type="checkbox" class="form-check-input" id="propReadOnly" checked>
                        <label class="form-check-label small" for="propReadOnly">Μόνο για Ανάγνωση (Read Only)</label>
                    </div>
                    <div class="mb-2 form-check">
                        <input type="checkbox" class="form-check-input" id="propHidden">
                        <label class="form-check-label small" for="propHidden">Κρυφό από το Χρήστη (Hidden)</label>
                    </div>
                </div>

                <!-- File properties -->
                <div class="d-none" id="fileGroup">
                    <div class="mb-2">
                        <label class="form-label small">Επιτρεπόμενοι τύποι (comma separated)</label>
                        <input type="text" class="form-control form-control-sm" id="propFileTypes" placeholder="pdf,docx,jpg">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Μέγιστο Μέγεθος (MB)</label>
                        <input type="number" class="form-control form-control-sm" id="propFileSize" value="5">
                    </div>
                </div>

                <!-- Phone properties -->
                <div class="d-none" id="phoneGroup">
                    <div class="mb-2">
                        <label class="form-label small">Μορφή Εισαγωγής (Format)</label>
                        <select class="form-select form-select-sm" id="propPhoneFormat">
                            <option value="international">Διεθνές (International)</option>
                            <option value="national">Εθνικό (National)</option>
                        </select>
                    </div>
                </div>

                <!-- URL properties -->
                <div class="d-none" id="urlGroup">
                    <div class="mb-2">
                        <label class="form-label small">Allowed Protocols (comma separated)</label>
                        <input type="text" class="form-control form-control-sm" id="propUrlProtocols" value="http,https">
                    </div>
                </div>

                <!-- Time properties -->
                <div class="d-none" id="timeGroup">
                    <div class="mb-2">
                        <label class="form-label small">Μορφή Ώρας (Format)</label>
                        <select class="form-select form-select-sm" id="propTimeFormat">
                            <option value="24">24-hour</option>
                            <option value="12">12-hour</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Διάστημα Λεπτών (Interval)</label>
                        <input type="number" class="form-control form-control-sm" id="propTimeInterval" value="15">
                    </div>
                </div>

                <!-- Rating properties -->
                <div class="d-none" id="ratingGroup">
                    <div class="mb-2">
                        <label class="form-label small">Μέγιστη Αξιολόγηση (Max Stars)</label>
                        <input type="number" class="form-control form-control-sm" id="propRatingMax" value="5" min="3" max="10">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Τύπος Εικονιδίου (Icon)</label>
                        <select class="form-select form-select-sm" id="propRatingIcon">
                            <option value="star">Star</option>
                            <option value="heart">Heart</option>
                            <option value="circle">Circle</option>
                        </select>
                    </div>
                </div>

                <!-- Range Slider properties -->
                <div class="d-none" id="rangeGroup">
                    <div class="mb-2">
                        <label class="form-label small">Ελάχιστο (Min)</label>
                        <input type="number" class="form-control form-control-sm" id="propRangeMin" value="0">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Μέγιστο (Max)</label>
                        <input type="number" class="form-control form-control-sm" id="propRangeMax" value="100">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Βήμα (Step)</label>
                        <input type="number" class="form-control form-control-sm" id="propRangeStep" value="5">
                    </div>
                </div>

                <!-- GDPR Consent properties -->
                <div class="d-none" id="consentGroup">
                    <div class="mb-2">
                        <label class="form-label small">Κείμενο Συγκατάθεσης (Consent Text)</label>
                        <textarea class="form-control form-control-sm" id="propConsentText" rows="3"></textarea>
                    </div>
                </div>

                <!-- Currency properties -->
                <div class="d-none" id="currencyGroup">
                    <div class="mb-2">
                        <label class="form-label small">Κωδικός Νομίσματος (Currency)</label>
                        <select class="form-select form-select-sm" id="propCurrencyCode">
                            <option value="EUR">EUR (€)</option>
                            <option value="USD">USD ($)</option>
                            <option value="GBP">GBP (£)</option>
                        </select>
                    </div>
                </div>

                <!-- HTML Block properties -->
                <div class="d-none" id="htmlBlockGroup">
                    <div class="mb-2">
                        <label class="form-label small">Περιεχόμενο HTML (Content)</label>
                        <textarea class="form-control form-control-sm" id="propHtmlContent" rows="4"></textarea>
                    </div>
                </div>

                <!-- Repeater properties -->
                <div class="d-none" id="repeaterGroup">
                    <div class="mb-2">
                        <label class="form-label small">Ελάχιστες Σειρές (Min Rows)</label>
                        <input type="number" class="form-control form-control-sm" id="propRepeaterMin" value="1">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Μέγιστες Σειρές (Max Rows)</label>
                        <input type="number" class="form-control form-control-sm" id="propRepeaterMax" value="10">
                    </div>
                </div>

                <!-- Likert Scale properties -->
                <div class="d-none" id="likertGroup">
                    <div class="mb-2">
                        <label class="form-label small">Τύπος Απάντησης (Answer Type)</label>
                        <select class="form-select form-select-sm" id="propLikertType">
                            <option value="single">Μονή Απάντηση (Single Option)</option>
                            <option value="multiple">Πολλαπλή Απάντηση (Multiple Options)</option>
                        </select>
                    </div>
                </div>

                <!-- Image Choice properties -->
                <div class="d-none" id="imageChoiceGroup">
                    <div class="mb-2">
                        <label class="form-label small">Επιλογές Εικόνων (Image URL, Label, Stored Value per line)</label>
                        <textarea class="form-control form-control-sm" id="propImageChoices" rows="3" placeholder="http://image-url,Label,Value"></textarea>
                    </div>
                </div>

                <!-- Dynamic Repository Select properties -->
                <div class="d-none" id="dynamicSelectGroup">
                    <div class="mb-2">
                        <label class="form-label small">Repository Source</label>
                        <select class="form-select form-select-sm" id="propDynamicSourceId">
                            <option value="">Επιλέξτε Repository...</option>
                            <?php foreach ($repositories as $repo): ?>
                                <option value="<?= $repo['id'] ?>"><?= \App\Core\View::escape($repo['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Repository Autocomplete properties -->
                <div class="d-none" id="repoAutoGroup">
                    <div class="mb-2">
                        <label class="form-label small">Source Repository</label>
                        <select class="form-select form-select-sm" id="propRepoAutoSourceId" onchange="updateRepoAutoDropdowns()">
                            <option value="">Επιλέξτε Repository...</option>
                            <?php foreach ($repositories as $repo): 
                                $cols = empty($repo['columns_json']) ? '[]' : $repo['columns_json'];
                            ?>
                                <option value="<?= $repo['id'] ?>" data-columns='<?= \App\Core\View::escape($cols) ?>'><?= \App\Core\View::escape($repo['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Πεδίο Αναζήτησης (Search Field)</label>
                        <select class="form-select form-select-sm" id="propRepoAutoSearchField">
                            <option value="label">Display Label (label)</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Πεδίο Εμφάνισης (Display Field)</label>
                        <select class="form-select form-select-sm" id="propRepoAutoValueField">
                            <option value="label">Display Label (label)</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Αντιστοιχίσεις (Mappings)</label>
                        <div id="propRepoAutoMappingsContainer" class="d-flex flex-column gap-2 mb-2"></div>
                        <button type="button" class="btn btn-outline-info btn-xs w-100" onclick="addRepoAutoMapping()"><i class="fa-solid fa-plus me-1"></i> Προσθήκη Mapping</button>
                        <small class="text-muted d-block mt-1" style="font-size: 10px;">Θα γεμίζει αυτόματα αυτά τα πεδία της φόρμας με τις τιμές από το επιλεγμένο record.</small>
                    </div>
                </div>

                <!-- Repository Tags properties -->
                <div class="d-none" id="repoTagsGroup">
                    <div class="mb-2">
                        <label class="form-label small">Source Repository 1 (Υποχρεωτικό)</label>
                        <select class="form-select form-select-sm" id="propRepoTagsSource1" onchange="updateRepoTagsDropdowns(1)">
                            <option value="">Επιλέξτε Repository...</option>
                            <?php foreach ($repositories as $repo): 
                                $cols = empty($repo['columns_json']) ? '[]' : $repo['columns_json'];
                            ?>
                                <option value="<?= $repo['id'] ?>" data-columns='<?= \App\Core\View::escape($cols) ?>'><?= \App\Core\View::escape($repo['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Πεδίο Αναζήτησης/Εμφάνισης 1</label>
                        <select class="form-select form-select-sm" id="propRepoTagsSearch1">
                            <option value="label">Display Label (label)</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Source Repository 2 (Προαιρετικό)</label>
                        <select class="form-select form-select-sm" id="propRepoTagsSource2" onchange="updateRepoTagsDropdowns(2)">
                            <option value="">Επιλέξτε Repository 2...</option>
                            <?php foreach ($repositories as $repo): 
                                $cols = empty($repo['columns_json']) ? '[]' : $repo['columns_json'];
                            ?>
                                <option value="<?= $repo['id'] ?>" data-columns='<?= \App\Core\View::escape($cols) ?>'><?= \App\Core\View::escape($repo['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Πεδίο Αναζήτησης/Εμφάνισης 2</label>
                        <select class="form-select form-select-sm" id="propRepoTagsSearch2">
                            <option value="label">Display Label (label)</option>
                        </select>
                    </div>
                </div>

                <!-- Conditional Logic Properties Section -->
                <div class="border border-glass rounded p-2 mt-3" id="conditionalLogicSection">
                    <h6 class="text-white small font-heading mb-2">Κριτήρια Εμφάνισης (Conditional Logic)</h6>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="propCondEnabled">
                        <label class="form-check-label small" for="propCondEnabled">Ενεργοποίηση</label>
                    </div>
                    <div class="d-none" id="propCondBody">
                        <div class="mb-2">
                            <label class="form-label small">Ενέργεια (Action)</label>
                            <select class="form-select form-select-sm" id="propCondAction">
                                <option value="show">Εμφάνιση (Show)</option>
                                <option value="hide">Απόκρυψη (Hide)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Συνθήκη (Match Mode)</label>
                            <select class="form-select form-select-sm" id="propCondMatch">
                                <option value="all">Όλα τα κριτήρια (All)</option>
                                <option value="any">Οποιοδήποτε κριτήριο (Any)</option>
                            </select>
                        </div>
                        <div id="propCondRulesList" class="mb-2">
                            <!-- Rules will list here dynamically -->
                        </div>
                        <button type="button" class="btn btn-outline-success btn-xs" id="addCondRuleBtn"><i class="fa-solid fa-plus me-1"></i>Προσθήκη Κανόνα</button>
                    </div>
                </div>

                <!-- System Prefill Properties Section -->
                <div class="border border-glass rounded p-2 mt-3" id="systemPrefillSection">
                    <h6 class="text-white small font-heading mb-2">System Prefill (Προ-συμπλήρωση Πεδίου)</h6>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="propPrefillEnabled">
                        <label class="form-check-label small" for="propPrefillEnabled">Ενεργοποίηση Προ-συμπλήρωσης</label>
                    </div>
                    <div class="d-none" id="propPrefillBody">
                        <div class="mb-2">
                            <label class="form-label small">Σύστημα / smart tag</label>
                            <select class="form-select form-select-sm" id="propPrefillTag">
                                <option value="">Επιλέξτε smart tag...</option>
                                <option value="{user_id}">{user_id} (ID Χρήστη)</option>
                                <option value="{user_display}">{user_display} (Ονοματεπώνυμο)</option>
                                <option value="{user_first_name}">{user_first_name} (Όνομα)</option>
                                <option value="{user_last_name}">{user_last_name} (Επώνυμο)</option>
                                <option value="{user_email}">{user_email} (Email Χρήστη)</option>
                                <option value="{employee_id}">{employee_id} (ID Υπαλλήλου)</option>
                                <option value="{user_department}">{user_department} (Τμήμα)</option>
                                <option value="{user_department_id}">{user_department_id} (ID Τμήματος)</option>
                                <option value="{user_role}">{user_role} (Ρόλος)</option>
                                <option value="{user_role_id}">{user_role_id} (ID Ρόλου)</option>
                                <option value="{user_manager}">{user_manager} (Προϊστάμενος)</option>
                                <option value="{user_manager_email}">{user_manager_email} (Email Προϊσταμένου)</option>
                                <option value="{current_date}">{current_date} (Ημερομηνία)</option>
                                <option value="{current_time}">{current_time} (Ώρα)</option>
                                <option value="{current_datetime}">{current_datetime} (Ημ/νία & Ώρα)</option>
                                <option value="{form_id}">{form_id} (ID Φόρμας)</option>
                                <option value="{form_title}">{form_title} (Τίτλος Φόρμας)</option>
                                <option value="{form_slug}">{form_slug} (Slug Φόρμας)</option>
                            </select>
                        </div>
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="propPrefillReadonly">
                            <label class="form-check-label small" for="propPrefillReadonly">Μόνο για Ανάγνωση (Read Only)</label>
                        </div>
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="propPrefillHidden">
                            <label class="form-check-label small" for="propPrefillHidden">Κρυφό Πεδίο (Hidden from User)</label>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary btn-sm w-100 mt-3" id="applyPropBtn">Εφαρμογή Αλλαγών</button>
            </form>
        </div>
    </div>
</div>

<script>
const initialSchema = <?= json_encode($schema ?? [
    'schemaVersion' => 1,
    'settings' => [
        'submitLabel' => 'Υποβολή',
        'draftLabel' => 'Αποθήκευση ως πρόχειρο',
        'allowDraft' => true
    ],
    'sections' => []
]) ?>;

document.addEventListener('DOMContentLoaded', () => {
    let schema = JSON.parse(JSON.stringify(initialSchema));
    const canvas = document.getElementById('canvasSections');
    const saveBtn = document.getElementById('saveSchemaBtn');
    const addSectionBtn = document.getElementById('addSectionBtn');
    const propPanel = document.getElementById('propertiesPanel');
    const csrfToken = '<?= \App\Core\Csrf::token() ?>';

    function renderCanvas() {
        canvas.innerHTML = '';
        if (schema.sections.length === 0) {
            canvas.innerHTML = '<p class="text-muted text-center py-5">Δεν έχουν προστεθεί ενότητες. Πατήστε "Προσθήκη Ενότητας".</p>';
            return;
        }

        schema.sections.forEach((section, sIdx) => {
            const sectionEl = document.createElement('div');
            sectionEl.className = 'border rounded-3 p-3 mb-4 section-card';
            sectionEl.style.borderColor = 'var(--color-border)';
            sectionEl.style.backgroundColor = 'var(--color-card)';
            sectionEl.dataset.secIdx = sIdx;
            sectionEl.innerHTML = `
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2" style="border-color: var(--color-border) !important;">
                    <div class="d-flex align-items-center gap-2 w-75">
                        <span class="section-drag-handle text-muted" style="cursor: grab; padding: 2px 6px;" title="Drag to reorder section">
                            <i class="fa-solid fa-grip-vertical"></i>
                        </span>
                        <input type="text" class="form-control form-control-sm bg-transparent border-0 text-soft fw-bold w-100 section-title" data-idx="${sIdx}" value="${section.title || 'Νέα Ενότητα'}">
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-danger btn-sm border-0 delete-section" data-idx="${sIdx}"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
                <div class="fields-container p-2 rounded" data-idx="${sIdx}" style="min-height: 50px;">
                    <!-- Fields will render here -->
                </div>
                <div class="text-center mt-2 border-top border-dashed pt-2 small text-muted" style="border-color: var(--color-border) !important;">
                    Σύρετε στοιχεία εδώ ή κάντε κλικ στα αριστερά.
                </div>
            `;

            // Section drag handle behavior
            const secHandle = sectionEl.querySelector('.section-drag-handle');
            secHandle.addEventListener('mousedown', () => {
                sectionEl.setAttribute('draggable', 'true');
            });
            secHandle.addEventListener('mouseup', () => {
                sectionEl.removeAttribute('draggable');
            });

            sectionEl.addEventListener('dragstart', (e) => {
                e.stopPropagation();
                e.dataTransfer.setData('text/plain', JSON.stringify({ type: 'section', sIdx: sIdx }));
                sectionEl.classList.add('opacity-50');
            });

            sectionEl.addEventListener('dragend', (e) => {
                sectionEl.classList.remove('opacity-50');
                sectionEl.removeAttribute('draggable');
            });

            sectionEl.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });

            sectionEl.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const rawData = e.dataTransfer.getData('text/plain');
                if (!rawData) return;
                try {
                    const data = JSON.parse(rawData);
                    if (data.type === 'section') {
                        const fromIdx = data.sIdx;
                        const toIdx = sIdx;
                        if (fromIdx !== toIdx) {
                            const movedSec = schema.sections.splice(fromIdx, 1)[0];
                            schema.sections.splice(toIdx, 0, movedSec);
                            schema.sections.forEach((sec, idx) => { sec.order = idx + 1; });
                            renderCanvas();
                        }
                    }
                } catch (err) {}
            });

            const fieldsContainer = sectionEl.querySelector('.fields-container');
            
            // Allow dropping fields into an empty fields container or cross-section
            fieldsContainer.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                fieldsContainer.style.backgroundColor = 'var(--color-primary-light)';
            });

            fieldsContainer.addEventListener('dragleave', (e) => {
                fieldsContainer.style.backgroundColor = 'transparent';
            });

            fieldsContainer.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                fieldsContainer.style.backgroundColor = 'transparent';
                const rawData = e.dataTransfer.getData('text/plain');
                if (!rawData) return;
                try {
                    const data = JSON.parse(rawData);
                    if (data.type === 'field') {
                        const fromSec = data.sIdx;
                        const fromF = data.fIdx;
                        const toSec = sIdx;
                        if (fromSec === toSec && fieldsContainer.children.length === schema.sections[toSec].fields.length) {
                            // Handled by field element drop
                            return;
                        }
                        const movedField = schema.sections[fromSec].fields.splice(fromF, 1)[0];
                        schema.sections[toSec].fields.push(movedField);
                        renderCanvas();
                    }
                } catch (err) {}
            });

            if (section.fields && section.fields.length > 0) {
                section.fields.forEach((f, fIdx) => {
                    const fEl = document.createElement('div');
                    fEl.className = 'field-element p-2 rounded mb-2 d-flex justify-content-between align-items-center border';
                    fEl.style.backgroundColor = 'var(--color-bg)';
                    fEl.style.borderColor = 'var(--color-border)';
                    fEl.dataset.secIdx = sIdx;
                    fEl.dataset.fIdx = fIdx;

                    let extraInfo = '';
                    if (f.type === 'calculated') {
                        extraInfo = `<div class="small text-warning font-monospace" style="font-size: 10px;">Formula: ${f.formula || 'N/A'}</div>`;
                    }
                    let condBadge = '';
                    if (f.conditional_logic && f.conditional_logic.enabled) {
                        const rulesDesc = (f.conditional_logic.rules || []).map(r => `${r.field} ${r.operator} ${r.value}`).join(', ');
                        condBadge = `<span class="badge bg-warning text-dark ms-2" style="font-size:9px;">Conditional: ${f.conditional_logic.action} if [${rulesDesc}]</span>`;
                    }
                    fEl.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <span class="field-drag-handle text-muted" style="cursor: grab; padding: 2px 4px;" title="Drag to reorder field">
                                <i class="fa-solid fa-grip-vertical"></i>
                            </span>
                            <div>
                                <span class="badge bg-primary me-2">${f.type.toUpperCase()}</span>
                                <span class="text-soft font-semibold">${f.label || 'Χωρίς όνομα'}</span>
                                ${condBadge}
                                <small class="text-muted d-block font-monospace" style="font-size: 10px;">Key: ${f.key}</small>
                                ${extraInfo}
                            </div>
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-outline-info btn-sm border-0 edit-field" data-sec-idx="${sIdx}" data-f-idx="${fIdx}"><i class="fa-solid fa-gear"></i></button>
                            <button class="btn btn-outline-danger btn-sm border-0 delete-field" data-sec-idx="${sIdx}" data-f-idx="${fIdx}"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    `;

                    // Field handle drag behavior
                    const fHandle = fEl.querySelector('.field-drag-handle');
                    fHandle.addEventListener('mousedown', () => {
                        fEl.setAttribute('draggable', 'true');
                    });
                    fHandle.addEventListener('mouseup', () => {
                        fEl.removeAttribute('draggable');
                    });

                    fEl.addEventListener('dragstart', (e) => {
                        e.stopPropagation();
                        e.dataTransfer.setData('text/plain', JSON.stringify({ type: 'field', sIdx: sIdx, fIdx: fIdx }));
                        fEl.classList.add('opacity-50');
                    });

                    fEl.addEventListener('dragend', (e) => {
                        fEl.classList.remove('opacity-50');
                        fEl.removeAttribute('draggable');
                    });

                    fEl.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                    });

                    fEl.addEventListener('drop', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const rawData = e.dataTransfer.getData('text/plain');
                        if (!rawData) return;
                        try {
                            const data = JSON.parse(rawData);
                            if (data.type === 'field') {
                                const fromSec = data.sIdx;
                                const fromF = data.fIdx;
                                const toSec = sIdx;
                                const toF = fIdx;

                                if (fromSec === toSec && fromF === toF) return;

                                const movedField = schema.sections[fromSec].fields.splice(fromF, 1)[0];
                                schema.sections[toSec].fields.splice(toF, 0, movedField);
                                renderCanvas();
                            }
                        } catch (err) {}
                    });

                    fieldsContainer.appendChild(fEl);
                });
            } else {
                fieldsContainer.innerHTML = '<p class="text-muted small text-center my-2">Κενή ενότητα.</p>';
            }

            canvas.appendChild(sectionEl);
        });

        // Add event listeners for section title change
        canvas.querySelectorAll('.section-title').forEach(input => {
            input.addEventListener('change', (e) => {
                const idx = e.target.dataset.idx;
                schema.sections[idx].title = e.target.value;
            });
        });

        // Delete Section listener
        canvas.querySelectorAll('.delete-section').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const idx = e.currentTarget.dataset.idx;
                schema.sections.splice(idx, 1);
                renderCanvas();
            });
        });

        // Delete Field listener
        canvas.querySelectorAll('.delete-field').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const sIdx = e.currentTarget.dataset.secIdx;
                const fIdx = e.currentTarget.dataset.fIdx;
                schema.sections[sIdx].fields.splice(fIdx, 1);
                renderCanvas();
            });
        });

        // Edit Field listener
        canvas.querySelectorAll('.edit-field').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const sIdx = e.currentTarget.dataset.secIdx;
                const fIdx = e.currentTarget.dataset.fIdx;
                openProperties(sIdx, fIdx);
            });
        });
    }

    addSectionBtn.addEventListener('click', () => {
        schema.sections.push({
            id: 'sec_' + Date.now(),
            title: 'Νέα Ενότητα',
            description: '',
            order: schema.sections.length + 1,
            fields: []
        });
        renderCanvas();
    });

    // Handle Palette Add click
    document.querySelectorAll('.palette-item').forEach(btn => {
        btn.addEventListener('click', () => {
            if (schema.sections.length === 0) {
                alert('Παρακαλώ προσθέστε πρώτα μια ενότητα.');
                return;
            }

            const type = btn.dataset.type;
            const targetSectionIdx = schema.sections.length - 1; // Default to last section
            const fieldId = 'field_' + Math.random().toString(36).substr(2, 9);

            const newField = {
                id: fieldId,
                key: 'field_' + Date.now(),
                type: type,
                label: 'Νέο Πεδίο ' + type.toUpperCase(),
                placeholder: '',
                required: false,
                width: 12,
                conditional: null
            };

            // Set specific defaults
            if (type === 'select' || type === 'radio' || type === 'checkbox') {
                newField.dataSource = 'static';
                newField.options = [];
                newField.repositoryId = '';
            }

            schema.sections[targetSectionIdx].fields.push(newField);
            renderCanvas();
            openProperties(targetSectionIdx, schema.sections[targetSectionIdx].fields.length - 1);
        });
    });

    function openProperties(sIdx, fIdx) {
        const field = schema.sections[sIdx].fields[fIdx];
        document.getElementById('propFieldId').value = fIdx;
        document.getElementById('propSectionId').value = sIdx;
        document.getElementById('propKey').value = field.key;
        document.getElementById('propLabel').value = field.label;
        document.getElementById('propPlaceholder').value = field.placeholder || '';
        document.getElementById('propHelp').value = field.helpText || '';
        document.getElementById('propWidth').value = field.width || 12;
        document.getElementById('propRequired').checked = field.required || false;

        // Hide/Show fields by type
        const dsGroup = document.getElementById('dataSourceGroup');
        const fileGroup = document.getElementById('fileGroup');
        const calcGroup = document.getElementById('calculatedGroup');
        const phoneGroup = document.getElementById('phoneGroup');
        const urlGroup = document.getElementById('urlGroup');
        const timeGroup = document.getElementById('timeGroup');
        const ratingGroup = document.getElementById('ratingGroup');
        const rangeGroup = document.getElementById('rangeGroup');
        const consentGroup = document.getElementById('consentGroup');
        const currencyGroup = document.getElementById('currencyGroup');
        const htmlBlockGroup = document.getElementById('htmlBlockGroup');
        const repoAutoGroup = document.getElementById('repoAutoGroup');
        const repoTagsGroup = document.getElementById('repoTagsGroup');

        dsGroup.classList.add('d-none');
        fileGroup.classList.add('d-none');
        calcGroup.classList.add('d-none');
        phoneGroup.classList.add('d-none');
        urlGroup.classList.add('d-none');
        timeGroup.classList.add('d-none');
        ratingGroup.classList.add('d-none');
        rangeGroup.classList.add('d-none');
        consentGroup.classList.add('d-none');
        currencyGroup.classList.add('d-none');
        htmlBlockGroup.classList.add('d-none');
        repoAutoGroup.classList.add('d-none');
        repoTagsGroup.classList.add('d-none');

        // Manage Default Values Group visibility based on type
        const hasDataSrc = ['select', 'radio', 'checkbox'].includes(field.type);
        if (hasDataSrc) {
            document.getElementById('propDefaultValuesGroup').classList.remove('d-none');
            document.getElementById('propDefaultValues').value = Array.isArray(field.default_values) ? field.default_values.join(', ') : '';
        } else {
            document.getElementById('propDefaultValuesGroup').classList.add('d-none');
            document.getElementById('propDefaultValues').value = '';
        }

        if (field.type === 'select' || field.type === 'radio') {
            dsGroup.classList.remove('d-none');
            document.getElementById('propDataSource').value = field.dataSource || 'static';
            
            // Format options
            const optLines = (field.options || []).map(o => {
                let suffix = '';
                if (o.calcValue !== undefined && o.calcValue !== null && o.calcValue !== '') {
                    suffix = ',' + o.calcValue;
                }
                return o.value + ',' + o.label + suffix;
            });
            document.getElementById('propStaticOptions').value = optLines.join('\n');
            document.getElementById('propRepositoryId').value = field.repositoryId || '';

            toggleDataSourceInputs(field.dataSource || 'static');
        } else if (field.type === 'checkbox') {
            // Also support choice calc values for checkbox
            dsGroup.classList.remove('d-none');
            document.getElementById('propDataSource').value = field.dataSource || 'static';
            const optLines = (field.options || []).map(o => {
                let suffix = '';
                if (o.calcValue !== undefined && o.calcValue !== null && o.calcValue !== '') {
                    suffix = ',' + o.calcValue;
                }
                return o.value + ',' + o.label + suffix;
            });
            document.getElementById('propStaticOptions').value = optLines.join('\n');
            document.getElementById('propRepositoryId').value = field.repositoryId || '';
            toggleDataSourceInputs(field.dataSource || 'static');
        } else if (field.type === 'file') {
            fileGroup.classList.remove('d-none');
            document.getElementById('propFileTypes').value = (field.acceptedTypes || []).join(',');
            document.getElementById('propFileSize').value = field.maxSize || 5;
        } else if (field.type === 'calculated') {
            calcGroup.classList.remove('d-none');
            document.getElementById('propFormula').value = field.formula || '';
            document.getElementById('propDecimalPlaces').value = field.decimalPlaces !== undefined ? field.decimalPlaces : 2;
            document.getElementById('propPrefix').value = field.prefix || '';
            document.getElementById('propSuffix').value = field.suffix || '';
            document.getElementById('propReadOnly').checked = field.readOnly !== false;
            document.getElementById('propHidden').checked = !!field.hidden;
        } else if (field.type === 'phone') {
            phoneGroup.classList.remove('d-none');
            document.getElementById('propPhoneFormat').value = field.phoneFormat || 'international';
        } else if (field.type === 'url') {
            urlGroup.classList.remove('d-none');
            document.getElementById('propUrlProtocols').value = (field.urlProtocols || ['http', 'https']).join(',');
        } else if (field.type === 'time') {
            timeGroup.classList.remove('d-none');
            document.getElementById('propTimeFormat').value = field.timeFormat || '24';
            document.getElementById('propTimeInterval').value = field.timeInterval || 15;
        } else if (field.type === 'rating') {
            ratingGroup.classList.remove('d-none');
            document.getElementById('propRatingMax').value = field.ratingMax || 5;
            document.getElementById('propRatingIcon').value = field.ratingIcon || 'star';
        } else if (field.type === 'range') {
            rangeGroup.classList.remove('d-none');
            document.getElementById('propRangeMin').value = field.rangeMin !== undefined ? field.rangeMin : 0;
            document.getElementById('propRangeMax').value = field.rangeMax !== undefined ? field.rangeMax : 100;
            document.getElementById('propRangeStep').value = field.rangeStep !== undefined ? field.rangeStep : 5;
        } else if (field.type === 'consent') {
            consentGroup.classList.remove('d-none');
            document.getElementById('propConsentText').value = field.consentText || '';
        } else if (field.type === 'currency') {
            currencyGroup.classList.remove('d-none');
            document.getElementById('propCurrencyCode').value = field.currencyCode || 'EUR';
        } else if (field.type === 'html') {
            htmlBlockGroup.classList.remove('d-none');
            document.getElementById('propHtmlContent').value = field.htmlContent || '';
        } else if (field.type === 'repeater') {
            document.getElementById('repeaterGroup').classList.remove('d-none');
            document.getElementById('propRepeaterMin').value = field.repeaterMin || 1;
            document.getElementById('propRepeaterMax').value = field.repeaterMax || 10;
        } else if (field.type === 'likert') {
            document.getElementById('likertGroup').classList.remove('d-none');
            document.getElementById('propLikertType').value = field.likertType || 'single';
        } else if (field.type === 'image_choice') {
            document.getElementById('imageChoiceGroup').classList.remove('d-none');
            const lines = (field.imageChoices || []).map(c => `${c.url},${c.label},${c.value}`);
            document.getElementById('propImageChoices').value = lines.join('\n');
        } else if (field.type === 'dynamic_select') {
            document.getElementById('dynamicSelectGroup').classList.remove('d-none');
            document.getElementById('propDynamicSourceId').value = field.dynamicSourceId || '';
        } else if (field.type === 'repository_autocomplete') {
            document.getElementById('repoAutoGroup').classList.remove('d-none');
            document.getElementById('propRepoAutoSourceId').value = field.repoAutoSourceId || '';
            
            updateRepoAutoDropdowns();
            
            document.getElementById('propRepoAutoSearchField').value = field.repoAutoSearchField || 'label';
            document.getElementById('propRepoAutoValueField').value = field.repoAutoValueField || 'label';
            
            renderRepoAutoMappings(field.repoAutoMappings || '');
            
        } else if (field.type === 'repository_tags') {
            document.getElementById('repoTagsGroup').classList.remove('d-none');
            document.getElementById('propRepoTagsSource1').value = field.repoTagsSource1 || '';
            document.getElementById('propRepoTagsSource2').value = field.repoTagsSource2 || '';
            
            updateRepoTagsDropdowns(1);
            updateRepoTagsDropdowns(2);
            
            document.getElementById('propRepoTagsSearch1').value = field.repoTagsSearch1 || 'label';
            if (field.repoTagsSource2) {
                document.getElementById('propRepoTagsSearch2').value = field.repoTagsSearch2 || 'label';
            }
        }

        // Bind Conditional Logic
        const condSection = document.getElementById('conditionalLogicSection');
        const condEnabledChk = document.getElementById('propCondEnabled');
        const condBody = document.getElementById('propCondBody');
        
        // Hide conditional logic settings on layout elements
        if (['section', 'heading', 'divider'].includes(field.type)) {
            condSection.classList.add('d-none');
        } else {
            condSection.classList.remove('d-none');
            const cond = field.conditional_logic || { enabled: false, action: 'show', match: 'all', rules: [] };
            condEnabledChk.checked = !!cond.enabled;
            document.getElementById('propCondAction').value = cond.action || 'show';
            document.getElementById('propCondMatch').value = cond.match || 'all';
            
            if (cond.enabled) {
                condBody.classList.remove('d-none');
            } else {
                condBody.classList.add('d-none');
            }
            renderCondRules(cond.rules || [], field.key);
        }

        // Bind System Prefill configuration properties
        const prefillSection = document.getElementById('systemPrefillSection');
        const prefillEnabled = document.getElementById('propPrefillEnabled');
        const prefillBody = document.getElementById('propPrefillBody');
        const prefillTag = document.getElementById('propPrefillTag');
        const prefillReadonly = document.getElementById('propPrefillReadonly');
        const prefillHidden = document.getElementById('propPrefillHidden');

        // Only show prefill configuration panel for supported fields types
        const supportedTypes = ['text', 'email', 'number', 'hidden', 'select', 'radio', 'date', 'datetime'];
        if (supportedTypes.includes(field.type)) {
            prefillSection.classList.remove('d-none');
            prefillEnabled.checked = !!field.system_prefill_enabled;
            prefillTag.value = field.system_prefill_tag || '';
            prefillReadonly.checked = !!field.system_prefill_readonly;
            prefillHidden.checked = !!field.system_prefill_hidden;
            if (field.system_prefill_enabled) {
                prefillBody.classList.remove('d-none');
            } else {
                prefillBody.classList.add('d-none');
            }
        } else {
            prefillSection.classList.add('d-none');
        }

        propPanel.classList.remove('d-none');
    }

    // Toggle conditional logic section body
    document.getElementById('propCondEnabled').addEventListener('change', (e) => {
        const body = document.getElementById('propCondBody');
        if (e.target.checked) {
            body.classList.remove('d-none');
        } else {
            body.classList.add('d-none');
        }
    });

    // Toggle prefill settings section body
    document.getElementById('propPrefillEnabled').addEventListener('change', (e) => {
        const body = document.getElementById('propPrefillBody');
        if (e.target.checked) {
            body.classList.remove('d-none');
        } else {
            body.classList.add('d-none');
        }
    });

    // Render Conditional Rules list dynamically
    function renderCondRules(rules, currentFieldKey) {
        const container = document.getElementById('propCondRulesList');
        container.innerHTML = '';

        // Build list of eligible source fields
        const sourceFields = [];
        schema.sections.forEach(sec => {
            if (sec.fields) {
                sec.fields.forEach(f => {
                    if (f.key !== currentFieldKey && !['heading', 'divider', 'section'].includes(f.type)) {
                        sourceFields.push(f);
                    }
                });
            }
        });

        if (rules.length === 0) {
            container.innerHTML = '<p class="text-muted small">Δεν έχουν προστεθεί κανόνες.</p>';
        }

        rules.forEach((rule, rIdx) => {
            const ruleDiv = document.createElement('div');
            ruleDiv.className = 'border border-glass rounded p-2 mb-2 rule-item';
            ruleDiv.innerHTML = `
                <div class="mb-1 d-flex justify-content-between align-items-center">
                    <span class="small text-white-50">Κανόνας #${rIdx + 1}</span>
                    <button type="button" class="btn btn-outline-danger btn-xs border-0 remove-rule-btn" data-idx="${rIdx}"><i class="fa-solid fa-times"></i></button>
                </div>
                <div class="mb-1">
                    <select class="form-select form-select-sm rule-source-field" required>
                        <option value="">Επιλέξτε πεδίο...</option>
                        ${sourceFields.map(sf => `<option value="${sf.key}" ${sf.key === rule.field ? 'selected' : ''}>${sf.label} (${sf.key}) [${sf.type}]</option>`).join('')}
                    </select>
                </div>
                <div class="mb-1">
                    <select class="form-select form-select-sm rule-operator" required>
                        <option value="equals" ${rule.operator === 'equals' ? 'selected' : ''}>Ισούται με (equals)</option>
                        <option value="not_equals" ${rule.operator === 'not_equals' ? 'selected' : ''}>Δεν ισούται με (not equals)</option>
                        <option value="contains" ${rule.operator === 'contains' ? 'selected' : ''}>Περιέχει (contains)</option>
                        <option value="not_contains" ${rule.operator === 'not_contains' ? 'selected' : ''}>Δεν περιέχει (not contains)</option>
                        <option value="greater_than" ${rule.operator === 'greater_than' ? 'selected' : ''}>Μεγαλύτερο από (greater than)</option>
                        <option value="greater_than_or_equal" ${rule.operator === 'greater_than_or_equal' ? 'selected' : ''}>Μεγαλύτερο ή ίσο (greater or equal)</option>
                        <option value="less_than" ${rule.operator === 'less_than' ? 'selected' : ''}>Μικρότερο από (less than)</option>
                        <option value="less_than_or_equal" ${rule.operator === 'less_than_or_equal' ? 'selected' : ''}>Μικρότερο ή ίσο (less or equal)</option>
                        <option value="is_empty" ${rule.operator === 'is_empty' ? 'selected' : ''}>Είναι κενό (is empty)</option>
                        <option value="is_not_empty" ${rule.operator === 'is_not_empty' ? 'selected' : ''}>Δεν είναι κενό (is not empty)</option>
                        <option value="is_checked" ${rule.operator === 'is_checked' ? 'selected' : ''}>Είναι επιλεγμένο (is checked)</option>
                        <option value="is_not_checked" ${rule.operator === 'is_not_checked' ? 'selected' : ''}>Δεν είναι επιλεγμένο (is not checked)</option>
                        <option value="starts_with" ${rule.operator === 'starts_with' ? 'selected' : ''}>Ξεκινάει με (starts with)</option>
                        <option value="ends_with" ${rule.operator === 'ends_with' ? 'selected' : ''}>Τελειώνει με (ends with)</option>
                    </select>
                </div>
                <div>
                    <input type="text" class="form-control form-control-sm rule-value" placeholder="Τιμή σύγκρισης" value="${rule.value || ''}">
                </div>
            `;
            container.appendChild(ruleDiv);
        });

        // Bind remove rule buttons
        container.querySelectorAll('.remove-rule-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const idx = parseInt(e.currentTarget.dataset.idx);
                rules.splice(idx, 1);
                renderCondRules(rules, currentFieldKey);
            });
        });
    }

    // Add Rule button action
    document.getElementById('addCondRuleBtn').addEventListener('click', () => {
        const sIdx = document.getElementById('propSectionId').value;
        const fIdx = document.getElementById('propFieldId').value;
        const field = schema.sections[sIdx].fields[fIdx];
        if (!field.conditional_logic) {
            field.conditional_logic = { enabled: true, action: 'show', match: 'all', rules: [] };
        }
        field.conditional_logic.rules = field.conditional_logic.rules || [];
        field.conditional_logic.rules.push({ field: '', operator: 'equals', value: '' });
        renderCondRules(field.conditional_logic.rules, field.key);
    });

    document.getElementById('propDataSource').addEventListener('change', (e) => {
        toggleDataSourceInputs(e.target.value);
    });

    function toggleDataSourceInputs(source) {
        const staticGroup = document.getElementById('propStaticOptionsGroup');
        const repoGroup = document.getElementById('propRepositoryGroup');
        
        staticGroup.classList.add('d-none');
        repoGroup.classList.add('d-none');

        if (source === 'static') {
            staticGroup.classList.remove('d-none');
        } else {
            repoGroup.classList.remove('d-none');
        }
    }

    document.getElementById('applyPropBtn').addEventListener('click', () => {
        const sIdx = document.getElementById('propSectionId').value;
        const fIdx = document.getElementById('propFieldId').value;
        const field = schema.sections[sIdx].fields[fIdx];

        field.key = document.getElementById('propKey').value.trim();
        field.label = document.getElementById('propLabel').value.trim();
        field.placeholder = document.getElementById('propPlaceholder').value.trim();
        field.helpText = document.getElementById('propHelp').value.trim();
        field.width = parseInt(document.getElementById('propWidth').value);
        field.required = document.getElementById('propRequired').checked;

        if (field.type === 'select' || field.type === 'radio' || field.type === 'checkbox') {
            field.dataSource = document.getElementById('propDataSource').value;
            if (field.dataSource === 'static') {
                const lines = document.getElementById('propStaticOptions').value.split('\n');
                field.options = lines.filter(l => l.trim() !== '').map(l => {
                    const pts = l.split(',');
                    return { 
                        value: pts[0].trim(), 
                        label: (pts[1] || pts[0]).trim(),
                        calcValue: pts[2] ? parseFloat(pts[2].trim()) : null
                    };
                });
                field.repositoryId = '';
            } else {
                field.repositoryId = document.getElementById('propRepositoryId').value;
                field.options = [];
            }
        } else if (field.type === 'file') {
            const types = document.getElementById('propFileTypes').value;
            field.acceptedTypes = types.split(',').map(t => t.trim()).filter(t => t !== '');
            field.maxSize = parseInt(document.getElementById('propFileSize').value);
        } else if (field.type === 'calculated') {
            field.formula = document.getElementById('propFormula').value.trim();
            field.decimalPlaces = parseInt(document.getElementById('propDecimalPlaces').value);
            field.prefix = document.getElementById('propPrefix').value.trim();
            field.suffix = document.getElementById('propSuffix').value.trim();
            field.readOnly = document.getElementById('propReadOnly').checked;
            field.hidden = document.getElementById('propHidden').checked;
        } else if (field.type === 'phone') {
            field.phoneFormat = document.getElementById('propPhoneFormat').value;
        } else if (field.type === 'url') {
            field.urlProtocols = document.getElementById('propUrlProtocols').value.split(',').map(p => p.trim());
        } else if (field.type === 'time') {
            field.timeFormat = document.getElementById('propTimeFormat').value;
            field.timeInterval = parseInt(document.getElementById('propTimeInterval').value);
        } else if (field.type === 'rating') {
            field.ratingMax = parseInt(document.getElementById('propRatingMax').value);
            field.ratingIcon = document.getElementById('propRatingIcon').value;
        } else if (field.type === 'range') {
            field.rangeMin = parseFloat(document.getElementById('propRangeMin').value);
            field.rangeMax = parseFloat(document.getElementById('propRangeMax').value);
            field.rangeStep = parseFloat(document.getElementById('propRangeStep').value);
        } else if (field.type === 'consent') {
            field.consentText = document.getElementById('propConsentText').value.trim();
        } else if (field.type === 'currency') {
            field.currencyCode = document.getElementById('propCurrencyCode').value;
        } else if (field.type === 'html') {
            field.htmlContent = document.getElementById('propHtmlContent').value.trim();
        } else if (field.type === 'repeater') {
            field.repeaterMin = parseInt(document.getElementById('propRepeaterMin').value);
            field.repeaterMax = parseInt(document.getElementById('propRepeaterMax').value);
        } else if (field.type === 'likert') {
            field.likertType = document.getElementById('propLikertType').value;
        } else if (field.type === 'image_choice') {
            const lines = document.getElementById('propImageChoices').value.split('\n');
            field.imageChoices = lines.filter(l => l.trim() !== '').map(l => {
                const pts = l.split(',');
                return {
                    url: pts[0].trim(),
                    label: (pts[1] || pts[0]).trim(),
                    value: (pts[2] || pts[0]).trim()
                };
            });
        } else if (field.type === 'dynamic_select') {
            field.dynamicSourceId = document.getElementById('propDynamicSourceId').value;
        } else if (field.type === 'repository_autocomplete') {
            field.repoAutoSourceId = document.getElementById('propRepoAutoSourceId').value;
            field.repoAutoSearchField = document.getElementById('propRepoAutoSearchField').value;
            field.repoAutoValueField = document.getElementById('propRepoAutoValueField').value;
            
            // Collect mappings
            const mappingRows = document.querySelectorAll('.repo-mapping-row');
            let maps = [];
            mappingRows.forEach(r => {
                const rField = r.querySelector('.map-repo-field').value;
                const fField = r.querySelector('.map-form-field').value;
                if (rField && fField) maps.push(`${rField}:${fField}`);
            });
            field.repoAutoMappings = maps.join(',');
            
        } else if (field.type === 'repository_tags') {
            field.repoTagsSource1 = document.getElementById('propRepoTagsSource1').value;
            field.repoTagsSource2 = document.getElementById('propRepoTagsSource2').value;
            field.repoTagsSearch1 = document.getElementById('propRepoTagsSearch1').value;
            field.repoTagsSearch2 = document.getElementById('propRepoTagsSearch2').value;
        }

        // Apply Conditional Logic
        if (!['section', 'heading', 'divider'].includes(field.type)) {
            const condEnabled = document.getElementById('propCondEnabled').checked;
            if (condEnabled) {
                const rules = [];
                const ruleItems = document.querySelectorAll('.rule-item');
                ruleItems.forEach(item => {
                    const fieldVal = item.querySelector('.rule-source-field').value;
                    const opVal = item.querySelector('.rule-operator').value;
                    const cVal = item.querySelector('.rule-value').value;
                    if (fieldVal) {
                        rules.push({ field: fieldVal, operator: opVal, value: cVal });
                    }
                });
                field.conditional_logic = {
                    enabled: true,
                    action: document.getElementById('propCondAction').value,
                    match: document.getElementById('propCondMatch').value,
                    rules: rules
                };
            } else {
                field.conditional_logic = { enabled: false, action: 'show', match: 'all', rules: [] };
            }
        }

        // Apply System Prefill configuration properties
        const supportedTypes = ['text', 'email', 'number', 'hidden', 'select', 'radio', 'date', 'datetime'];
        if (supportedTypes.includes(field.type)) {
            field.system_prefill_enabled = document.getElementById('propPrefillEnabled').checked;
            field.system_prefill_tag = document.getElementById('propPrefillTag').value;
            field.system_prefill_readonly = document.getElementById('propPrefillReadonly').checked;
            field.system_prefill_hidden = document.getElementById('propPrefillHidden').checked;
        }

        // Save default_values configurations
        const hasDataSrc = ['select', 'radio', 'checkbox'].includes(field.type);
        if (hasDataSrc) {
            const defValRaw = document.getElementById('propDefaultValues').value.trim();
            field.default_values = defValRaw ? defValRaw.split(',').map(s => s.trim()).filter(s => s !== '') : [];
        }

        propPanel.classList.add('d-none');
        renderCanvas();
    });

    saveBtn.addEventListener('click', async () => {
        const alertBox = document.getElementById('schemaAlert');
        alertBox.classList.add('d-none');

        try {
            const response = await fetch('/admin/forms/<?= $form['id'] ?>/builder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ schema_json: JSON.stringify(schema) })
            });

            const res = await response.json();
            if (res.success) {
                alert('Το σχέδιο αποθηκεύτηκε επιτυχώς!');
                window.location.href = '/admin/forms';
            } else {
                alertBox.textContent = res.error || 'Σφάλμα κατά την αποθήκευση.';
                alertBox.classList.remove('d-none');
            }
        } catch(e) {
            alertBox.textContent = 'Σφάλμα σύνδεσης με τον διακομιστή.';
            alertBox.classList.remove('d-none');
        }
    });

    renderCanvas();

    // Dynamically expand container area
    const workspace = document.querySelector('.workspace-area');
    if (workspace) {
        workspace.style.maxWidth = '100%';
        workspace.style.paddingLeft = '20px';
        workspace.style.paddingRight = '20px';
    }

    const toggleBtn = document.getElementById('togglePropertiesBtn');
    toggleBtn.addEventListener('click', () => {
        const propPanel = document.getElementById('propertiesPanel');
        const canvasCol = document.getElementById('canvasSections').closest('.col-xl-6');
        if (propPanel.classList.contains('d-none')) {
            propPanel.classList.remove('d-none');
            if (canvasCol) {
                canvasCol.className = 'col-xl-6 col-lg-8 col-md-12';
            }
        } else {
            propPanel.classList.add('d-none');
            if (canvasCol) {
                canvasCol.className = 'col-xl-9 col-lg-8 col-md-12';
            }
        }
    });

    // Helper functions for Repository Auto/Tags fields
    window.updateRepoAutoDropdowns = function() {
        const select = document.getElementById('propRepoAutoSourceId');
        const selected = select.options[select.selectedIndex];
        if (!selected || !selected.value) return;

        const colsStr = selected.getAttribute('data-columns');
        const cols = colsStr ? JSON.parse(colsStr) : [];
        const allCols = [{key: 'label', label: 'Display Label (label)'}, {key: 'value', label: 'Machine Key (value)'}, ...cols];
        
        const searchSelect = document.getElementById('propRepoAutoSearchField');
        const valueSelect = document.getElementById('propRepoAutoValueField');
        
        const currentSearch = searchSelect.value;
        const currentValue = valueSelect.value;
        
        searchSelect.innerHTML = '';
        valueSelect.innerHTML = '';
        
        allCols.forEach(c => {
            searchSelect.innerHTML += `<option value="${c.key}">${c.label} (${c.key})</option>`;
            valueSelect.innerHTML += `<option value="${c.key}">${c.label} (${c.key})</option>`;
        });
        
        if (currentSearch) searchSelect.value = currentSearch;
        if (currentValue) valueSelect.value = currentValue;
    };

    window.updateRepoTagsDropdowns = function(index) {
        const select = document.getElementById(`propRepoTagsSource${index}`);
        const selected = select.options[select.selectedIndex];
        if (!selected || !selected.value) return;

        const colsStr = selected.getAttribute('data-columns');
        const cols = colsStr ? JSON.parse(colsStr) : [];
        const allCols = [{key: 'label', label: 'Display Label (label)'}, {key: 'value', label: 'Machine Key (value)'}, ...cols];
        
        const searchSelect = document.getElementById(`propRepoTagsSearch${index}`);
        const currentSearch = searchSelect.value;
        
        searchSelect.innerHTML = '';
        
        allCols.forEach(c => {
            searchSelect.innerHTML += `<option value="${c.key}">${c.label} (${c.key})</option>`;
        });
        
        if (currentSearch) searchSelect.value = currentSearch;
    };

    window.renderRepoAutoMappings = function(mapsStr) {
        const container = document.getElementById('propRepoAutoMappingsContainer');
        container.innerHTML = '';
        if (!mapsStr) return;
        const maps = mapsStr.split(',');
        maps.forEach(m => {
            const parts = m.split(':');
            if (parts.length === 2) {
                addRepoAutoMapping(parts[0], parts[1]);
            }
        });
    };

    window.addRepoAutoMapping = function(rVal = '', fVal = '') {
        const select = document.getElementById('propRepoAutoSourceId');
        const selected = select.options[select.selectedIndex];
        let allCols = [{key: 'label', label: 'label'}, {key: 'value', label: 'value'}];
        if (selected && selected.value) {
            const colsStr = selected.getAttribute('data-columns');
            const cols = colsStr ? JSON.parse(colsStr) : [];
            allCols = [...allCols, ...cols];
        }

        const container = document.getElementById('propRepoAutoMappingsContainer');
        const row = document.createElement('div');
        row.className = 'repo-mapping-row d-flex gap-1 align-items-center bg-dark bg-opacity-25 p-1 rounded';
        
        let repoSelectOpts = '';
        allCols.forEach(c => {
            const sel = (c.key === rVal) ? 'selected' : '';
            repoSelectOpts += `<option value="${c.key}" ${sel}>${c.label}</option>`;
        });
        
        // Build form fields dropdown
        let formFieldsOpts = '<option value="">Επιλέξτε...</option>';
        schema.sections.forEach(sec => {
            if (sec.fields) {
                sec.fields.forEach(f => {
                    if (['text', 'email', 'phone', 'number', 'date', 'hidden'].includes(f.type)) {
                        const sel = (f.key === fVal) ? 'selected' : '';
                        formFieldsOpts += `<option value="${f.key}" ${sel}>${f.label}</option>`;
                    }
                });
            }
        });

        row.innerHTML = `
            <select class="form-select form-select-sm map-repo-field" style="width: 45%;">
                ${repoSelectOpts}
            </select>
            <i class="fa-solid fa-arrow-right mx-1 text-muted" style="font-size: 10px;"></i>
            <select class="form-select form-select-sm map-form-field" style="width: 45%;">
                ${formFieldsOpts}
            </select>
            <button type="button" class="btn btn-outline-danger btn-sm px-2 py-0" onclick="this.parentElement.remove()"><i class="fa-solid fa-times"></i></button>
        `;
        container.appendChild(row);
    };

});
</script>
