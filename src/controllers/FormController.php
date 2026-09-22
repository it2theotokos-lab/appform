<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Repository;
use App\Models\Role;
use App\Models\User;
use App\Models\FormRoleAssignment;
use App\Models\FormUserAssignment;
use PDO;

class FormController extends Controller {
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
        $db = Database::getInstance();
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(title LIKE ? OR slug LIKE ? OR description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($status !== '') {
            $where[] = "status = ?";
            $params[] = $status;
        }

        $sql = "SELECT * FROM forms";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $forms = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        View::render('forms/index', [
            'title' => 'Διαχείριση Φορμών',
            'forms' => $forms,
            'totalRecords' => count($forms)
        ]);
    }

    public function store() {
        $this->checkCsrf();
        $data = Request::all();

        $validated = $this->validate($data, [
            'title' => ['required'],
            'slug' => ['required']
        ]);

        $db = Database::getInstance();
        $chk = $db->prepare("SELECT COUNT(*) FROM forms WHERE slug = ?");
        $chk->execute([$validated['slug']]);
        if ($chk->fetchColumn() > 0) {
            Session::flash('error', 'Υπάρχει ήδη φόρμα με αυτό το Slug.');
            $this->back();
        }

        $stmt = $db->prepare("
            INSERT INTO forms (title, slug, description, current_version, status, is_active, allow_drafts, created_by)
            VALUES (?, ?, ?, 1, 'draft', 1, 1, ?)
        ");
        $stmt->execute([
            $validated['title'],
            strtolower($validated['slug']),
            $data['description'] ?? '',
            Auth::id()
        ]);

        $newFormId = $db->lastInsertId();

        // Create first draft version
        $emptySchema = json_encode([
            'schemaVersion' => 1,
            'settings' => [
                'submitLabel' => 'Υποβολή',
                'draftLabel' => 'Αποθήκευση ως πρόχειρο',
                'allowDraft' => true
            ],
            'sections' => []
        ]);

        $insVer = $db->prepare("
            INSERT INTO form_versions (form_id, version_number, schema_json, created_by)
            VALUES (?, 1, ?, ?)
        ");
        $insVer->execute([$newFormId, $emptySchema, Auth::id()]);

        $this->logAudit('create', 'forms', $newFormId, ['slug' => $validated['slug']]);

        Session::flash('success', 'Η φόρμα δημιουργήθηκε επιτυχώς ως Draft.');
        $this->redirect('/admin/forms/' . $newFormId . '/builder');
    }
    public function edit($params) {
        $id = (int)$params['id'];
        $form = Form::findById($id);
        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $roles = Role::getAll();
        $users = User::getAll();
        $roleAssignments = FormRoleAssignment::getAssignmentsByFormId($id);
        $userAssignments = FormUserAssignment::getAssignmentsByFormId($id);

        View::render('forms/edit', [
            'title' => 'Επεξεργασία Φόρμας: ' . $form['title'],
            'form' => $form,
            'roles' => $roles,
            'users' => $users,
            'roleAssignments' => $roleAssignments,
            'userAssignments' => $userAssignments
        ]);
    }

    public function updateForm($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];
        $data = Request::all();

        $validated = $this->validate($data, [
            'title' => ['required'],
            'slug' => ['required']
        ]);

        $db = Database::getInstance();

        // Check unique slug on other forms
        $chk = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?"); // just sample pattern, let's fix it for forms
        $chk = $db->prepare("SELECT COUNT(*) FROM forms WHERE slug = ? AND id != ?");
        $chk->execute([$validated['slug'], $id]);
        if ($chk->fetchColumn() > 0) {
            Session::flash('error', 'Υπάρχει ήδη άλλη φόρμα με αυτό το Slug.');
            $this->back();
            return;
        }

        $startsAt = !empty($data['submission_starts_at']) ? date('Y-m-d H:i:s', strtotime($data['submission_starts_at'])) : null;
        $expiresAt = !empty($data['submission_expires_at']) ? date('Y-m-d H:i:s', strtotime($data['submission_expires_at'])) : null;

        $maxSub = !empty($data['maximum_submissions']) ? (int)$data['maximum_submissions'] : null;

        // Validate that terms content is not empty if require_terms_acceptance is enabled
        if (isset($data['require_terms_acceptance']) && empty(trim($data['terms_content'] ?? ''))) {
            Session::flash('error', 'Πρέπει να ορίσετε το περιεχόμενο των όρων χρήσης (Terms Content) όταν απαιτείται αποδοχή όρων.');
            $this->back();
            return;
        }

        // Validate anonymous form identity tags protection
        if (isset($data['is_anonymous'])) {
            $latestVer = FormVersion::getLatestVersion($id);
            if ($latestVer) {
                $schema = json_decode($latestVer['schema_json'], true);
                $incompatible = [];
                if (isset($schema['sections']) && is_array($schema['sections'])) {
                    foreach ($schema['sections'] as $sec) {
                        if (isset($sec['fields']) && is_array($sec['fields'])) {
                            foreach ($sec['fields'] as $f) {
                                if (!empty($f['system_prefill_enabled']) && !empty($f['system_prefill_tag'])) {
                                    if (\App\Services\SystemPrefillResolver::isIdentityTag($f['system_prefill_tag'])) {
                                        $incompatible[] = $f['label'] . ' (' . $f['system_prefill_tag'] . ')';
                                    }
                                }
                            }
                        }
                    }
                }
                if (!empty($incompatible)) {
                    Session::flash('error', 'Δεν είναι δυνατή η ενεργοποίηση της Ανώνυμης Υποβολής διότι η φόρμα περιέχει πεδία συστήματος με στοιχεία ταυτότητας χρήστη: ' . implode(', ', $incompatible));
                    $this->back();
                    return;
                }
            }
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                UPDATE forms 
                SET title = ?, slug = ?, description = ?, is_active = ?, allow_drafts = ?,
                    form_mode = ?, submission_access_mode = ?, is_anonymous = ?, survey_analytics = ?, survey_charts_pdf = ?,
                    submission_starts_at = ?, submission_expires_at = ?, expiration_message = ?, single_submission_enabled = ?, daily_submission_enabled = ?,
                    maximum_submissions = ?, capacity_closed_message = ?,
                    is_public = ?, require_terms_acceptance = ?, terms_link_label = ?, terms_checkbox_label = ?, terms_content = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $validated['title'],
                strtolower($validated['slug']),
                $data['description'] ?? '',
                isset($data['is_active']) ? 1 : 0,
                isset($data['allow_drafts']) ? 1 : 0,
                $data['form_mode'] ?? 'workflow',
                $data['submission_access_mode'] ?? 'all_authenticated',
                isset($data['is_anonymous']) ? 1 : 0,
                isset($data['survey_analytics']) ? 1 : 0,
                isset($data['survey_charts_pdf']) ? 1 : 0,
                $startsAt,
                $expiresAt,
                $data['expiration_message'] ?? null,
                isset($data['single_submission_enabled']) ? 1 : 0,
                isset($data['daily_submission_enabled']) ? 1 : 0,
                $maxSub,
                $data['capacity_closed_message'] ?? null,
                isset($data['is_public']) ? 1 : 0,
                isset($data['require_terms_acceptance']) ? 1 : 0,
                !empty($data['terms_link_label']) ? $data['terms_link_label'] : 'Δείτε τους όρους χρήσης',
                !empty($data['terms_checkbox_label']) ? $data['terms_checkbox_label'] : 'Έχω διαβάσει και αποδέχομαι τους όρους χρήσης της φόρμας.',
                $data['terms_content'] ?? null,
                $id
            ]);

            // Sync submission permissions (roles/users) based on the submission_access_mode
            $subMode = $data['submission_access_mode'] ?? 'all_authenticated';
            if ($subMode === 'selected_roles') {
                $db->prepare("DELETE FROM form_role_assignments WHERE form_id = ?")->execute([$id]);
                if (isset($data['submission_roles']) && is_array($data['submission_roles'])) {
                    $insRole = $db->prepare("INSERT INTO form_role_assignments (form_id, role_id, can_view, can_submit, created_by) VALUES (?, ?, 1, 1, ?)");
                    foreach ($data['submission_roles'] as $roleId) {
                        $insRole->execute([$id, (int)$roleId, Auth::id()]);
                    }
                }
            } elseif ($subMode === 'selected_users') {
                $db->prepare("DELETE FROM form_user_assignments WHERE form_id = ?")->execute([$id]);
                if (isset($data['submission_users']) && is_array($data['submission_users'])) {
                    $insUser = $db->prepare("INSERT INTO form_user_assignments (form_id, user_id, can_view, can_submit, created_by) VALUES (?, ?, 1, 1, ?)");
                    foreach ($data['submission_users'] as $userId) {
                        $insUser->execute([$id, (int)$userId, Auth::id()]);
                    }
                }
            }

            // Generate secure public token atomically if is_public is enabled and no token exists
            if (isset($data['is_public'])) {
                $checkToken = $db->prepare("SELECT public_token FROM forms WHERE id = ?");
                $checkToken->execute([$id]);
                $currentToken = $checkToken->fetchColumn();
                if (empty($currentToken)) {
                    $newToken = bin2hex(random_bytes(24));
                    $db->prepare("UPDATE forms SET public_token = ? WHERE id = ?")->execute([$newToken, $id]);
                }
            }

            $db->commit();
            $this->logAudit('update', 'forms', $id, ['slug' => $validated['slug']]);
            Session::flash('success', 'Τα στοιχεία της φόρμας ενημερώθηκαν επιτυχώς.');
        } catch (\Exception $e) {
            $db->rollBack();
            Session::flash('error', 'Σφάλμα κατά την ενημέρωση της φόρμας: ' . $e->getMessage());
        }
        $this->redirect('/admin/forms');
    }

    public function builder($params) {
        $id = (int)$params['id'];
        $form = Form::findById($id);
        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $latestVer = FormVersion::getLatestVersion($id);
        $schema = $latestVer ? json_decode($latestVer['schema_json'], true) : null;
        $repositories = Repository::getActive();

        View::render('forms/builder', [
            'title' => 'Σχεδιασμός Φόρμας: ' . $form['title'],
            'form' => $form,
            'schema' => $schema,
            'repositories' => $repositories
        ]);
    }

    public function saveSchema($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];
        $data = Request::all();

        $form = Form::findById($id);
        if (!$form) {
            http_response_code(404);
            echo json_encode(['error' => 'Form not found']);
            exit;
        }

        $schemaRaw = $data['schema_json'] ?? '';
        
        // 1. Verify valid JSON schema
        $schema = json_decode($schemaRaw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($schema)) {
            echo json_encode(['error' => 'Μη έγκυρη μορφή JSON.']);
            exit;
        }

        // 2. Perform server-side validation checks on Schema structure
        $validationError = $this->validateSchemaStructure($schema);
        if ($validationError) {
            echo json_encode(['error' => $validationError]);
            exit;
        }

        // Validate anonymous form identity tags protection on schema save
        if (!empty($form['is_anonymous']) && isset($schema['sections']) && is_array($schema['sections'])) {
            $incompatible = [];
            foreach ($schema['sections'] as $sec) {
                if (isset($sec['fields']) && is_array($sec['fields'])) {
                    foreach ($sec['fields'] as $f) {
                        if (!empty($f['system_prefill_enabled']) && !empty($f['system_prefill_tag'])) {
                            if (\App\Services\SystemPrefillResolver::isIdentityTag($f['system_prefill_tag'])) {
                                $incompatible[] = $f['label'] . ' (' . $f['system_prefill_tag'] . ')';
                            }
                        }
                    }
                }
            }
            if (!empty($incompatible)) {
                echo json_encode(['error' => 'Δεν είναι δυνατή η αποθήκευση του σχεδίου διότι η Ανώνυμη Υποβολή είναι ενεργή και η φόρμα περιέχει πεδία συστήματος με στοιχεία ταυτότητας χρήστη: ' . implode(', ', $incompatible)]);
                exit;
            }
        }

        // Circular Dependency Validation
        $allFields = [];
        foreach ($schema['sections'] as $sec) {
            if (isset($sec['fields']) && is_array($sec['fields'])) {
                foreach ($sec['fields'] as $f) {
                    $allFields[] = $f;
                }
            }
        }
        $circularError = \App\Services\ConditionalLogicService::checkCircularDependencies($allFields);
        if ($circularError) {
            echo json_encode(['error' => $circularError]);
            exit;
        }

        $db = Database::getInstance();

        // If form is published, editing saves a new draft version
        $db->beginTransaction();
        try {
            $latestVer = FormVersion::getLatestVersion($id);
            $newVerNum = $latestVer ? $latestVer['version_number'] : 1;

            if ($form['status'] === 'published') {
                // If currently published, create next draft version
                $newVerNum++;
                $ins = $db->prepare("
                    INSERT INTO form_versions (form_id, version_number, schema_json, created_by)
                    VALUES (?, ?, ?, ?)
                ");
                $ins->execute([$id, $newVerNum, $schemaRaw, Auth::id()]);

                // Update current draft version flag on form, but keep status 'draft' for the new draft
                $upd = $db->prepare("UPDATE forms SET current_version = ?, status = 'draft' WHERE id = ?");
                $upd->execute([$newVerNum, $id]);
            } else {
                // If it is draft, we overwrite/update the latest version row
                $updVer = $db->prepare("UPDATE form_versions SET schema_json = ? WHERE form_id = ? AND version_number = ?");
                $updVer->execute([$schemaRaw, $id, $newVerNum]);
            }

            $db->commit();
            $this->logAudit('save_builder_schema', 'forms', $id, ['version' => $newVerNum]);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Σφάλμα βάσης: ' . $e->getMessage()]);
        }
        exit;
    }

    public function publish($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];

        $form = Form::findById($id);
        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $db = Database::getInstance();
        $latestVer = FormVersion::getLatestVersion($id);

        if (!$latestVer) {
            Session::flash('error', 'Δεν βρέθηκε έκδοση φόρμας για δημοσίευση.');
            $this->back();
        }

        $db->beginTransaction();
        try {
            // Update form status to published
            $stmt = $db->prepare("UPDATE forms SET status = 'published', current_version = ? WHERE id = ?");
            $stmt->execute([$latestVer['version_number'], $id]);

            $db->commit();
            $this->logAudit('publish', 'forms', $id, ['version' => $latestVer['version_number']]);
            Session::flash('success', 'Η φόρμα δημοσιεύθηκε με επιτυχία!');
        } catch (\Exception $e) {
            $db->rollBack();
            Session::flash('error', 'Σφάλμα δημοσίευσης: ' . $e->getMessage());
        }

        $this->back();
    }

    public function archive($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];

        $form = Form::findById($id);
        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE forms SET status = 'archived' WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAudit('archive', 'forms', $id, []);
        Session::flash('success', 'Η φόρμα αρχειοθετήθηκε.');
        $this->back();
    }

    public function delete($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];

        $form = Form::findById($id);
        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        // Prevent deletion of published form with submissions
        if ($form['status'] === 'published' && Form::hasSubmissions($id)) {
            Session::flash('error', 'Δεν μπορείτε να διαγράψετε δημοσιευμένη φόρμα με υποβολές.');
            $this->back();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM forms WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAudit('delete', 'forms', $id, ['title' => $form['title']]);
        Session::flash('success', 'Η φόρμα διαγράφηκε.');
        $this->redirect('/admin/forms');
    }

    public function duplicateForm($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];

        $form = Form::findById($id);
        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $db = Database::getInstance();

        // Generate clean unique slug
        $baseSlug = $form['slug'];
        $slug = $baseSlug . '-copy';
        $index = 1;
        while (true) {
            $chk = $db->prepare("SELECT COUNT(*) FROM forms WHERE slug = ?");
            $chk->execute([$slug]);
            if ($chk->fetchColumn() == 0) {
                break;
            }
            $slug = $baseSlug . '-copy-' . $index;
            $index++;
        }

        $db->beginTransaction();
        try {
            // Copy form main details row
            $ins = $db->prepare("
                INSERT INTO forms (title, slug, description, current_version, status, is_active, allow_drafts,
                                  form_mode, is_anonymous, survey_analytics, survey_charts_pdf,
                                  submission_starts_at, submission_expires_at, expiration_message,
                                  single_submission_enabled, daily_submission_enabled, maximum_submissions, capacity_closed_message,
                                  is_public, require_terms_acceptance, terms_link_label, terms_checkbox_label, terms_content,
                                  public_token, created_by)
                VALUES (?, ?, ?, 1, 'draft', 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?)
            ");
            $ins->execute([
                $form['title'] . ' (Αντίγραφο)',
                $slug,
                $form['description'] ?? '',
                $form['allow_drafts'] ?? 1,
                $form['form_mode'] ?? 'workflow',
                $form['is_anonymous'] ?? 0,
                $form['survey_analytics'] ?? 0,
                $form['survey_charts_pdf'] ?? 0,
                $form['submission_starts_at'] ?? null,
                $form['submission_expires_at'] ?? null,
                $form['expiration_message'] ?? null,
                $form['single_submission_enabled'] ?? 0,
                $form['daily_submission_enabled'] ?? 0,
                $form['maximum_submissions'] ?? null,
                $form['capacity_closed_message'] ?? null,
                $form['is_public'] ?? 0,
                $form['require_terms_acceptance'] ?? 0,
                $form['terms_link_label'] ?? null,
                $form['terms_checkbox_label'] ?? null,
                $form['terms_content'] ?? null,
                Auth::id()
            ]);

            $newFormId = $db->lastInsertId();

            // Fetch the schema contents from current version
            $ver = FormVersion::getVersion($form['id'], $form['current_version']);
            $schemaRaw = $ver ? $ver['schema_json'] : json_encode([
                'schemaVersion' => 1,
                'settings' => ['submitLabel' => 'Υποβολή', 'draftLabel' => 'Αποθήκευση ως πρόχειρο', 'allowDraft' => true],
                'sections' => []
            ]);

            // Copy schema version record
            $insVer = $db->prepare("
                INSERT INTO form_versions (form_id, version_number, schema_json, created_by)
                VALUES (?, 1, ?, ?)
            ");
            $insVer->execute([$newFormId, $schemaRaw, Auth::id()]);

            $db->commit();
            $this->logAudit('duplicate', 'forms', $id, ['new_form_id' => $newFormId, 'new_slug' => $slug]);
            Session::flash('success', 'Η φόρμα αντιγράφηκε επιτυχώς.');
        } catch (\Exception $e) {
            $db->rollBack();
            Session::flash('error', 'Σφάλμα κατά την αντιγραφή της φόρμας: ' . $e->getMessage());
        }

        $this->redirect('/admin/forms');
    }

    private function validateSchemaStructure($schema): ?string {
        if (!isset($schema['sections']) || !is_array($schema['sections'])) {
            return 'Το schema πρέπει να περιέχει έναν πίνακα "sections".';
        }

        $fieldKeys = [];
        foreach ($schema['sections'] as $sec) {
            if (!isset($sec['id']) || !isset($sec['title'])) {
                return 'Κάθε Section πρέπει να έχει "id" και "title".';
            }

            if (isset($sec['fields']) && is_array($sec['fields'])) {
                foreach ($sec['fields'] as $f) {
                    if (!isset($f['id']) || !isset($f['key']) || !isset($f['type']) || !isset($f['label'])) {
                        return 'Κάθε Field πρέπει να έχει "id", "key", "type" και "label".';
                    }

                    // Check duplicate field key
                    if (in_array($f['key'], $fieldKeys)) {
                        return 'Διπλότυπο κλειδί πεδίου (Duplicate Field Key): ' . htmlspecialchars($f['key']);
                    }
                    $fieldKeys[] = $f['key'];

                    // Check repository existence if repository source
                    if (isset($f['dataSource']) && $f['dataSource'] === 'repository' && !empty($f['repositoryId'])) {
                        $repo = Repository::findById((int)$f['repositoryId']);
                        if (!$repo || !$repo['is_active']) {
                            return 'Το Repository με ID ' . htmlspecialchars($f['repositoryId']) . ' δεν υπάρχει ή είναι ανενεργό.';
                        }
                    }
                }
            }
        }
        return null;
    }

    public function showPublicForm($params) {
        $slug = $params['slug'];
        $form = is_numeric($slug) ? Form::findById((int)$slug) : Form::findBySlug($slug);
        
        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        if ($form['status'] !== 'published') {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        $latestVer = FormVersion::getVersion($form['id'], $form['current_version']);
        if (!$latestVer) {
            die('Σφάλμα: Δεν βρέθηκε δημοσιευμένο schema.');
        }

        $schema = json_decode($latestVer['schema_json'], true);
        $repositories = [];
        // Load dynamic dropdown and multi-checkbox repository contents
        if (isset($schema['sections']) && is_array($schema['sections'])) {
            foreach ($schema['sections'] as $sec) {
                if (isset($sec['fields']) && is_array($sec['fields'])) {
                    foreach ($sec['fields'] as $f) {
                        if (in_array($f['type'], ['select', 'radio', 'checkbox']) && isset($f['dataSource']) && $f['dataSource'] === 'repository' && !empty($f['repositoryId'])) {
                            $repo = Repository::findById((int)$f['repositoryId']);
                            if ($repo) {
                                $repositories[$f['repositoryId']] = json_decode($repo['data_json'], true);
                            }
                        }
                    }
                }
            }
        }

        // Try to load any existing draft/returned submission for this user
        $db = Database::getInstance();

        // 1. Enforce availability and restriction constraints (Bypass for admin)
        $isAdmin = (Auth::role() === 'administrator');
        $layout = !empty($params['publicLayout']) ? 'public' : 'app';

        if (!$isAdmin) {
            $statusCheck = \App\Services\FormAvailabilityService::checkAvailability($form, Auth::id());
            if ($statusCheck !== 'available') {
                http_response_code(403);
                $greekMsg = \App\Services\FormAvailabilityService::getGreekMessage($statusCheck, $form);
                if ($statusCheck === 'already_submitted_today') {
                    $todaySubmission = \App\Services\FormAvailabilityService::getTodaySubmission((int)$form['id'], (int)Auth::id());
                    $pendingRequest = null;
                    if ($todaySubmission) {
                        $pendingStmt = $db->prepare("SELECT * FROM submission_correction_requests WHERE submission_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
                        $pendingStmt->execute([$todaySubmission['id']]);
                        $pendingRequest = $pendingStmt->fetch(PDO::FETCH_ASSOC) ?: null;
                    }
                    View::render('forms/submission-limited', [
                        'title' => 'Ημερήσιος περιορισμός υποβολής',
                        'message' => $greekMsg,
                        'form' => $form,
                        'submission' => $todaySubmission,
                        'pendingRequest' => $pendingRequest,
                    ], $layout);
                    exit;
                }
                if ($layout === 'public') {
                    // Render error inside standalone public layout cleanly
                    View::render('errors/public_error', [
                        'title' => 'Μη Διαθέσιμη Φόρμα',
                        'message' => $greekMsg
                    ], 'public');
                    exit;
                }
                die('<div style="font-family:sans-serif; text-align:center; padding:50px;"><h2>' . htmlspecialchars($greekMsg) . '</h2></div>');
            }
        }

        $stmtSub = $db->prepare("
            SELECT * FROM form_submissions 
            WHERE form_id = ? AND user_id = ? AND status IN ('draft', 'returned') 
            ORDER BY updated_at DESC LIMIT 1
        ");
        $stmtSub->execute([$form['id'], Auth::id()]);
        $existingSub = $stmtSub->fetch(PDO::FETCH_ASSOC);

        $answers = [];
        $submissionUuid = '';
        if ($existingSub) {
            $answers = json_decode($existingSub['data_json'], true) ?: [];
            $submissionUuid = $existingSub['uuid'];
        }

        // Apply system prefill values for fields that are enabled and not already populated in the draft
        if (isset($schema['sections']) && is_array($schema['sections'])) {
            foreach ($schema['sections'] as $sec) {
                if (isset($sec['fields']) && is_array($sec['fields'])) {
                    foreach ($sec['fields'] as $f) {
                        if (!empty($f['system_prefill_enabled']) && !empty($f['system_prefill_tag'])) {
                            if (!isset($answers[$f['key']]) || $answers[$f['key']] === '') {
                                $resolvedVal = \App\Services\SystemPrefillResolver::resolve($f['system_prefill_tag'], $form);
                                if ($resolvedVal !== null && $resolvedVal !== '') {
                                    // For Select and Radio, only prefill if the resolved value matches an option value
                                    if (in_array($f['type'], ['select', 'radio'])) {
                                        $options = $f['options'] ?? [];
                                        $found = false;
                                        foreach ($options as $opt) {
                                            if ((string)($opt['value']) === (string)($resolvedVal)) {
                                                $found = true;
                                                break;
                                            }
                                        }
                                        if ($found) {
                                            $answers[$f['key']] = $resolvedVal;
                                        } else {
                                            error_log("Validation Warning: Prefill tag value '{$resolvedVal}' does not match any choice in field '{$f['key']}'");
                                        }
                                    } else {
                                        $answers[$f['key']] = $resolvedVal;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        View::render('forms/view', [
            'title' => $form['title'],
            'form' => $form,
            'schema' => $schema,
            'repositories' => $repositories,
            'versionId' => $latestVer['id'],
            'answers' => $answers,
            'submissionUuid' => $submissionUuid,
            'isPublicView' => ($layout === 'public')
        ], $layout);
    }

    public function showPublicFormByToken($params) {
        $token = $params['token'];
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM forms WHERE public_token = ?");
        $stmt->execute([$token]);
        $form = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$form || empty($form['is_public'])) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        // Forward to normal public form display using token lookup bypass, passing publicLayout flag
        $this->showPublicForm(array_merge($params, ['publicLayout' => true]));
    }

    public function regeneratePublicToken($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];

        if (!Auth::hasPermission('forms.manage')) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        $db = Database::getInstance();
        $newToken = bin2hex(random_bytes(24));

        $stmt = $db->prepare("UPDATE forms SET public_token = ? WHERE id = ?");
        $stmt->execute([$newToken, $id]);

        $this->logAudit('regenerate_token', 'forms', $id, ['action' => 'regenerate_public_token']);

        Session::flash('success', 'Ο δημόσιος σύνδεσμος ανανεώθηκε επιτυχώς.');
        $this->back();
    }

    public function showPublicSuccess($params) {
        $title = $_GET['title'] ?? 'Φόρμα';
        View::render('forms/public_success', [
            'title' => $title,
            'message' => 'Η υποβολή σας καταχωρήθηκε με επιτυχία στο σύστημα. Ευχαριστούμε για τη συμμετοχή σας!'
        ], 'public');
    }

    public function adminPreview($params) {
        $id = (int)$params['id'];
        $form = Form::findById($id);

        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        if (!Auth::hasPermission('forms.manage')) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        $latestVer = FormVersion::getLatestVersion($id);
        if (!$latestVer) {
            die('Σφάλμα: Δεν βρέθηκε schema για αυτή τη φόρμα.');
        }

        $schema = json_decode($latestVer['schema_json'], true);
        $repositories = [];

        // Load repository dynamic sources
        if (isset($schema['sections']) && is_array($schema['sections'])) {
            foreach ($schema['sections'] as $sec) {
                if (isset($sec['fields']) && is_array($sec['fields'])) {
                    foreach ($sec['fields'] as $f) {
                        if (in_array($f['type'], ['select', 'radio', 'checkbox']) && isset($f['dataSource']) && $f['dataSource'] === 'repository' && !empty($f['repositoryId'])) {
                            $repo = Repository::findById((int)$f['repositoryId']);
                            if ($repo) {
                                $repositories[$f['repositoryId']] = json_decode($repo['data_json'], true);
                            }
                        }
                    }
                }
            }
        }

        // Apply system prefill values for fields that are enabled
        $answers = [];
        if (isset($schema['sections']) && is_array($schema['sections'])) {
            foreach ($schema['sections'] as $sec) {
                if (isset($sec['fields']) && is_array($sec['fields'])) {
                    foreach ($sec['fields'] as $f) {
                        if (!empty($f['system_prefill_enabled']) && !empty($f['system_prefill_tag'])) {
                            $resolvedVal = \App\Services\SystemPrefillResolver::resolve($f['system_prefill_tag'], $form);
                            if ($resolvedVal !== null && $resolvedVal !== '') {
                                if (in_array($f['type'], ['select', 'radio'])) {
                                    $options = $f['options'] ?? [];
                                    $found = false;
                                    foreach ($options as $opt) {
                                        if ((string)($opt['value']) === (string)($resolvedVal)) {
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if ($found) {
                                        $answers[$f['key']] = $resolvedVal;
                                    }
                                } else {
                                    $answers[$f['key']] = $resolvedVal;
                                }
                            }
                        }
                    }
                }
            }
        }

        View::render('forms/view', [
            'title' => $form['title'],
            'form' => $form,
            'schema' => $schema,
            'repositories' => $repositories,
            'versionId' => $latestVer['id'],
            'answers' => $answers,
            'submissionUuid' => '',
            'isAdminPreview' => true
        ]);
    }

    public function export($params) {
        $id = (int)$params['id'];
        $form = Form::findById($id);
        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        if (!Auth::hasPermission('forms.manage')) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        $db = Database::getInstance();

        // Get all versions
        $stmt = $db->prepare("SELECT * FROM form_versions WHERE form_id = ? ORDER BY version_number ASC");
        $stmt->execute([$id]);
        $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get notifications
        $stmt = $db->prepare("SELECT * FROM form_notifications WHERE form_id = ?");
        $stmt->execute([$id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get workflow assignments/steps if any
        $workflowSteps = [];
        if (!empty($form['active_workflow_definition_id'])) {
            $stmt = $db->prepare("SELECT * FROM workflow_steps WHERE workflow_definition_id = ? ORDER BY step_order ASC");
            $stmt->execute([$form['active_workflow_definition_id']]);
            $workflowSteps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $exportData = [
            'form' => $form,
            'versions' => $versions,
            'notifications' => $notifications,
            'workflowSteps' => $workflowSteps,
            'exported_at' => date('Y-m-d H:i:s')
        ];

        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        $filename = 'form-export-' . $form['slug'] . '-' . date('YmdHis') . '.json';
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));
        echo $json;
        exit;
    }
}
