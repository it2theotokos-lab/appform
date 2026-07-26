<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class DataExchangeService {
    
    // Supported entities array
    public static function getSupportedEntities(): array {
        return [
            'users' => 'Χρήστες',
            'roles' => 'Ρόλοι',
            'permissions' => 'Δικαιώματα',
            'repositories' => 'Repositories',
            'repository_items' => 'Στοιχεία Repositories',
            'forms' => 'Φόρμες',
            'form_versions' => 'Εκδόσεις Φορμών',
            'form_role_assignments' => 'Αναθέσεις Ρόλων σε Φόρμες',
            'form_user_assignments' => 'Αναθέσεις Χρηστών σε Φόρμες',
            'form_submissions' => 'Υποβολές Φορμών',
            'submission_files' => 'Μεταδεδομένα Αρχείων Υποβολών',
            'submission_status_history' => 'Ιστορικό Κατάστασης Υποβολών',
            'document_templates' => 'Πρότυπα Εγγράφων',
            'document_instances' => 'Παραστατικά / Έγγραφα',
            'workflow_definitions' => 'Ορισμοί Workflow',
            'workflow_steps' => 'Βήματα Workflow',
            'workflow_instances' => 'Υποθέσεις / Instances Workflow',
            'workflow_comments' => 'Σχόλια Workflow',
            'notifications' => 'Ειδοποιήσεις Χρηστών',
            'notification_templates' => 'Πρότυπα Email / Ειδοποιήσεων',
            'navigation_menus' => 'Μενού Πλοήγησης',
            'navigation_menu_items' => 'Στοιχεία Μενού Πλοήγησης',
            'saved_reports' => 'Αποθηκευμένες Αναφορές',
            'system_settings' => 'Ρυθμίσεις Συστήματος',
            'ldap_role_mappings' => 'Αντιστοιχίσεις LDAP Ρόλων',
            'plugins' => 'Πρόσθετα (Plugins)',
            'audit_logs' => 'Καταγραφές Ελέγχου (Audit Logs) [Μόνο Εξαγωγή]'
        ];
    }

    public static function getData(string $entity, array $filters = []): array {
        $db = Database::getInstance();
        $query = "";
        $params = [];

        switch ($entity) {
            case 'users':
                // Never export password hashes
                $query = "SELECT id, username, email, full_name, role_id, is_active, failed_login_attempts, locked_until, last_login_at, created_at, updated_at, authentication_provider, external_identifier, directory_dn, directory_department, last_directory_sync_at, manager_id FROM users";
                break;
            case 'roles':
                $query = "SELECT id, name, slug, description, is_system, created_at, updated_at FROM roles";
                break;
            case 'permissions':
                $query = "SELECT id, name, slug, description, created_at, updated_at FROM permissions";
                break;
            case 'repositories':
                $query = "SELECT id, name, slug, description, data_json, is_active, created_by, created_at, updated_at FROM repositories";
                break;
            case 'repository_items':
                // Synthesize from repositories table options list
                $stmt = $db->query("SELECT id, name, slug, data_json FROM repositories");
                $items = [];
                while ($repo = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $data = json_decode($repo['data_json'], true) ?: [];
                    foreach ($data as $d) {
                        $items[] = [
                            'repository_id' => $repo['id'],
                            'repository_slug' => $repo['slug'],
                            'repository_name' => $repo['name'],
                            'value' => $d['value'] ?? '',
                            'label' => $d['label'] ?? ''
                        ];
                    }
                }
                return $items;
            case 'forms':
                $query = "SELECT id, title, slug, description, current_version, status, is_active, allow_drafts, created_by, created_at, updated_at FROM forms";
                break;
            case 'form_versions':
                $query = "SELECT id, form_id, version_number, schema_json, created_by, created_at FROM form_versions";
                break;
            case 'form_role_assignments':
                $query = "SELECT id, form_id, role_id, can_view, can_submit, created_by, created_at FROM form_role_assignments";
                break;
            case 'form_user_assignments':
                $query = "SELECT id, form_id, user_id, can_view, can_submit, created_by, created_at FROM form_user_assignments";
                break;
            case 'form_submissions':
                $query = "SELECT id, uuid, form_id, form_version_id, user_id, data_json, status, submitted_at, reviewed_by, reviewed_at, review_notes, created_at, updated_at FROM form_submissions";
                break;
            case 'submission_files':
                $query = "SELECT id, submission_id, field_key, original_name, stored_name, mime_type, file_size, storage_path, uploaded_at, file_extension, checksum_sha256, uploaded_by FROM submission_files";
                break;
            case 'submission_status_history':
                $query = "SELECT id, submission_id, old_status, new_status, notes, changed_by, created_at FROM submission_status_history";
                break;
            case 'document_templates':
                $query = "SELECT id, name, file_path, status, created_at, updated_at FROM document_templates";
                break;
            case 'document_instances':
                $query = "SELECT id, template_id, template_version_id, user_id, current_step_id, status, file_path, signed_file_path, created_at, updated_at FROM document_instances";
                break;
            case 'workflow_definitions':
                $query = "SELECT id, name, description, is_active, created_by, created_at, updated_at FROM workflow_definitions";
                break;
            case 'workflow_steps':
                $query = "SELECT id, workflow_definition_id, name, step_type, role_id, user_id, step_order, created_at, updated_at FROM workflow_steps";
                break;
            case 'workflow_instances':
                $query = "SELECT id, workflow_definition_id, form_submission_id, document_instance_id, current_step_id, status, created_at, updated_at FROM workflow_instances";
                break;
            case 'workflow_comments':
                $query = "SELECT id, workflow_instance_id, user_id, comment, created_at FROM workflow_comments";
                break;
            case 'notifications':
                $query = "SELECT id, user_id, type, title, message, link_url, is_read, read_at, created_at FROM notifications";
                break;
            case 'notification_templates':
                $query = "SELECT id, slug, name, subject, body_html, body_text, is_active, created_at, updated_at FROM notification_templates";
                break;
            case 'navigation_menus':
                $query = "SELECT id, role_id, name, menu_structure_json, is_active, created_by, created_at, updated_at FROM navigation_menus";
                break;
            case 'navigation_menu_items':
                $query = "SELECT id, menu_id, parent_id, label, icon, item_type, route_name, url, form_id, permission_slug, sort_order, open_in_new_tab, is_active, created_at, updated_at FROM navigation_menu_items";
                break;
            case 'saved_reports':
                $query = "SELECT id, name, report_type, form_id, owner_user_id, filters_json, columns_json, is_shared, created_at, updated_at FROM saved_reports";
                break;
            case 'system_settings':
                // Never export sensitive settings in plaintext
                $query = "SELECT id, setting_key, 
                            CASE 
                                WHEN setting_key LIKE '%password%' OR setting_key LIKE '%secret%' OR setting_key LIKE '%key%' OR setting_key LIKE '%token%' THEN '[ENCRYPTED/HIDDEN]'
                                ELSE setting_value 
                            END as setting_value, 
                            setting_type, is_public, updated_by, updated_at FROM system_settings";
                break;
            case 'ldap_role_mappings':
                $query = "SELECT id, directory_group, appform_role_id FROM ldap_role_mappings";
                break;
            case 'plugins':
                $query = "SELECT id, name, `key`, version, is_enabled, settings_json, created_at, updated_at FROM plugins";
                break;
            case 'audit_logs':
                $query = "SELECT id, user_id, action, entity_type, entity_id, metadata_json, ip_address, user_agent, created_at FROM audit_logs";
                break;
            default:
                return [];
        }

        // Apply filters (dates, user etc.)
        $conditions = [];
        if (!empty($filters['date_from']) && self::hasColumn($entity, 'created_at')) {
            $conditions[] = "created_at >= :date_from";
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to']) && self::hasColumn($entity, 'created_at')) {
            $conditions[] = "created_at <= :date_to";
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['user_id']) && self::hasColumn($entity, 'user_id')) {
            $conditions[] = "user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['status']) && self::hasColumn($entity, 'status')) {
            $conditions[] = "status = :status";
            $params['status'] = $filters['status'];
        }

        if ($conditions) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function hasColumn(string $entity, string $col): bool {
        $cols = [
            'users' => ['created_at'],
            'roles' => ['created_at'],
            'permissions' => ['created_at'],
            'repositories' => ['created_at', 'created_by'],
            'forms' => ['created_at', 'status'],
            'form_versions' => ['created_at'],
            'form_role_assignments' => ['created_at'],
            'form_user_assignments' => ['created_at'],
            'form_submissions' => ['created_at', 'status', 'user_id'],
            'submission_files' => ['uploaded_at'],
            'submission_status_history' => ['created_at'],
            'document_templates' => ['created_at', 'status'],
            'document_instances' => ['created_at', 'status', 'user_id'],
            'workflow_definitions' => ['created_at'],
            'workflow_steps' => ['created_at'],
            'workflow_instances' => ['created_at', 'status'],
            'workflow_comments' => ['created_at', 'user_id'],
            'notifications' => ['created_at', 'user_id'],
            'notification_templates' => ['created_at'],
            'navigation_menus' => ['created_at'],
            'navigation_menu_items' => ['created_at'],
            'saved_reports' => ['created_at'],
            'system_settings' => ['updated_at'],
            'plugins' => ['created_at'],
            'audit_logs' => ['created_at', 'user_id']
        ];
        return isset($cols[$entity]) && in_array($col, $cols[$entity]);
    }
}
