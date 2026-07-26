<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class AnalyticsService {
    public static function getSubmissionStats(int $formId): array {
        $db = Database::getInstance();

        // 1. Get totals by status
        $stmt = $db->prepare("
            SELECT status, COUNT(*) as count 
            FROM form_submissions 
            WHERE form_id = ? 
            GROUP BY status
        ");
        $stmt->execute([$formId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [
            'total' => 0,
            'draft' => 0,
            'submitted' => 0,
            'under_review' => 0,
            'approved' => 0,
            'rejected' => 0,
            'returned' => 0,
            'cancelled' => 0
        ];

        foreach ($rows as $r) {
            $stats[$r['status']] = (int)$r['count'];
            $stats['total'] += (int)$r['count'];
        }

        return $stats;
    }

    public static function getFieldAnalysis(int $formId, string $fieldKey, array $fieldConf): array {
        $db = Database::getInstance();

        // Fetch submissions data
        $stmt = $db->prepare("SELECT data_json FROM form_submissions WHERE form_id = ? AND status != 'draft'");
        $stmt->execute([$formId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalSubmissions = count($rows);
        $values = [];

        foreach ($rows as $row) {
            $data = json_decode($row['data_json'], true);
            if (isset($data[$fieldKey]) && $data[$fieldKey] !== '') {
                $values[] = $data[$fieldKey];
            }
        }

        $analysis = [
            'total_responses' => count($values),
            'missing_responses' => $totalSubmissions - count($values),
            'type' => $fieldConf['type']
        ];

        // Perform analysis per type
        if (in_array($fieldConf['type'], ['select', 'radio', 'checkbox', 'yes_no', 'rating'])) {
            $counts = [];
            foreach ($values as $val) {
                if (is_array($val)) {
                    foreach ($val as $subVal) {
                        $counts[$subVal] = ($counts[$subVal] ?? 0) + 1;
                    }
                } else {
                    $counts[$val] = ($counts[$val] ?? 0) + 1;
                }
            }

            $options = $fieldConf['options'] ?? [];
            if ($fieldConf['type'] === 'yes_no') {
                $options = [
                    ['label' => 'Ναι', 'value' => 'yes'],
                    ['label' => 'Όχι', 'value' => 'no']
                ];
            } elseif ($fieldConf['type'] === 'rating') {
                $options = [];
                for ($i = 1; $i <= 5; $i++) {
                    $options[] = ['label' => "$i Αστέρια", 'value' => (string)$i];
                }
                
                // Add average calculation for rating fields
                if (count($values) > 0) {
                    $numericRatings = array_filter(array_map('floatval', $values));
                    $analysis['avg'] = count($numericRatings) > 0 ? round(array_sum($numericRatings) / count($numericRatings), 2) : 0;
                } else {
                    $analysis['avg'] = 0;
                }
            } elseif ($fieldConf['type'] === 'checkbox' && empty($options)) {
                // If it is a single boolean checkbox
                $options = [
                    ['label' => ($fieldConf['placeholder'] ?? '') ?: 'Αποδέχομαι', 'value' => '1']
                ];
            }
            $distribution = [];

            foreach ($options as $opt) {
                $val = $opt['value'];
                $cnt = $counts[$val] ?? 0;
                $distribution[] = [
                    'label' => $opt['label'],
                    'value' => $val,
                    'count' => $cnt,
                    'percentage' => $totalSubmissions > 0 ? round(($cnt / $totalSubmissions) * 100, 1) : 0
                ];
            }
            $analysis['distribution'] = $distribution;

        } elseif ($fieldConf['type'] === 'number') {
            if (count($values) > 0) {
                $analysis['sum'] = array_sum($values);
                $analysis['avg'] = round(array_sum($values) / count($values), 2);
                $analysis['min'] = min($values);
                $analysis['max'] = max($values);
            } else {
                $analysis['sum'] = 0;
                $analysis['avg'] = 0;
                $analysis['min'] = 0;
                $analysis['max'] = 0;
            }
        }

        return $analysis;
    }
}
