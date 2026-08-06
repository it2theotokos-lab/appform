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

    /**
     * Export repository dataset in CSV, XLSX, or JSON format.
     */
    public function export($params) {
        $id = (int)$params['id'];
        $repo = Repository::findById($id);
        if (!$repo) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $format = strtolower($_GET['format'] ?? 'csv');
        if (!in_array($format, ['csv', 'xlsx', 'json'], true)) {
            $format = 'csv';
        }

        // Header column keys
        $headers = ['value', 'label'];
        $cols = json_decode($repo['columns_json'] ?? '[]', true);
        if (is_array($cols)) {
            foreach ($cols as $c) {
                if (!empty($c['key']) && !in_array($c['key'], $headers, true)) {
                    $headers[] = $c['key'];
                }
            }
        }

        $rows = json_decode($repo['data_json'] ?? '[]', true);
        if (!is_array($rows)) {
            $rows = [];
        }

        $slug = preg_replace('/[^a-z0-9_-]/i', '_', $repo['slug'] ?: 'repository');
        $filename = "{$slug}_export." . ($format === 'excel' ? 'xlsx' : $format);

        $this->logAudit('export', 'repositories', $id, ['format' => $format, 'count' => count($rows)]);

        if ($format === 'xlsx') {
            $content = \App\Services\SpreadsheetService::exportXlsx($headers, $rows);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$slug}_export.xlsx\"");
            header('Content-Length: ' . strlen($content));
            echo $content;
            exit;
        } elseif ($format === 'json') {
            $content = \App\Services\SpreadsheetService::exportJson($headers, $rows);
            header('Content-Type: application/json; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$slug}_export.json\"");
            echo $content;
            exit;
        } else {
            $content = \App\Services\SpreadsheetService::exportCsv($headers, $rows);
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$slug}_export.csv\"");
            echo $content;
            exit;
        }
    }

    /**
     * Download empty repository template file (CSV, XLSX, JSON).
     */
    public function downloadTemplate($params) {
        $id = (int)$params['id'];
        $repo = Repository::findById($id);
        if (!$repo) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $format = strtolower($_GET['format'] ?? 'csv');
        if (!in_array($format, ['csv', 'xlsx', 'json'], true)) {
            $format = 'csv';
        }

        $headers = ['value', 'label'];
        $cols = json_decode($repo['columns_json'] ?? '[]', true);
        if (is_array($cols)) {
            foreach ($cols as $c) {
                if (!empty($c['key']) && !in_array($c['key'], $headers, true)) {
                    $headers[] = $c['key'];
                }
            }
        }

        // Sample placeholder row
        $sampleRow = [];
        foreach ($headers as $h) {
            $sampleRow[$h] = ($h === 'value' ? 'sample_code' : ($h === 'label' ? 'Sample Item Name' : ''));
        }
        $rows = [$sampleRow];

        $slug = preg_replace('/[^a-z0-9_-]/i', '_', $repo['slug'] ?: 'repository');

        $this->logAudit('template_download', 'repositories', $id, ['format' => $format]);

        if ($format === 'xlsx') {
            $content = \App\Services\SpreadsheetService::exportXlsx($headers, $rows);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$slug}_template.xlsx\"");
            header('Content-Length: ' . strlen($content));
            echo $content;
            exit;
        } elseif ($format === 'json') {
            $content = \App\Services\SpreadsheetService::exportJson($headers, $rows);
            header('Content-Type: application/json; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$slug}_template.json\"");
            echo $content;
            exit;
        } else {
            $content = \App\Services\SpreadsheetService::exportCsv($headers, $rows);
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$slug}_template.csv\"");
            echo $content;
            exit;
        }
    }

    /**
     * Preview uploaded import file (CSV/XLSX/JSON) and return validation summary JSON.
     */
    public function previewImport($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];
        $repo = Repository::findById($id);
        if (!$repo) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => __('Repository not found.')]);
            exit;
        }

        if (empty($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => __('Uploaded file is missing or failed to upload.')]);
            exit;
        }

        $tmpFile = $_FILES['import_file']['tmp_name'];
        $origName = $_FILES['import_file']['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        $strategy = $_POST['strategy'] ?? 'upsert';
        if (!in_array($strategy, ['insert', 'update', 'upsert'], true)) {
            $strategy = 'upsert';
        }

        try {
            if ($ext === 'csv') {
                $parsed = \App\Services\SpreadsheetService::parseCsvFile($tmpFile);
            } elseif ($ext === 'xlsx') {
                $parsed = \App\Services\SpreadsheetService::parseXlsxFile($tmpFile);
            } elseif ($ext === 'json') {
                $content = file_get_contents($tmpFile);
                $parsed = \App\Services\SpreadsheetService::parseJsonContent($content);
            } else {
                throw new \Exception(__('Unsupported file extension. Allowed: CSV, XLSX, JSON.'));
            }
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        $headers = $parsed['headers'];
        $rows = $parsed['rows'];

        // Known Repository headers
        $knownHeaders = ['value', 'label'];
        $cols = json_decode($repo['columns_json'] ?? '[]', true);
        if (is_array($cols)) {
            foreach ($cols as $c) {
                if (!empty($c['key']) && !in_array($c['key'], $knownHeaders, true)) {
                    $knownHeaders[] = $c['key'];
                }
            }
        }

        // Validate headers: check for unknown headers
        $unknownHeaders = array_diff($headers, $knownHeaders);

        // Existing repository data map indexed by `value` key
        $existingItems = json_decode($repo['data_json'] ?? '[]', true) ?: [];
        $existingKeys = [];
        foreach ($existingItems as $item) {
            if (isset($item['value'])) {
                $existingKeys[(string)$item['value']] = true;
            }
        }

        $validationErrors = [];
        $validCount = 0;
        $insertCount = 0;
        $updateCount = 0;
        $seenFileKeys = [];
        $previewRows = [];

        foreach ($rows as $idx => $row) {
            $lineNum = $row['_line'] ?? ($idx + 2);
            $rowErrors = [];

            $val = trim((string)($row['value'] ?? ''));
            $label = trim((string)($row['label'] ?? ''));

            if ($val === '') {
                $rowErrors[] = __('Line %s — Column value — Value is required.', [$lineNum]);
            }
            if ($label === '') {
                $rowErrors[] = __('Line %s — Column label — Label is required.', [$lineNum]);
            }

            if ($val !== '') {
                if (isset($seenFileKeys[$val])) {
                    $rowErrors[] = __('Line %s — Column value — Duplicate value "%s" in file.', [$lineNum, $val]);
                }
                $seenFileKeys[$val] = true;

                $exists = isset($existingKeys[$val]);
                if ($strategy === 'insert' && $exists) {
                    $rowErrors[] = __('Line %s — Value "%s" already exists (Insert mode).', [$lineNum, $val]);
                } elseif ($strategy === 'update' && !$exists) {
                    $rowErrors[] = __('Line %s — Value "%s" does not exist (Update mode).', [$lineNum, $val]);
                }

                if (empty($rowErrors)) {
                    if ($exists) {
                        $updateCount++;
                    } else {
                        $insertCount++;
                    }
                }
            }

            if (!empty($rowErrors)) {
                foreach ($rowErrors as $err) {
                    $validationErrors[] = $err;
                }
            } else {
                $validCount++;
            }

            if (count($previewRows) < 10) {
                unset($row['_line']);
                $previewRows[] = $row;
            }
        }

        // Save validated upload payload to temporary session file for safe execution on confirmation
        $tempToken = bin2hex(random_bytes(16));
        $tempPath = sys_get_temp_dir() . "/repo_import_{$tempToken}.json";
        file_put_contents($tempPath, json_encode([
            'repo_id' => $id,
            'strategy' => $strategy,
            'rows' => $rows,
            'known_headers' => $knownHeaders
        ], JSON_UNESCAPED_UNICODE));

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'temp_token' => $tempToken,
            'total_rows' => count($rows),
            'valid_rows' => $validCount,
            'insert_rows' => $insertCount,
            'update_rows' => $updateCount,
            'failed_rows' => count($rows) - $validCount,
            'unknown_headers' => array_values($unknownHeaders),
            'errors' => $validationErrors,
            'preview_rows' => $previewRows
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Process confirmed repository import with automatic backup & transaction safety.
     */
    public function processImport($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];
        $repo = Repository::findById($id);
        if (!$repo) {
            Session::flash('error', __('Repository not found.'));
            $this->back();
        }

        $tempToken = $_POST['temp_token'] ?? '';
        $tempPath = sys_get_temp_dir() . "/repo_import_{$tempToken}.json";

        if ($tempToken === '' || !file_exists($tempPath)) {
            Session::flash('error', __('Import session expired. Please re-upload your file.'));
            $this->redirect("/admin/repositories/{$id}/edit");
        }

        $importData = json_decode(file_get_contents($tempPath), true);
        @unlink($tempPath); // cleanup temp payload file

        if (!$importData || (int)$importData['repo_id'] !== $id) {
            Session::flash('error', __('Invalid import session data.'));
            $this->redirect("/admin/repositories/{$id}/edit");
        }

        $rows = $importData['rows'];
        $strategy = $importData['strategy'];

        // Automatic Backup of current state before processing
        $backupJson = $repo['data_json'];

        $existingItems = json_decode($backupJson, true) ?: [];
        $itemsMap = [];
        foreach ($existingItems as $item) {
            if (isset($item['value'])) {
                $itemsMap[(string)$item['value']] = $item;
            }
        }

        $insertedCount = 0;
        $updatedCount = 0;
        $knownHeaders = $importData['known_headers'] ?? ['value', 'label'];

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            foreach ($rows as $row) {
                unset($row['_line']);
                $val = (string)($row['value'] ?? '');
                if ($val === '') continue;

                $cleanItem = [];
                foreach ($knownHeaders as $h) {
                    $cleanItem[$h] = $row[$h] ?? '';
                }

                if (isset($itemsMap[$val])) {
                    // Update
                    $itemsMap[$val] = array_merge($itemsMap[$val], $cleanItem);
                    $updatedCount++;
                } else {
                    // Insert
                    $itemsMap[$val] = $cleanItem;
                    $insertedCount++;
                }
            }

            $newItemsArray = array_values($itemsMap);
            $newDataJson = json_encode($newItemsArray, JSON_UNESCAPED_UNICODE);

            $stmt = $db->prepare("UPDATE repositories SET data_json = ? WHERE id = ?");
            $stmt->execute([$newDataJson, $id]);

            $db->commit();

            $this->logAudit('import', 'repositories', $id, [
                'strategy' => $strategy,
                'inserted' => $insertedCount,
                'updated' => $updatedCount
            ]);

            Session::flash('success', __('Import completed successfully! Inserted: %s, Updated: %s.', [$insertedCount, $updatedCount]));
            $this->redirect("/admin/repositories/{$id}/edit");

        } catch (\Exception $e) {
            $db->rollBack();
            // Restore previous state if fail occurs
            $stmtRestore = $db->prepare("UPDATE repositories SET data_json = ? WHERE id = ?");
            $stmtRestore->execute([$backupJson, $id]);

            Session::flash('error', __('Import failed during database write. Previous state restored. Error: %s', [$e->getMessage()]));
            $this->redirect("/admin/repositories/{$id}/edit");
        }
    }
}

