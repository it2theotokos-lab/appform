<?php
namespace App\Models;

use App\Core\Model;

class FormRoleAssignment extends Model {
    public static function getAssignmentsByFormId(int $formId): array {
        return self::fetchAll("SELECT * FROM form_role_assignments WHERE form_id = ?", [$formId]);
    }
}
