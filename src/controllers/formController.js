const db = require('../config/db');

exports.getAllForms = async (req, res) => {
  try {
    const forms = await db.allAsync('SELECT * FROM forms ORDER BY created_at DESC');
    res.json(forms.map(f => ({
      ...f,
      schema: JSON.parse(f.schema_json)
    })));
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.getFormById = async (req, res) => {
  const { id } = req.params;
  try {
    const form = await db.getAsync('SELECT * FROM forms WHERE id = ?', [id]);
    if (!form) {
      return res.status(404).json({ error: 'Form not found' });
    }
    res.json({
      ...form,
      schema: JSON.parse(form.schema_json)
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.createForm = async (req, res) => {
  const { title, description, schema } = req.body;
  if (!title || !schema) {
    return res.status(400).json({ error: 'Title and schema are required' });
  }

  try {
    const result = await db.runAsync(
      'INSERT INTO forms (title, description, schema_json) VALUES (?, ?, ?)',
      [title, description || '', JSON.stringify(schema)]
    );
    res.status(201).json({ message: 'Form created successfully', id: result.lastID });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.updateForm = async (req, res) => {
  const { id } = req.params;
  const { title, description, schema } = req.body;

  if (!title || !schema) {
    return res.status(400).json({ error: 'Title and schema are required' });
  }

  try {
    await db.runAsync(
      'UPDATE forms SET title = ?, description = ?, schema_json = ? WHERE id = ?',
      [title, description || '', JSON.stringify(schema), id]
    );
    res.json({ message: 'Form updated successfully' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.deleteForm = async (req, res) => {
  const { id } = req.params;
  try {
    await db.runAsync('DELETE FROM forms WHERE id = ?', [id]);
    res.json({ message: 'Form deleted successfully' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};
