const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const bcrypt = require('bcryptjs');

const dbPath = path.resolve(__dirname, '../../appform.db');
const db = new sqlite3.Database(dbPath, (err) => {
  if (err) {
    console.error('Database connection error:', err.message);
  } else {
    console.log('Connected to the SQLite database.');
  }
});

// Helper for running queries with promises
db.runAsync = function (sql, params = []) {
  return new Promise((resolve, reject) => {
    this.run(sql, params, function (err) {
      if (err) reject(err);
      else resolve(this);
    });
  });
};

db.getAsync = function (sql, params = []) {
  return new Promise((resolve, reject) => {
    this.get(sql, params, (err, row) => {
      if (err) reject(err);
      else resolve(row);
    });
  });
};

db.allAsync = function (sql, params = []) {
  return new Promise((resolve, reject) => {
    this.all(sql, params, (err, rows) => {
      if (err) reject(err);
      else resolve(rows);
    });
  });
};

// Initialize schema
async function initDatabase() {
  try {
    // 1. Roles table
    await db.runAsync(`
      CREATE TABLE IF NOT EXISTS roles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // 2. Users table
    await db.runAsync(`
      CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
      )
    `);

    // 3. Repositories table
    await db.runAsync(`
      CREATE TABLE IF NOT EXISTS repositories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        data_json TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // 4. Forms table
    await db.runAsync(`
      CREATE TABLE IF NOT EXISTS forms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        schema_json TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // 5. Form Submissions table
    await db.runAsync(`
      CREATE TABLE IF NOT EXISTS form_submissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        form_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        data_json TEXT NOT NULL,
        status TEXT DEFAULT 'submitted',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
      )
    `);

    // 6. Navigation Menus table
    await db.runAsync(`
      CREATE TABLE IF NOT EXISTS navigation_menus (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        role_id INTEGER NOT NULL UNIQUE,
        menu_structure_json TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
      )
    `);

    // Seed Roles
    const rolesCount = await db.getAsync('SELECT COUNT(*) as count FROM roles');
    if (rolesCount.count === 0) {
      await db.runAsync("INSERT INTO roles (id, name) VALUES (1, 'Admin')");
      await db.runAsync("INSERT INTO roles (id, name) VALUES (2, 'Manager')");
      await db.runAsync("INSERT INTO roles (id, name) VALUES (3, 'User')");
      console.log('Default roles seeded.');
    }

    // Seed Users
    const usersCount = await db.getAsync('SELECT COUNT(*) as count FROM users');
    if (usersCount.count === 0) {
      const adminHash = await bcrypt.hash('admin123', 10);
      const managerHash = await bcrypt.hash('manager123', 10);
      const userHash = await bcrypt.hash('user123', 10);

      await db.runAsync("INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, 1)", ['admin', adminHash]);
      await db.runAsync("INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, 2)", ['manager', managerHash]);
      await db.runAsync("INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, 3)", ['user', userHash]);
      console.log('Default users seeded (admin/admin123, manager/manager123, user/user123).');
    }

    // Seed Default Navigation Menus
    const menuCount = await db.getAsync('SELECT COUNT(*) as count FROM navigation_menus');
    if (menuCount.count === 0) {
      const adminMenu = JSON.stringify([
        { title: 'Dashboard', path: '/dashboard' },
        { title: 'Form Builder', path: '/builder' },
        { title: 'Menu Builder', path: '/menus' },
        { title: 'Submissions', path: '/submissions' },
        { title: 'Analytics', path: '/analytics' }
      ]);
      const managerMenu = JSON.stringify([
        { title: 'Dashboard', path: '/dashboard' },
        { title: 'Submissions', path: '/submissions' },
        { title: 'Analytics', path: '/analytics' }
      ]);
      const userMenu = JSON.stringify([
        { title: 'My Portal', path: '/portal' }
      ]);

      await db.runAsync("INSERT INTO navigation_menus (role_id, menu_structure_json) VALUES (1, ?)", [adminMenu]);
      await db.runAsync("INSERT INTO navigation_menus (role_id, menu_structure_json) VALUES (2, ?)", [managerMenu]);
      await db.runAsync("INSERT INTO navigation_menus (role_id, menu_structure_json) VALUES (3, ?)", [userMenu]);
      console.log('Default navigation menus seeded.');
    }

    // Seed Default Repositories
    const repoCount = await db.getAsync('SELECT COUNT(*) as count FROM repositories');
    if (repoCount.count === 0) {
      const depts = JSON.stringify([
        { id: 1, name: 'IT Department' },
        { id: 2, name: 'HR Department' },
        { id: 3, name: 'Operations' },
        { id: 4, name: 'Sales & Marketing' }
      ]);
      const eqTypes = JSON.stringify([
        { id: 1, name: 'Laptop' },
        { id: 2, name: 'Desktop PC' },
        { id: 3, name: 'Server' },
        { id: 4, name: 'Printer' },
        { id: 5, name: 'Network Switch' }
      ]);
      await db.runAsync("INSERT INTO repositories (name, data_json) VALUES (?, ?)", ['Departments', depts]);
      await db.runAsync("INSERT INTO repositories (name, data_json) VALUES (?, ?)", ['Equipment Types', eqTypes]);
      console.log('Default repositories seeded.');
    }

  } catch (err) {
    console.error('Error seeding database:', err);
  }
}

initDatabase();

module.exports = db;
