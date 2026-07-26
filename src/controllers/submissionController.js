const db = require('../config/db');

exports.getSubmissions = async (req, res) => {
  const { role, id: userId } = req.user;
  const { formId } = req.query;

  try {
    let query = `
      SELECT s.*, f.title as form_title, u.username 
      FROM form_submissions s 
      JOIN forms f ON s.form_id = f.id 
      JOIN users u ON s.user_id = u.id
    `;
    const params = [];

    if (role === 'User') {
      query += ' WHERE s.user_id = ?';
      params.push(userId);
      if (formId) {
        query += ' AND s.form_id = ?';
        params.push(formId);
      }
    } else if (formId) {
      query += ' WHERE s.form_id = ?';
      params.push(formId);
    }

    query += ' ORDER BY s.created_at DESC';

    const submissions = await db.allAsync(query, params);
    res.json(submissions.map(s => ({
      ...s,
      data: JSON.parse(s.data_json)
    })));
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.getSubmissionById = async (req, res) => {
  const { id } = req.params;
  const { role, id: userId } = req.user;

  try {
    const submission = await db.getAsync(
      `SELECT s.*, f.title as form_title, f.schema_json, u.username 
       FROM form_submissions s 
       JOIN forms f ON s.form_id = f.id 
       JOIN users u ON s.user_id = u.id
       WHERE s.id = ?`,
      [id]
    );

    if (!submission) {
      return res.status(404).json({ error: 'Submission not found' });
    }

    // Authorization check
    if (role === 'User' && submission.user_id !== userId) {
      return res.status(403).json({ error: 'Access denied' });
    }

    res.json({
      ...submission,
      data: JSON.parse(submission.data_json),
      form_schema: JSON.parse(submission.schema_json)
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.createSubmission = async (req, res) => {
  const { form_id, data, status } = req.body;
  const userId = req.user.id;

  if (!form_id || !data) {
    return res.status(400).json({ error: 'Form ID and response data are required' });
  }

  try {
    const result = await db.runAsync(
      'INSERT INTO form_submissions (form_id, user_id, data_json, status) VALUES (?, ?, ?, ?)',
      [form_id, userId, JSON.stringify(data), status || 'submitted']
    );
    res.status(201).json({ message: 'Submission saved successfully', id: result.lastID });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.updateSubmission = async (req, res) => {
  const { id } = req.params;
  const { data, status } = req.body;
  const { role, id: userId } = req.user;

  try {
    const submission = await db.getAsync('SELECT * FROM form_submissions WHERE id = ?', [id]);
    if (!submission) {
      return res.status(404).json({ error: 'Submission not found' });
    }

    if (role === 'User' && submission.user_id !== userId) {
      return res.status(403).json({ error: 'Access denied' });
    }

    await db.runAsync(
      'UPDATE form_submissions SET data_json = ?, status = ? WHERE id = ?',
      [JSON.stringify(data), status || 'submitted', id]
    );

    res.json({ message: 'Submission updated successfully' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.deleteSubmission = async (req, res) => {
  const { id } = req.params;
  const { role } = req.user;

  if (role !== 'Admin') {
    return res.status(403).json({ error: 'Access denied. Admin only.' });
  }

  try {
    await db.runAsync('DELETE FROM form_submissions WHERE id = ?', [id]);
    res.json({ message: 'Submission deleted successfully' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};
