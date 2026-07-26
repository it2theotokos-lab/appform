const db = require('../config/db');

exports.getMenuByRole = async (req, res) => {
  const roleName = req.params.roleName || req.user.role;
  try {
    const role = await db.getAsync('SELECT id FROM roles WHERE name = ?', [roleName]);
    if (!role) {
      return res.status(404).json({ error: 'Role not found' });
    }

    const menu = await db.getAsync('SELECT * FROM navigation_menus WHERE role_id = ?', [role.id]);
    if (!menu) {
      return res.json([]);
    }
    res.json(JSON.parse(menu.menu_structure_json));
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.updateMenuByRole = async (req, res) => {
  const { roleId, menuStructure } = req.body;
  if (!roleId || !menuStructure) {
    return res.status(400).json({ error: 'Role ID and menu structure are required' });
  }

  try {
    const result = await db.runAsync(
      `INSERT INTO navigation_menus (role_id, menu_structure_json) 
       VALUES (?, ?)
       ON CONFLICT(role_id) DO UPDATE SET menu_structure_json = excluded.menu_structure_json`,
      [roleId, JSON.stringify(menuStructure)]
    );
    res.json({ message: 'Menu structure updated successfully' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};

exports.getRoles = async (req, res) => {
  try {
    const roles = await db.allAsync('SELECT * FROM roles');
    res.json(roles);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }
};
