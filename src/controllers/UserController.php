<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Models\User;
use App\Models\Role;
use PDO;

class UserController extends Controller {
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
        $search = $_GET['search'] ?? '';
        $roleId = $_GET['role_id'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $page = (int)($_GET['page'] ?? 1);
        if ($page < 1) $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $db = Database::getInstance();
        $query = "
            SELECT u.*, r.name as role_name, o.name as org_unit_name, o.type as org_unit_type 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            LEFT JOIN org_units o ON u.org_unit_id = o.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)";
            $searchVal = "%$search%";
            $params[] = $searchVal;
            $params[] = $searchVal;
            $params[] = $searchVal;
        }

        if ($roleId !== '') {
            $query .= " AND u.role_id = ?";
            $params[] = (int)$roleId;
        }

        if ($status !== '') {
            $query .= " AND u.is_active = ?";
            $params[] = (int)$status;
        }

        // Count queries
        $countQuery = str_replace("u.*, r.name as role_name", "COUNT(*)", $query);
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($params);
        $totalItems = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalItems / $limit);

        // Fetch items
        $query .= " ORDER BY u.id ASC LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $roles = Role::getAll();

        View::render('users/index', [
            'title' => 'Διαχείριση Χρηστών',
            'users' => $users,
            'roles' => $roles,
            'search' => $search,
            'roleId' => $roleId,
            'status' => $status,
            'page' => $page,
            'totalPages' => $totalPages
        ]);
    }

    public function create() {
        $roles = Role::getAll();
        View::render('users/create', [
            'title' => 'Νέος Χρήστης',
            'roles' => $roles
        ]);
    }

    public function store() {
        $this->checkCsrf();
        $data = Request::all();

        $validated = $this->validate($data, [
            'username' => ['required'],
            'email' => ['required', 'email'],
            'full_name' => ['required'],
            'role_id' => ['required', 'numeric'],
            'password' => ['required']
        ]);

        $db = Database::getInstance();
        
        // Validation checks
        $chk1 = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $chk1->execute([$validated['username']]);
        if ($chk1->fetchColumn() > 0) {
            Session::flash('errors', ['username' => ['Το όνομα χρήστη χρησιμοποιείται ήδη.']]);
            $this->back();
        }

        $chk2 = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $chk2->execute([$validated['email']]);
        if ($chk2->fetchColumn() > 0) {
            Session::flash('errors', ['email' => ['Το email χρησιμοποιείται ήδη.']]);
            $this->back();
        }

        $hash = password_hash($validated['password'], PASSWORD_BCRYPT);
        
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password_hash, full_name, role_id, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $validated['username'],
            $validated['email'],
            $hash,
            $validated['full_name'],
            $validated['role_id']
        ]);
        
        $newUserId = $db->lastInsertId();
        $this->logAudit('create', 'users', $newUserId, ['username' => $validated['username']]);

        Session::flash('success', 'Ο χρήστης δημιουργήθηκε επιτυχώς.');
        $this->redirect('/admin/users');
    }

    public function edit($params) {
        $id = (int)$params['id'];
        $user = User::findById($id);
        if (!$user) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $roles = Role::getAll();
        $orgUnits = \App\Models\OrgUnit::all();
        $db = Database::getInstance();
        $stmtM = $db->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id != ? AND u.is_active = 1
            ORDER BY u.full_name ASC
        ");
        $stmtM->execute([$id]);
        $managers = $stmtM->fetchAll(PDO::FETCH_ASSOC);

        View::render('users/edit', [
            'title' => 'Επεξεργασία Χρήστη',
            'user' => $user,
            'roles' => $roles,
            'orgUnits' => $orgUnits,
            'managers' => $managers
        ]);
    }

    public function update($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];
        $data = Request::all();

        $user = User::findById($id);
        if (!$user) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $validated = $this->validate($data, [
            'email' => ['required', 'email'],
            'full_name' => ['required'],
            'role_id' => ['required', 'numeric']
        ]);

        $personalEmail = trim($data['personal_email'] ?? '');
        $corporatePhone = trim($data['corporate_phone'] ?? '');
        $mobilePhone = trim($data['mobile_phone'] ?? '');
        $internalPhone = trim($data['internal_phone'] ?? '');
        $orgUnitId = !empty($data['org_unit_id']) ? (int)$data['org_unit_id'] : null;

        // Validation for personal email
        if (!empty($personalEmail) && !filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', __('Invalid personal email format.'));
            $this->back();
            return;
        }

        // Length validation for phones
        if (strlen($corporatePhone) > 30 || strlen($mobilePhone) > 30 || strlen($internalPhone) > 20) {
            Session::flash('error', __('Phone numbers exceed maximum allowed length.'));
            $this->back();
            return;
        }

        // Validate org_unit_id if set
        if ($orgUnitId !== null) {
            $unit = \App\Models\OrgUnit::find($orgUnitId);
            if (!$unit) {
                Session::flash('error', __('Selected organizational unit does not exist.'));
                $this->back();
                return;
            }
        }

        $db = Database::getInstance();

        // Check email conflict
        $chk = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $chk->execute([$validated['email'], $id]);
        if ($chk->fetchColumn() > 0) {
            Session::flash('errors', ['email' => ['Το email χρησιμοποιείται ήδη.']]);
            $this->back();
            return;
        }

        // Prevent last Administrator role removal
        if ($user['role_id'] == 1 && $validated['role_id'] != 1) {
            $adminCount = $db->query("SELECT COUNT(*) FROM users WHERE role_id = 1 AND is_active = 1")->fetchColumn();
            if ($adminCount <= 1) {
                Session::flash('error', 'Δεν μπορείτε να αφαιρέσετε τον ρόλο του Administrator από τον τελευταίο ενεργό διαχειριστή.');
                $this->back();
                return;
            }
        }

        $managerId = !empty($data['manager_id']) ? (int)$data['manager_id'] : null;

        // Circular hierarchy check
        if ($managerId !== null) {
            if (\App\Services\OrganizationalScopeService::detectCircularHierarchy($id, $managerId)) {
                Session::flash('error', 'Δεν επιτρέπεται κυκλική συσχέτιση προϊσταμένου (Circular/Self hierarchy loop).');
                $this->back();
                return;
            }
        }

        $stmt = $db->prepare("
            UPDATE users SET 
                email = ?, 
                full_name = ?, 
                role_id = ?, 
                manager_id = ?,
                org_unit_id = ?,
                personal_email = ?,
                corporate_phone = ?,
                mobile_phone = ?,
                internal_phone = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $validated['email'],
            $validated['full_name'],
            $validated['role_id'],
            $managerId,
            $orgUnitId,
            $personalEmail ?: null,
            $corporatePhone ?: null,
            $mobilePhone ?: null,
            $internalPhone ?: null,
            $id
        ]);

        $this->logAudit('edit', 'users', $id, [
            'email' => $validated['email'], 
            'manager_id' => $managerId,
            'org_unit_id' => $orgUnitId
        ]);

        Session::flash('success', 'Ο χρήστης ενημερώθηκε επιτυχώς.');
        $this->redirect('/admin/users');
    }

    public function toggleStatus($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];
        
        if ($id === Auth::id()) {
            Session::flash('error', 'Δεν μπορείτε να απενεργοποιήσετε τον εαυτό σας.');
            $this->back();
        }

        $user = User::findById($id);
        if (!$user) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $db = Database::getInstance();

        // Prevent last Admin inactivation
        if ($user['role_id'] == 1 && $user['is_active'] == 1) {
            $adminCount = $db->query("SELECT COUNT(*) FROM users WHERE role_id = 1 AND is_active = 1")->fetchColumn();
            if ($adminCount <= 1) {
                Session::flash('error', 'Δεν μπορείτε να απενεργοποιήσετε τον τελευταίο ενεργό διαχειριστή.');
                $this->back();
            }
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);

        $this->logAudit('toggle_status', 'users', $id, ['active_status' => $newStatus]);

        Session::flash('success', 'Η κατάσταση του χρήστη άλλαξε επιτυχώς.');
        $this->back();
    }

    public function delete($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];

        if ($id === Auth::id()) {
            Session::flash('error', 'Δεν μπορείτε να διαγράψετε τον εαυτό σας.');
            $this->back();
        }

        $user = User::findById($id);
        if (!$user) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $db = Database::getInstance();

        if ($user['role_id'] == 1) {
            $adminCount = $db->query("SELECT COUNT(*) FROM users WHERE role_id = 1")->fetchColumn();
            if ($adminCount <= 1) {
                Session::flash('error', 'Δεν μπορείτε να διαγράψετε τον τελευταίο διαχειριστή.');
                $this->back();
            }
        }

        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAudit('delete', 'users', $id, ['username' => $user['username']]);

        Session::flash('success', 'Ο χρήστης διαγράφηκε επιτυχώς.');
        $this->redirect('/admin/users');
    }

    public function resetPassword($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];
        $data = Request::all();

        $user = User::findById($id);
        if (!$user) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        if (empty($data['password'])) {
            Session::flash('error', 'Ο κωδικός πρόσβασης δεν μπορεί να είναι κενός.');
            $this->back();
        }

        $db = Database::getInstance();
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $id]);

        $this->logAudit('reset_password', 'users', $id, []);

        Session::flash('success', 'Ο κωδικός πρόσβασης άλλαξε επιτυχώς.');
        $this->back();
    }

    public function showOrganizationTree() {
        $db = Database::getInstance();
        $stmt = $db->query("
            SELECT u.*, r.name as role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            ORDER BY u.id ASC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('users/organization', [
            'title' => 'Οργανωτική Δομή',
            'users' => $users
        ]);
    }
}
