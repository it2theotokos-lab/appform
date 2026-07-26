class FormBuilder {
  constructor(containerId) {
    this.container = document.getElementById(containerId);
    this.fields = [];
    this.repositories = [];
  }

  async init(formId = null) {
    this.container.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
    await this.fetchRepositories();
    if (formId) {
      await this.loadForm(formId);
    }
    this.render();
  }

  async fetchRepositories() {
    try {
      const response = await fetch('/api/repositories', {
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
      });
      if (response.ok) {
        this.repositories = await response.json();
      }
    } catch (err) {
      console.error('Failed to load repositories', err);
    }
  }

  async loadForm(formId) {
    try {
      const response = await fetch(`/api/forms/${formId}`, {
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
      });
      if (response.ok) {
        const form = await response.json();
        this.formId = form.id;
        this.formTitle = form.title;
        this.formDescription = form.description;
        this.fields = form.schema.fields || [];
      }
    } catch (err) {
      console.error('Failed to load form details', err);
    }
  }

  render() {
    this.container.innerHTML = `
      <div class="row g-4">
        <!-- Controls Column -->
        <div class="col-md-4">
          <div class="glass-panel p-4 mb-4">
            <h5 class="font-heading mb-3 text-white">Add Elements</h5>
            <div class="d-flex flex-column gap-2">
              <button class="btn btn-outline-primary text-start" onclick="builder.addField('text')">
                <i class="fa-solid fa-font me-2"></i> Text Input
              </button>
              <button class="btn btn-outline-primary text-start" onclick="builder.addField('number')">
                <i class="fa-solid fa-hashtag me-2"></i> Number Input
              </button>
              <button class="btn btn-outline-primary text-start" onclick="builder.addField('date')">
                <i class="fa-solid fa-calendar me-2"></i> Date Picker
              </button>
              <button class="btn btn-outline-primary text-start" onclick="builder.addField('dropdown')">
                <i class="fa-solid fa-square-caret-down me-2"></i> Dropdown Select
              </button>
              <button class="btn btn-outline-primary text-start" onclick="builder.addField('radio')">
                <i class="fa-solid fa-circle-dot me-2"></i> Radio Option
              </button>
              <button class="btn btn-outline-primary text-start" onclick="builder.addField('file')">
                <i class="fa-solid fa-file-arrow-up me-2"></i> File Upload
              </button>
            </div>
          </div>
        </div>

        <!-- Canvas Column -->
        <div class="col-md-8">
          <div class="glass-panel p-4 mb-4">
            <div class="mb-3">
              <label class="form-label">Form Title</label>
              <input type="text" class="form-control form-control-lg" id="formTitleInput" value="${this.formTitle || 'New Custom Form'}" placeholder="Enter form title..." required>
            </div>
            <div class="mb-4">
              <label class="form-label">Form Description</label>
              <textarea class="form-control" id="formDescInput" rows="2" placeholder="Brief description of this form...">${this.formDescription || ''}</textarea>
            </div>

            <h5 class="font-heading mb-3 text-white">Form Fields</h5>
            <div id="canvasArea" class="builder-canvas">
              <!-- Fields will render here -->
              <p class="text-muted text-center py-5 m-0" id="emptyMessage">No fields added yet. Click elements on the left to build your form!</p>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-4">
              <button class="btn btn-outline-secondary" onclick="app.navigate('/dashboard')">Cancel</button>
              <button class="btn btn-premium" onclick="builder.saveForm()">Save Form Structure <i class="fa-solid fa-save ms-2"></i></button>
            </div>
          </div>
        </div>
      </div>
    `;

    this.renderCanvasFields();
  }

  addField(type) {
    const fieldId = `field_${Date.now()}`;
    const newField = {
      id: fieldId,
      type: type,
      label: `New ${type.toUpperCase()} Field`,
      placeholder: `Enter ${type}...`,
      required: false,
      dataSource: 'static',
      options: ['Option 1', 'Option 2'],
      repositoryId: ''
    };
    this.fields.push(newField);
    this.renderCanvasFields();
  }

  removeField(id) {
    this.fields = this.fields.filter(f => f.id !== id);
    this.renderCanvasFields();
  }

  updateFieldProp(id, prop, value) {
    const field = this.fields.find(f => f.id === id);
    if (field) {
      if (prop === 'options') {
        field[prop] = value.split(',').map(s => s.trim());
      } else {
        field[prop] = value;
      }
    }
  }

  renderCanvasFields() {
    const canvas = document.getElementById('canvasArea');
    const emptyMsg = document.getElementById('emptyMessage');
    
    if (this.fields.length === 0) {
      emptyMsg.classList.remove('d-none');
      return;
    }
    emptyMsg.classList.add('d-none');

    // Retain input states of other fields before drawing
    canvas.querySelectorAll('.field-element-item').forEach(item => {
      // Save user edits directly on loss of focus instead of re-reading here.
    });

    const listHtml = this.fields.map((field, index) => {
      let extraConfig = '';

      if (field.type === 'dropdown') {
        extraConfig = `
          <div class="row g-2 mt-2">
            <div class="col-md-6">
              <label class="form-label small text-muted">Data Source</label>
              <select class="form-select form-select-sm" onchange="builder.updateFieldProp('${field.id}', 'dataSource', this.value); builder.renderCanvasFields();">
                <option value="static" ${field.dataSource === 'static' ? 'selected' : ''}>Static Options</option>
                <option value="repository" ${field.dataSource === 'repository' ? 'selected' : ''}>External Repository</option>
              </select>
            </div>
            ${field.dataSource === 'static' ? `
              <div class="col-md-6">
                <label class="form-label small text-muted">Options (comma separated)</label>
                <input type="text" class="form-control form-control-sm" value="${(field.options || []).join(', ')}" onchange="builder.updateFieldProp('${field.id}', 'options', this.value)">
              </div>
            ` : `
              <div class="col-md-6">
                <label class="form-label small text-muted">Select Repository</label>
                <select class="form-select form-select-sm" onchange="builder.updateFieldProp('${field.id}', 'repositoryId', this.value)">
                  <option value="">Select source...</option>
                  ${this.repositories.map(r => `<option value="${r.id}" ${field.repositoryId == r.id ? 'selected' : ''}>${r.name}</option>`).join('')}
                </select>
              </div>
            `}
          </div>
        `;
      } else if (field.type === 'radio') {
        extraConfig = `
          <div class="mt-2">
            <label class="form-label small text-muted">Options (comma separated)</label>
            <input type="text" class="form-control form-control-sm" value="${(field.options || []).join(', ')}" onchange="builder.updateFieldProp('${field.id}', 'options', this.value)">
          </div>
        `;
      }

      return `
        <div class="field-element-item border border-glass bg-dark bg-opacity-25 rounded-3 p-3 mb-3" data-id="${field.id}">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="badge bg-secondary font-heading">${field.type.toUpperCase()}</span>
            <button class="btn btn-outline-danger btn-sm border-0" onclick="builder.removeField('${field.id}')">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
          
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label small text-muted">Field Label</label>
              <input type="text" class="form-control form-control-sm" value="${field.label}" onchange="builder.updateFieldProp('${field.id}', 'label', this.value)">
            </div>
            <div class="col-md-4">
              <label class="form-label small text-muted">Placeholder</label>
              <input type="text" class="form-control form-control-sm" value="${field.placeholder || ''}" onchange="builder.updateFieldProp('${field.id}', 'placeholder', this.value)">
            </div>
            <div class="col-md-2 d-flex align-items-end justify-content-center pb-2">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="req_${field.id}" ${field.required ? 'checked' : ''} onchange="builder.updateFieldProp('${field.id}', 'required', this.checked)">
                <label class="form-check-label small text-muted" for="req_${field.id}">Required</label>
              </div>
            </div>
          </div>
          ${extraConfig}
        </div>
      `;
    }).join('');

    canvas.innerHTML = listHtml;
  }

  async saveForm() {
    const title = document.getElementById('formTitleInput').value.trim();
    const description = document.getElementById('formDescInput').value.trim();

    if (!title) {
      alert('Form title is required');
      return;
    }

    const payload = {
      title,
      description,
      schema: {
        title,
        description,
        fields: this.fields
      }
    };

    const url = this.formId ? `/api/forms/${this.formId}` : '/api/forms';
    const method = this.formId ? 'PUT' : 'POST';

    try {
      const response = await fetch(url, {
        method,
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify(payload)
      });

      if (response.ok) {
        alert('Form saved successfully!');
        app.navigate('/dashboard');
      } else {
        const data = await response.json();
        alert(data.error || 'Failed to save form');
      }
    } catch (err) {
      console.error(err);
      alert('Error connecting to the server');
    }
  }
}
window.FormBuilder = FormBuilder;
