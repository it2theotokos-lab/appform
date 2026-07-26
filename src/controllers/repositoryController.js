const db = require('../config/db');

exports.getAllRepositories = async (req, res) => {
  try {
    const repos = await db.allAsync('SELECT * FROM repositories ORDER BY name ASC');
    res.json(repos.map(r => ({
      ...r,
      data: JSON.parse(r.data_json)
    })));
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.getRepositoryById = async (req, res) => {
  const { id } = req.params;
  try {
    const repo = await db.getAsync('SELECT * FROM repositories WHERE id = ?', [id]);
    if (!repo) {
      return res.status(404).json({ error: 'Repository not found' });
    }
    res.json({
      ...repo,
      data: JSON.parse(repo.data_json)
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.createRepository = async (req, res) => {
  const { name, data } = req.body;
  if (!name || !data) {
    return res.status(400).json({ error: 'Repository name and data are required' });
  }

  try {
    const result = await db.runAsync(
      'INSERT INTO repositories (name, data_json) VALUES (?, ?)',
      [name, JSON.stringify(data)]
    );
    res.status(201).json({ message: 'Repository created successfully', id: result.lastID });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};
