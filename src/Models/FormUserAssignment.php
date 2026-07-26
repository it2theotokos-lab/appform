<?php
namespace App\Models;

use App\Core\Model;

class FormUserAssignment extends Model {
    public static function getAssignmentsByFormId(int $formId): array {
        return self::fetchAll("SELECT * FROM form_user_assignments WHERE form_id = ?", [$formId]);
    }
}
