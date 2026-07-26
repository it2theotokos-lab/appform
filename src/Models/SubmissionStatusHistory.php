<?php
namespace App\Models;

use App\Core\Model;

class SubmissionStatusHistory extends Model {
    public static function getHistoryBySubmissionId(int $submissionId): array {
        return self::fetchAll("
            SELECT h.*, u.username as user_name, u.full_name as user_fullname 
            FROM submission_status_history h 
            LEFT JOIN users u ON h.changed_by = u.id 
            WHERE h.submission_id = ? 
            ORDER BY h.created_at DESC
        ", [$submissionId]);
    }
}
