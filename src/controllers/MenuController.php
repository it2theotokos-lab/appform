<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Models\Role;
use App\Models\Form;
use App\Models\NavigationMenu;
use App\Models\NavigationMenuItem;
use PDO;

class MenuController extends Controller {
    protected function logAudit(string $action, string $entityType, ?int $entityId, array $metadata) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                Auth::id(),
                $action,
                $entityType,
                $entityId,
                json_encode($metadata, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
        } catch (\Exception $e) {}
    }

    public function index() {
        $roles = Role::getAll();
        View::render('menus/index', [
            'title' => 'Διαχείριση Μενού',
            'roles' => $roles
        ]);
    }

    public function edit($params) {
        $roleId = (int)$params['roleId'];
        
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$roleId]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$role) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        // Get or Create active menu record
        $menu = NavigationMenu::findByRoleId($roleId);
        if (!$menu) {
            $ins = $db->prepare("INSERT INTO navigation_menus (role_id, name, menu_structure_json, is_active) VALUES (?, ?, '[]', 1)");
            $ins->execute([$roleId, $role['name'] . ' Menu']);
            $menuId = $db->lastInsertId();
            $menu = NavigationMenu::findById($menuId);
        }

        $items = NavigationMenuItem::getItemsByMenuId($menu['id']);
        $forms = Form::getAll();
        $permissions = $db->query("SELECT slug, name FROM permissions ORDER BY slug ASC")->fetchAll(PDO::FETCH_ASSOC);

        View::render('menus/edit', [
            'title' => 'Επεξεργασία Μενού: ' . $role['name'],
            'role' => $role,
            'menu' => $menu,
            'items' => $items,
            'forms' => $forms,
            'permissions' => $permissions
        ]);
    }

    public function addItem($params) {
        $this->checkCsrf();
        $menuId = (int)$params['menuId'];
        $data = Request::all();

        $validated = $this->validate($data, [
            'label' => ['required'],
            'item_type' => ['required']
        ]);

        $db = Database::getInstance();

        $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
        $route = $data['route_name'] ?? null;
        $url = $data['url'] ?? null;
        $formId = !empty($data['form_id']) ? (int)$data['form_id'] : null;
        $perm = !empty($data['permission_slug']) ? $data['permission_slug'] : null;

        $stmt = $db->prepare("
            INSERT INTO navigation_menu_items (menu_id, parent_id, label, icon, item_type, route_name, url, form_id, permission_slug, sort_order, open_in_new_tab, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $menuId,
            $parentId,
            $validated['label'],
            $data['icon'] ?? 'fa-solid fa-link',
            $validated['item_type'],
            $route,
            $url,
            $formId,
            $perm,
            (int)($data['sort_order'] ?? 0),
            isset($data['open_in_new_tab']) ? 1 : 0
        ]);

        $this->logAudit('add_menu_item', 'navigation_menus', $menuId, ['label' => $validated['label'], 'parent_id' => $parentId]);
        Session::flash('success', 'Το στοιχείο προστέθηκε στο μενού.');
        $this->back();
    }

    public function updateItem($params) {
        $this->checkCsrf();
        $itemId = (int)$params['itemId'];
        $data = Request::all();

        $db = Database::getInstance();

        $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
        if ($parentId === $itemId) {
            Session::flash('error', 'Ένα στοιχείο δεν μπορεί να οριστεί ως γονέας του εαυτού του.');
            $this->back();
            return;
        }

        // Detect circular hierarchy loop
        if ($parentId !== null && $this->isDescendant($parentId, $itemId)) {
            Session::flash('error', 'Δεν επιτρέπεται κυκλική εξάρτηση γονέα/παιδιού.');
            $this->back();
            return;
        }

        $stmt = $db->prepare("
            UPDATE navigation_menu_items 
            SET parent_id = ?, label = ?, icon = ?, item_type = ?, route_name = ?, url = ?, form_id = ?, permission_slug = ?, sort_order = ?, open_in_new_tab = ?, is_active = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $parentId,
            $data['label'],
            $data['icon'] ?? 'fa-solid fa-link',
            $data['item_type'],
            $data['route_name'] ?? null,
            $data['url'] ?? null,
            !empty($data['form_id']) ? (int)$data['form_id'] : null,
            !empty($data['permission_slug']) ? $data['permission_slug'] : null,
            (int)($data['sort_order'] ?? 0),
            isset($data['open_in_new_tab']) ? 1 : 0,
            isset($data['is_active']) ? 1 : 0,
            $itemId
        ]);

        $this->logAudit('update_menu_item', 'navigation_menu_items', $itemId, ['parent_id' => $parentId]);
        Session::flash('success', 'Το στοιχείο μενού ενημερώθηκε επιτυχώς.');
        $this->back();
    }

    public function reorderItem($params) {
        $this->checkCsrf();
        $itemId = (int)$params['itemId'];
        $data = Request::all();

        $direction = $data['direction'] ?? 'up';
        $db = Database::getInstance();

        $stmt = $db->prepare("SELECT * FROM navigation_menu_items WHERE id = ?");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item) {
            $currentOrder = (int)$item['sort_order'];
            $newOrder = ($direction === 'up') ? max(0, $currentOrder - 1) : ($currentOrder + 1);
            
            $upd = $db->prepare("UPDATE navigation_menu_items SET sort_order = ? WHERE id = ?");
            $upd->execute([$newOrder, $itemId]);
        }

        Session::flash('success', 'Η σειρά ενημερώθηκε.');
        $this->back();
    }

    public function deleteItem($params) {
        $this->checkCsrf();
        $itemId = (int)$params['itemId'];
        $data = Request::all();
        $childAction = $data['child_action'] ?? 'promote'; // 'promote', 'reassign', 'delete'
        $targetParentId = !empty($data['target_parent_id']) ? (int)$data['target_parent_id'] : null;

        $db = Database::getInstance();

        if ($childAction === 'delete') {
            // Delete all children recursively
            $this->deleteChildrenRecursive($itemId);
        } elseif ($childAction === 'reassign' && $targetParentId !== null && $targetParentId !== $itemId) {
            $stmt = $db->prepare("UPDATE navigation_menu_items SET parent_id = ? WHERE parent_id = ?");
            $stmt->execute([$targetParentId, $itemId]);
        } else {
            // Default: promote children to root (NULL parent_id)
            $stmt = $db->prepare("UPDATE navigation_menu_items SET parent_id = NULL WHERE parent_id = ?");
            $stmt->execute([$itemId]);
        }

        // Delete parent item
        $stmtDel = $db->prepare("DELETE FROM navigation_menu_items WHERE id = ?");
        $stmtDel->execute([$itemId]);

        $this->logAudit('delete_menu_item', 'navigation_menu_items', $itemId, ['child_action' => $childAction]);
        Session::flash('success', 'Το στοιχείο διαγράφηκε.');
        $this->back();
    }

    private function isDescendant(int $possibleDescendantId, int $ancestorId): bool {
        $db = Database::getInstance();
        $curr = $possibleDescendantId;

        while ($curr !== null) {
            if ($curr === $ancestorId) {
                return true;
            }
            $stmt = $db->prepare("SELECT parent_id FROM navigation_menu_items WHERE id = ?");
            $stmt->execute([$curr]);
            $curr = $stmt->fetchColumn();
            if ($curr === false || $curr === null) break;
        }

        return false;
    }

    private function deleteChildrenRecursive(int $parentId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM navigation_menu_items WHERE parent_id = ?");
        $stmt->execute([$parentId]);
        $children = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($children as $childId) {
            $this->deleteChildrenRecursive((int)$childId);
            $del = $db->prepare("DELETE FROM navigation_menu_items WHERE id = ?");
            $del->execute([$childId]);
        }
    }
}
