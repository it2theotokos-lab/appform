const express = require('express');
const path = require('path');
const db = require('./config/db');

// Controllers
const authController = require('./controllers/authController');
const formController = require('./controllers/formController');
const submissionController = require('./controllers/submissionController');
const menuController = require('./controllers/menuController');
const repositoryController = require('./controllers/repositoryController');

// Middlewares
const { authenticateToken, requireRole } = require('./middleware/auth');

const app = express();
const PORT = process.env.PORT || 3000;

// Simple custom cookie parser middleware
app.use((req, res, next) => {
  req.cookies = {};
  const cookieHeader = req.headers.cookie;
  if (cookieHeader) {
    cookieHeader.split(';').forEach(cookie => {
      const parts = cookie.split('=');
      req.cookies[parts[0].trim()] = decodeURIComponent(parts[1] || '');
    });
  }
  next();
});

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve Static Frontend Assets
app.use(express.static(path.join(__dirname, '../public')));

// AUTH API
app.post('/api/auth/login', authController.login);
app.post('/api/auth/logout', authController.logout);
app.get('/api/auth/me', authenticateToken, authController.getCurrentUser);

// FORMS API
app.get('/api/forms', authenticateToken, formController.getAllForms);
app.get('/api/forms/:id', authenticateToken, formController.getFormById);
app.post('/api/forms', authenticateToken, requireRole(['Admin']), formController.createForm);
app.put('/api/forms/:id', authenticateToken, requireRole(['Admin']), formController.updateForm);
app.delete('/api/forms/:id', authenticateToken, requireRole(['Admin']), formController.deleteForm);

// SUBMISSIONS API
app.get('/api/submissions', authenticateToken, submissionController.getSubmissions);
app.get('/api/submissions/:id', authenticateToken, submissionController.getSubmissionById);
app.post('/api/submissions', authenticateToken, submissionController.createSubmission);
app.put('/api/submissions/:id', authenticateToken, submissionController.updateSubmission);
app.delete('/api/submissions/:id', authenticateToken, requireRole(['Admin']), submissionController.deleteSubmission);

// REPOSITORIES API
app.get('/api/repositories', authenticateToken, repositoryController.getAllRepositories);
app.get('/api/repositories/:id', authenticateToken, repositoryController.getRepositoryById);
app.post('/api/repositories', authenticateToken, requireRole(['Admin']), repositoryController.createRepository);

// MENUS API
app.get('/api/menus/roles', authenticateToken, requireRole(['Admin']), menuController.getRoles);
app.get('/api/menus', authenticateToken, menuController.getMenuByRole);
app.get('/api/menus/:roleName', authenticateToken, menuController.getMenuByRole);
app.post('/api/menus', authenticateToken, requireRole(['Admin']), menuController.updateMenuByRole);

// Frontend Page routing: Fallback to HTML files
app.get('/login', (req, res) => {
  res.sendFile(path.join(__dirname, '../public/login.html'));
});

// Wildcard fallback to direct views to correct dashboard
app.get('*any', (req, res) => {
  // If requesting api, let it 404
  if (req.url.startsWith('/api')) {
    return res.status(404).json({ error: 'API endpoint not found' });
  }
  res.sendFile(path.join(__dirname, '../public/index.html'));
});

// Start Server
app.listen(PORT, () => {
  console.log(`AppForm server running at http://localhost:${PORT}`);
});
