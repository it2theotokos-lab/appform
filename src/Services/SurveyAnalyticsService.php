<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class SurveyAnalyticsService {

    public static function getFormStats(int $formId, array $filters = []): array {
        $db = Database::getInstance();

        // Base submission retrieval
        $query = "SELECT fs.id, fs.data_json, fs.submitted_at, u.directory_department as department 
                  FROM form_submissions fs 
                  LEFT JOIN users u ON fs.user_id = u.id 
                  WHERE fs.form_id = :form_id AND fs.status = 'submitted'";
        
        $params = ['form_id' => $formId];

        if (!empty($filters['date_from'])) {
            $query .= " AND fs.submitted_at >= :date_from";
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $query .= " AND fs.submitted_at <= :date_to";
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['department'])) {
            $query .= " AND u.directory_department = :department";
            $params['department'] = $filters['department'];
        }

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Form Version Schema details
        $stmtSchema = $db->prepare("SELECT schema_json FROM form_versions WHERE form_id = ? ORDER BY version_number DESC LIMIT 1");
        $stmtSchema->execute([$formId]);
        $schemaJson = $stmtSchema->fetchColumn();
        $schema = json_decode($schemaJson, true) ?: [];

        $questionsStats = [];
        $sections = $schema['sections'] ?? [];

        foreach ($sections as $section) {
            $fields = $section['fields'] ?? [];
            foreach ($fields as $field) {
                $type = $field['type'] ?? '';
                $key = $field['key'] ?? '';
                $label = $field['label'] ?? '';

                if (in_array($type, ['heading', 'divider', 'file', 'signature'])) {
                    continue;
                }

                $responses = [];
                $skipped = 0;

                foreach ($submissions as $sub) {
                    $data = json_decode($sub['data_json'], true) ?: [];
                    $val = $data[$key] ?? null;

                    if ($val === null || $val === '') {
                        $skipped++;
                    } else {
                        if (is_array($val)) {
                            foreach ($val as $v) {
                                $responses[] = $v;
                            }
                        } else {
                            $responses[] = $val;
                        }
                    }
                }

                $stats = [
                    'key' => $key,
                    'label' => $label,
                    'type' => $type,
                    'total_responses' => count($responses),
                    'skipped' => $skipped,
                    'distribution' => [],
                    'average' => null,
                    'median' => null,
                    'min' => null,
                    'max' => null,
                    'text_responses' => []
                ];

                // Calculate distributions or numeric metrics based on field type
                if (in_array($type, ['select', 'radio', 'checkbox', 'yes_no'])) {
                    $counts = array_count_values($responses);
                    $dist = [];
                    foreach ($counts as $k => $c) {
                        $dist[] = [
                            'value' => $k,
                            'count' => $c,
                            'percentage' => count($responses) > 0 ? round(($c / count($responses)) * 100, 1) : 0
                        ];
                    }
                    $stats['distribution'] = $dist;
                } elseif (in_array($type, ['number', 'range', 'rating'])) {
                    $numericValues = array_filter($responses, 'is_numeric');
                    if ($numericValues) {
                        $stats['min'] = min($numericValues);
                        $stats['max'] = max($numericValues);
                        $stats['average'] = round(array_sum($numericValues) / count($numericValues), 2);
                        
                        sort($numericValues);
                        $mid = floor(count($numericValues) / 2);
                        $stats['median'] = count($numericValues) % 2 ? $numericValues[$mid] : ($numericValues[$mid - 1] + $numericValues[$mid]) / 2;
                    }
                } else {
                    // text, textarea type
                    $stats['text_responses'] = array_slice($responses, 0, 50); // limit to first 50
                }

                $questionsStats[] = $stats;
            }
        }

        return [
            'total_submissions' => count($submissions),
            'questions' => $questionsStats
        ];
    }
}
