<?php
namespace App\Models;

use App\Core\Model;

class SubmissionFile extends Model {
    public static function findById(int $id) {
        return self::fetch("SELECT * FROM submission_files WHERE id = ?", [$id]);
    }

    public static function getFilesBySubmissionId(int $submissionId): array {
        return self::fetchAll("SELECT * FROM submission_files WHERE submission_id = ?", [$submissionId]);
    }
}
