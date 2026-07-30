<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Models\Repository;
use PDO;

class RepositoryController extends Controller {
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
        $repos = Repository::getAll();
        View::render('repositories/index', [
            'title' => 'Διαχείριση Repositories',
            'repos' => $repos
        ]);
    }

    public function store() {
        $this->checkCsrf();
        $data = Request::all();

        $validated = $this->validate($data, [
            'name' => ['required'],
            'slug' => ['required'],
            'data_json'    => [],
            'columns_json' => []
        ]);

        // Validate Columns JSON if present
        $columnsJson = null;
        if (!empty($validated['columns_json'])) {
            $cols = json_decode($validated['columns_json'], true);
            if (!is_array($cols)) {
                Session::flash('error', 'Το Columns JSON πρέπει να είναι πίνακας.');
                $this->back();
            }
            $keys = [];
            foreach ($cols as $col) {
                if (empty($col['key']) || empty($col['label'])) {
                    Session::flash('error', 'Κάθε στήλη πρέπει να έχει key και label.');
                    $this->back();
                }
                if (in_array($col['key'], $keys)) {
                    Session::flash('error', 'Το machine key στήλης πρέπει να είναι μοναδικό. Βρέθηκε διπλότυπο: ' . $col['key']);
                    $this->back();
                }
                $keys[] = $col['key'];
            }
            $columnsJson = json_encode($cols, JSON_UNESCAPED_UNICODE);
        }

        // Validate Data JSON — only if provided
        $rawJson = trim($validated['data_json'] ?? '');
        if ($rawJson === '') {
            // Empty: create an empty repository
            $dataJson = '[]';
        } else {
            $decoded = json_decode($rawJson, true);
            if (!is_array($decoded)) {
                Session::flash('error', 'Το JSON πρέπει να είναι πίνακας (Array root).');
                $this->back();
            }
            foreach ($decoded as $item) {
                if (!isset($item['value']) || !isset($item['label'])) {
                    Session::flash('error', 'Κάθε εγγραφή στο JSON πρέπει να περιέχει "value" και "label" οπωσδήποτε (Machine Key/Label).');
                    $this->back();
                }
            }
            $dataJson = $rawJson;
        }

        $db = Database::getInstance();
        $chk = $db->prepare("SELECT COUNT(*) FROM repositories WHERE slug = ?");
        $chk->execute([$validated['slug']]);
        if ($chk->fetchColumn() > 0) {
            Session::flash('error', 'Υπάρχει ήδη Repository με αυτό το Slug.');
            $this->back();
        }

        $stmt = $db->prepare("
            INSERT INTO repositories (name, slug, description, data_json, columns_json, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, 1, ?)
        ");
        $stmt->execute([
            $validated['name'],
            strtolower($validated['slug']),
            $data['description'] ?? '',
            $dataJson,
            $columnsJson,
            Auth::id()
        ]);

        $newRepoId = $db->lastInsertId();
        $this->logAudit('create', 'repositories', $newRepoId, ['slug' => $validated['slug']]);

        Session::flash('success', 'Το Repository δημιουργήθηκε επιτυχώς.');
        $this->redirect('/admin/repositories');
    }

    public function edit($params) {
        $id = (int)$params['id'];
        $repo = Repository::findById($id);
        if (!$repo) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        View::render('repositories/edit', [
            'title' => 'Επεξεργασία Repository',
            'repo' => $repo
        ]);
    }

    public function update($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];
        $data = Request::all();

        $repo = Repository::findById($id);
        if (!$repo) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $validated = $this->validate($data, [
            'name' => ['required'],
            'data_json' => ['required', 'json'],
            'columns_json' => []
        ]);

        // Validate Columns JSON if present
        $columnsJson = null;
        if (!empty($validated['columns_json'])) {
            $cols = json_decode($validated['columns_json'], true);
            if (!is_array($cols)) {
                Session::flash('error', 'Το Columns JSON πρέπει να είναι πίνακας.');
                $this->back();
            }
            $keys = [];
            foreach ($cols as $col) {
                if (empty($col['key']) || empty($col['label'])) {
                    Session::flash('error', 'Κάθε στήλη πρέπει να έχει key και label.');
                    $this->back();
                }
                if (in_array($col['key'], $keys)) {
                    Session::flash('error', 'Το machine key στήλης πρέπει να είναι μοναδικό. Βρέθηκε διπλότυπο: ' . $col['key']);
                    $this->back();
                }
                $keys[] = $col['key'];
            }
            $columnsJson = json_encode($cols, JSON_UNESCAPED_UNICODE);
        }

        // Validate Data JSON Structure
        $decoded = json_decode($validated['data_json'], true);
        if (!is_array($decoded)) {
            Session::flash('error', 'Το JSON πρέπει να είναι πίνακας (Array root).');
            $this->back();
        }

        foreach ($decoded as $item) {
            if (!isset($item['value']) || !isset($item['label'])) {
                Session::flash('error', 'Κάθε εγγραφή στο JSON πρέπει να περιέχει "value" και "label".');
                $this->back();
            }
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE repositories SET name = ?, description = ?, data_json = ?, columns_json = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $validated['name'],
            $data['description'] ?? '',
            $validated['data_json'],
            $columnsJson,
            $id
        ]);

        $this->logAudit('edit', 'repositories', $id, ['name' => $validated['name']]);

        Session::flash('success', 'Το Repository ενημερώθηκε επιτυχώς.');
        $this->redirect('/admin/repositories');
    }

    public function toggleStatus($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];

        $repo = Repository::findById($id);
        if (!$repo) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $newStatus = $repo['is_active'] ? 0 : 1;
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE repositories SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);

        $this->logAudit('toggle_status', 'repositories', $id, ['active_status' => $newStatus]);

        Session::flash('success', 'Η κατάσταση άλλαξε επιτυχώς.');
        $this->back();
    }

    public function delete($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];

        $repo = Repository::findById($id);
        if (!$repo) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        if (Repository::isUsedInForms($id)) {
            Session::flash('error', 'Δεν μπορείτε να διαγράψετε Repository που χρησιμοποιείται σε δημοσιευμένη φόρμα.');
            $this->back();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM repositories WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAudit('delete', 'repositories', $id, ['slug' => $repo['slug']]);

        Session::flash('success', 'Το Repository διαγράφηκε επιτυχώς.');
        $this->redirect('/admin/repositories');
    }

    public function preview($params) {
        $id = (int)$params['id'];
        $repo = Repository::findById($id);
        if (!$repo) {
            http_response_code(404);
            echo json_encode(['error' => 'Not Found']);
            exit;
        }

        header('Content-Type: application/json');
        echo $repo['data_json'];
        exit;
    }

    public function apiAutocomplete() {
        $repoId = $_GET['repo_id'] ?? null;
        $searchField = $_GET['search_field'] ?? null;
        $query = $_GET['query'] ?? '';

        if (!$repoId || !$searchField) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }

        $repo = Repository::findById((int)$repoId);
        if (!$repo || !$repo['is_active']) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }

        $data = json_decode($repo['data_json'], true);
        if (!is_array($data)) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }

        $results = [];
        $queryLower = mb_strtolower($query);

        foreach ($data as $item) {
            if (isset($item[$searchField])) {
                $itemVal = mb_strtolower((string)$item[$searchField]);
                if ($queryLower === '' || mb_strpos($itemVal, $queryLower) !== false) {
                    $results[] = $item;
                    if (count($results) >= 20) break;
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode($results, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function apiTags() {
        $repo1Id = $_GET['repo1'] ?? null;
        $repo2Id = $_GET['repo2'] ?? null;
        $search1 = $_GET['search1'] ?? 'label';
        $search2 = $_GET['search2'] ?? 'label';
        
        if (!$repo1Id) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }
        
        $tags = [];
        
        $repo1 = Repository::findById((int)$repo1Id);
        if ($repo1 && $repo1['is_active']) {
            $data = json_decode($repo1['data_json'], true);
            if (is_array($data)) {
                foreach ($data as $item) {
                    if (isset($item[$search1])) {
                        $tags[] = (string)$item[$search1];
                    }
                }
            }
        }
        
        if ($repo2Id) {
            $repo2 = Repository::findById((int)$repo2Id);
            if ($repo2 && $repo2['is_active']) {
                $data = json_decode($repo2['data_json'], true);
                if (is_array($data)) {
                    foreach ($data as $item) {
                        if (isset($item[$search2])) {
                            $tags[] = (string)$item[$search2];
                        }
                    }
                }
            }
        }
        
        $tags = array_values(array_unique($tags));
        
        header('Content-Type: application/json');
        echo json_encode($tags, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
