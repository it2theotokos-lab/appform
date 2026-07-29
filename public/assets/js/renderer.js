class FormRenderer {
  constructor(containerId, schema, onSubmitCallback) {
    this.container = document.getElementById(containerId);
    this.schema = schema;
    this.onSubmit = onSubmitCallback;
    this.repositories = {};
  }

  async init() {
    this.container.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
    await this.loadRepositories();
    this.render();
  }

  async loadRepositories() {
    // Collect all fields that require dynamic repositories
    const repoFields = this.schema.fields.filter(f => f.dataSource === 'repository' && f.repositoryId);
    
    for (const field of repoFields) {
      try {
        const response = await fetch(`/api/repositories/${field.repositoryId}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        });
        if (response.ok) {
          const repo = await response.json();
          this.repositories[field.repositoryId] = repo.data;
        }
      } catch (err) {
        console.error(`Failed to load repository ${field.repositoryId}:`, err);
      }
    }
  }

  render() {
    this.container.innerHTML = '';
    
    const formEl = document.createElement('form');
    formEl.id = `dynamicForm_${Date.now()}`;
    formEl.className = 'glass-panel p-4';
    
    const titleEl = document.createElement('h3');
    titleEl.className = 'font-heading mb-2 text-white';
    titleEl.textContent = this.schema.title || 'Dynamic Form';
    formEl.appendChild(titleEl);

    if (this.schema.description) {
      const descEl = document.createElement('p');
      descEl.className = 'text-muted mb-4';
      descEl.textContent = this.schema.description;
      formEl.appendChild(descEl);
    }

    // Render Fields
    this.schema.fields.forEach(field => {
      const formGroup = document.createElement('div');
      formGroup.className = 'mb-3';

      const label = document.createElement('label');
      label.className = 'form-label';
      label.textContent = field.label;
      if (field.required) {
        const reqStar = document.createElement('span');
        reqStar.className = 'text-danger ms-1';
        reqStar.textContent = '*';
        label.appendChild(reqStar);
      }
      formGroup.appendChild(label);

      let inputEl;

      switch (field.type) {
        case 'text':
        case 'number':
        case 'date':
          inputEl = document.createElement('input');
          inputEl.type = field.type;
          inputEl.className = 'form-control';
          inputEl.name = field.id;
          inputEl.placeholder = field.placeholder || '';
          if (field.required) inputEl.required = true;
          formGroup.appendChild(inputEl);
          break;

        case 'dropdown':
          inputEl = document.createElement('select');
          inputEl.className = 'form-select';
          inputEl.name = field.id;
          if (field.required) inputEl.required = true;

          // Default option
          const defaultOpt = document.createElement('option');
          defaultOpt.value = '';
          defaultOpt.textContent = field.placeholder || 'Select an option';
          inputEl.appendChild(defaultOpt);

          // Populate options
          if (field.dataSource === 'static') {
            (field.options || []).forEach(opt => {
              const o = document.createElement('option');
              o.value = opt;
              o.textContent = opt;
              inputEl.appendChild(o);
            });
          } else if (field.dataSource === 'repository' && field.repositoryId) {
            const repoData = this.repositories[field.repositoryId] || [];
            repoData.forEach(item => {
              const o = document.createElement('option');
              o.value = item.name || item.id;
              o.textContent = item.name;
              inputEl.appendChild(o);
            });
          }
          formGroup.appendChild(inputEl);
          break;

        case 'radio':
          const radioContainer = document.createElement('div');
          radioContainer.className = 'd-flex flex-wrap gap-3 mt-1';
          
          (field.options || []).forEach((opt, idx) => {
            const wrap = document.createElement('div');
            wrap.className = 'form-check';

            const radio = document.createElement('input');
            radio.type = 'radio';
            radio.className = 'form-check-input';
            radio.name = field.id;
            radio.id = `${field.id}_${idx}`;
            radio.value = opt;
            if (field.required && idx === 0) radio.required = true;

            const rLabel = document.createElement('label');
            rLabel.className = 'form-check-label text-muted';
            rLabel.htmlFor = radio.id;
            rLabel.textContent = opt;

            wrap.appendChild(radio);
            wrap.appendChild(rLabel);
            radioContainer.appendChild(wrap);
          });
          formGroup.appendChild(radioContainer);
          break;

        case 'file':
          inputEl = document.createElement('input');
          inputEl.type = 'file';
          inputEl.className = 'form-control';
          inputEl.name = field.id;
          if (field.required) inputEl.required = true;
          formGroup.appendChild(inputEl);
          break;

        case 'repository_autocomplete':
          inputEl = document.createElement('input');
          inputEl.type = 'text';
          inputEl.className = 'form-control';
          inputEl.name = field.id;
          inputEl.placeholder = field.placeholder || 'Αναζήτηση...';
          if (field.required) inputEl.required = true;
          
          inputEl.dataset.repoId = field.repoAutoSourceId;
          inputEl.dataset.searchField = field.repoAutoSearchField;
          inputEl.dataset.valueField = field.repoAutoValueField;
          inputEl.dataset.mappings = field.repoAutoMappings;
          
          formGroup.appendChild(inputEl);
          
          setTimeout(() => {
              this.initAutocomplete(inputEl);
          }, 0);
          break;

        case 'repository_tags':
          inputEl = document.createElement('input');
          inputEl.type = 'text';
          inputEl.className = 'form-control';
          inputEl.name = field.id;
          inputEl.placeholder = field.placeholder || 'Επιλέξτε tags...';
          if (field.required) inputEl.required = true;
          
          inputEl.dataset.repo1 = field.repoTagsSource1;
          inputEl.dataset.repo2 = field.repoTagsSource2;
          inputEl.dataset.search1 = field.repoTagsSearch1 || 'label';
          inputEl.dataset.search2 = field.repoTagsSearch2 || 'label';
          inputEl.dataset.valueField = field.repoTagsValueField;
          
          formGroup.appendChild(inputEl);
          
          setTimeout(() => {
              this.initTagify(inputEl);
          }, 0);
          break;

        default:
          break;
      }

      formEl.appendChild(formGroup);
    });

    // Action buttons
    const actionsGroup = document.createElement('div');
    actionsGroup.className = 'd-flex gap-3 mt-4';

    const submitBtn = document.createElement('button');
    submitBtn.type = 'submit';
    submitBtn.className = 'btn btn-premium';
    submitBtn.innerHTML = 'Submit Response <i class="fa-solid fa-paper-plane ms-2"></i>';
    actionsGroup.appendChild(submitBtn);

    const draftBtn = document.createElement('button');
    draftBtn.type = 'button';
    draftBtn.className = 'btn btn-outline-secondary';
    draftBtn.innerHTML = 'Save Draft <i class="fa-solid fa-floppy-disk ms-2"></i>';
    draftBtn.addEventListener('click', () => this.handleFormSubmit(formEl, 'draft'));
    actionsGroup.appendChild(draftBtn);

    formEl.appendChild(actionsGroup);

    formEl.addEventListener('submit', (e) => {
      e.preventDefault();
      this.handleFormSubmit(formEl, 'submitted');
    });

    this.container.appendChild(formEl);
  }

  findFieldByKey(key) {
    if (!this.schema || !this.schema.sections) return null;
    for (const sec of this.schema.sections) {
      if (sec.fields) {
        const found = sec.fields.find(f => f.key === key);
        if (found) return found;
      }
    }
    return null;
  }

  initAutocomplete(inputEl) {
    if (typeof Awesomplete === 'undefined') {
      console.warn('Awesomplete is not loaded');
      return;
    }

    const repoId = inputEl.dataset.repoId;
    const searchField = inputEl.dataset.searchField;
    const valueField = inputEl.dataset.valueField;
    const mappings = inputEl.dataset.mappings;

    if (!repoId || !searchField) return;

    const awesomplete = new Awesomplete(inputEl, {
      minChars: 2,
      maxItems: 15,
      autoFirst: true
    });

    let currentItemData = {};

    inputEl.addEventListener('input', async (e) => {
      const val = e.target.value;
      if (val.length < 2) return;

      try {
        const res = await fetch(`/api/repositories/autocomplete?repo_id=${repoId}&search_field=${searchField}&query=${encodeURIComponent(val)}`, {
          headers: {
             'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        });
        if (res.ok) {
           const data = await res.json();
           const list = data.map(item => {
               return {
                  label: item[searchField],
                  value: item[valueField] || item[searchField],
                  originalData: item
               };
           });
           currentItemData = {};
           list.forEach(i => {
               currentItemData[i.value] = i.originalData;
           });
           awesomplete.list = list;
        }
      } catch (err) {
         console.error('Autocomplete fetch failed', err);
      }
    });

    inputEl.addEventListener('awesomplete-selectcomplete', (e) => {
      const selectedValue = e.text.value;
      const data = currentItemData[selectedValue];
      
      if (data && mappings) {
         const mapPairs = mappings.split(',').map(m => m.trim()).filter(m => m !== '');
         const formEl = inputEl.closest('form');
         if (formEl) {
            mapPairs.forEach(pair => {
                const parts = pair.split(':');
                if (parts.length === 2) {
                    const rField = parts[0].trim();
                    const fField = parts[1].trim();
                    const fieldDef = this.findFieldByKey(fField);
                    if (fieldDef && data[rField] !== undefined) {
                        const targetInput = formEl.querySelector(`[name="${fieldDef.id}"]`);
                        if (targetInput) {
                            targetInput.value = data[rField];
                        }
                    }
                }
            });
         }
      }
    });
  }

  async initTagify(inputEl) {
    if (typeof Tagify === 'undefined') {
      console.warn('Tagify is not loaded');
      return;
    }

    const repo1Id = inputEl.dataset.repo1;
    const repo2Id = inputEl.dataset.repo2 || '';
    const search1 = inputEl.dataset.search1 || 'label';
    const search2 = inputEl.dataset.search2 || 'label';

    if (!repo1Id) return;

    try {
        const res = await fetch(`/api/repositories/tags?repo1=${repo1Id}&repo2=${repo2Id}&search1=${search1}&search2=${search2}`, {
            headers: {
               'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        let whitelist = [];
        if (res.ok) {
            whitelist = await res.json();
        }

        new Tagify(inputEl, {
            whitelist: whitelist,
            dropdown: {
                maxItems: 20,
                classname: "tags-look",
                enabled: 0,
                closeOnSelect: false
            }
        });
    } catch (err) {
        console.error('Tagify initialization failed', err);
    }
  }

  async handleFormSubmit(formEl, status) {
    const formData = new FormData(formEl);
    const answers = {};
    
    // We also support files. To keep it simple, we convert files to base64 or string value.
    for (const [key, value] of formData.entries()) {
      if (value instanceof File && value.name) {
        answers[key] = `File: ${value.name}`;
      } else {
        answers[key] = value;
      }
    }

    if (this.onSubmit) {
      this.onSubmit(answers, status);
    }
  }
}
window.FormRenderer = FormRenderer;
