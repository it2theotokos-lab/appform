<?php
namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

class ImportService {

    public static function import(string $entity, array $rows, string $strategy, string $matchingField, bool $dryRun = false, int $userId = 1): array {
        $db = Database::getInstance();
        $db->beginTransaction();

        $total = count($rows);
        $success = 0;
        $failed = 0;
        $skipped = 0;
        $errors = [];

        try {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 1;
                $matchingVal = $row[$matchingField] ?? null;

                // Basic validation
                $validationError = self::validateRow($entity, $row);
                if ($validationError) {
                    $failed++;
                    $errors[] = [
                        'row' => $rowNum,
                        'identifier' => $matchingVal,
                        'message' => $validationError
                    ];
                    if ($strategy === 'stop') {
                        throw new Exception("Import stopped due to validation error on row $rowNum: $validationError");
                    }
                    continue;
                }

                // Check for duplicates
                $existing = null;
                if ($matchingVal !== null && $matchingField !== '') {
                    $existing = self::findExisting($entity, $matchingField, $matchingVal);
                }

                if ($existing) {
                    if ($strategy === 'skip') {
                        $skipped++;
                        continue;
                    } elseif ($strategy === 'stop') {
                        throw new Exception("Duplicate record detected on row $rowNum for field '$matchingField' = '$matchingVal'");
                    } elseif ($strategy === 'update') {
                        if (!$dryRun) {
                            self::updateRecord($entity, $existing['id'], $row);
                        }
                        $success++;
                    } else { // Create duplicate / default
                        if (!$dryRun) {
                            self::insertRecord($entity, $row);
                        }
                        $success++;
                    }
                } else {
                    if (!$dryRun) {
                        self::insertRecord($entity, $row);
                    }
                    $success++;
                }
            }

            if ($dryRun) {
                $db->rollBack();
            } else {
                $db->commit();
            }

            return [
                'status' => 'completed',
                'total' => $total,
                'success' => $success,
                'failed' => $failed,
                'skipped' => $skipped,
                'errors' => $errors
            ];

        } catch (Exception $e) {
            $db->rollBack();
            return [
                'status' => 'failed',
                'total' => $total,
                'success' => 0,
                'failed' => $total,
                'skipped' => 0,
                'errors' => [['row' => 0, 'identifier' => '', 'message' => $e->getMessage()]]
            ];
        }
    }

    private static function validateRow(string $entity, array $row): ?string {
        if ($entity === 'users') {
            if (empty($row['username'])) return "Το username είναι υποχρεωτικό.";
            if (empty($row['email'])) return "Το email είναι υποχρεωτικό.";
        }
        if ($entity === 'roles') {
            if (empty($row['name'])) return "Το όνομα είναι υποχρεωτικό.";
            if (empty($row['slug'])) return "Το slug είναι υποχρεωτικό.";
        }
        return null;
    }

    private static function findExisting(string $entity, string $field, $val) {
        $db = Database::getInstance();
        $allowedFields = ['id', 'uuid', 'username', 'email', 'slug', 'name', 'setting_key'];
        if (!in_array($field, $allowedFields)) {
            return null;
        }
        $stmt = $db->prepare("SELECT * FROM `$entity` WHERE `$field` = ? LIMIT 1");
        $stmt->execute([$val]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private static function insertRecord(string $entity, array $row) {
        $db = Database::getInstance();
        
        // Remove primary key if auto-increment is expected
        if (isset($row['id'])) {
            unset($row['id']);
        }

        // Safe User Passwords Check
        if ($entity === 'users' && empty($row['password_hash'])) {
            $row['password_hash'] = password_hash('ChangeMe!2025', PASSWORD_BCRYPT);
        }

        $columns = array_keys($row);
        $placeholders = array_fill(0, count($columns), '?');
        $query = "INSERT INTO `$entity` (" . implode(', ', array_map(fn($c) => "`$c`", $columns)) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $db->prepare($query);
        $stmt->execute(array_values($row));
    }

    private static function updateRecord(string $entity, int $id, array $row) {
        $db = Database::getInstance();
        if (isset($row['id'])) {
            unset($row['id']);
        }
        $updates = [];
        $values = [];
        foreach ($row as $col => $val) {
            $updates[] = "`$col` = ?";
            $values[] = $val;
        }
        $values[] = $id;

        $query = "UPDATE `$entity` SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute($values);
    }
}
