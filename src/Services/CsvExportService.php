<?php
namespace App\Services;

class CsvExportService {
    public static function export(array $headers, array $rows, string $delimiter = ';'): string {
        $output = fopen('php://temp', 'r+');

        // Add UTF-8 BOM for Microsoft Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        // Write Headers
        fputcsv($output, $headers, $delimiter);

        // Write Rows (with CSV Injection protection)
        foreach ($rows as $row) {
            $cleanedRow = array_map(function($val) {
                if (is_string($val)) {
                    // CSV Injection protection (sanitize strings starting with =, +, -, @)
                    if (str_starts_with($val, '=') || str_starts_with($val, '+') || str_starts_with($val, '-') || str_starts_with($val, '@')) {
                        return "'" . $val;
                    }
                }
                return $val;
            }, $row);

            fputcsv($output, $cleanedRow, $delimiter);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }
}
