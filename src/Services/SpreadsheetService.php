<?php
namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;

class SpreadsheetService {

    /**
     * Export data rows to CSV string (with UTF-8 BOM for Excel compatibility)
     */
    public static function exportCsv(array $headers, array $rows): string {
        $fp = fopen('php://temp', 'r+');
        // Add UTF-8 BOM
        fwrite($fp, "\xEF\xBB\xBF");

        fputcsv($fp, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $h) {
                $val = $row[$h] ?? '';
                if (is_array($val) || is_object($val)) {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                }
                $line[] = (string)$val;
            }
            fputcsv($fp, $line);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);
        return $csv;
    }

    /**
     * Export data rows to binary XLSX string via PhpSpreadsheet
     */
    public static function exportXlsx(array $headers, array $rows): string {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Write headers
        $colIndex = 1;
        foreach ($headers as $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
            $sheet->setCellValue("{$colLetter}1", $h);
        }

        // Write data
        $rowIndex = 2;
        foreach ($rows as $row) {
            $colIndex = 1;
            foreach ($headers as $h) {
                $val = $row[$h] ?? '';
                if (is_array($val) || is_object($val)) {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                }
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
                $sheet->setCellValue("{$colLetter}{$rowIndex}", (string)$val);
            }
            $rowIndex++;
        }

        $writer = new XlsxWriter($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    /**
     * Export data to formatted JSON string
     */
    public static function exportJson(array $headers, array $rows): string {
        $cleanRows = [];
        foreach ($rows as $row) {
            $cleanRow = [];
            foreach ($headers as $h) {
                $cleanRow[$h] = $row[$h] ?? '';
            }
            $cleanRows[] = $cleanRow;
        }
        return json_encode($cleanRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Parse CSV file into headers and data rows
     */
    public static function parseCsvFile(string $filePath): array {
        if (!file_exists($filePath)) {
            throw new \Exception(__('File not found for import.'));
        }

        $reader = new CsvReader();
        $reader->setInputEncoding('UTF-8');
        $reader->setDelimiter(',');
        $reader->setEnclosure('"');
        $reader->setSheetIndex(0);

        try {
            $spreadsheet = $reader->load($filePath);
        } catch (\Exception $e) {
            // Fallback to native fgetcsv if PhpSpreadsheet reader encounters encoding flags
            return self::parseCsvNative($filePath);
        }

        return self::extractRowsFromSpreadsheet($spreadsheet);
    }

    /**
     * Native fallback CSV parser
     */
    private static function parseCsvNative(string $filePath): array {
        $fp = fopen($filePath, 'r');
        if (!$fp) {
            throw new \Exception(__('Failed to open CSV file.'));
        }

        // Strip BOM if present
        $bom = fread($fp, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fp);
        }

        $headers = fgetcsv($fp, 0, ',');
        if (!$headers) {
            fclose($fp);
            return ['headers' => [], 'rows' => []];
        }

        $headers = array_map('trim', $headers);
        $rows = [];
        $lineNum = 2;

        while (($data = fgetcsv($fp, 0, ',')) !== false) {
            if (count($data) === 1 && trim((string)$data[0]) === '') {
                $lineNum++;
                continue; // empty line
            }
            $rowObj = ['_line' => $lineNum];
            foreach ($headers as $idx => $h) {
                if ($h === '') continue;
                $rowObj[$h] = isset($data[$idx]) ? trim((string)$data[$idx]) : '';
            }
            $rows[] = $rowObj;
            $lineNum++;
        }

        fclose($fp);
        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Parse XLSX file into headers and data rows
     */
    public static function parseXlsxFile(string $filePath): array {
        if (!file_exists($filePath)) {
            throw new \Exception(__('File not found for import.'));
        }

        $reader = new XlsxReader();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        return self::extractRowsFromSpreadsheet($spreadsheet);
    }

    /**
     * Parse JSON string or uploaded JSON file
     */
    public static function parseJsonContent(string $content): array {
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new \Exception(__('Invalid JSON content format. Must be an array of objects.'));
        }

        if (empty($decoded)) {
            return ['headers' => [], 'rows' => []];
        }

        // Gather all keys across items as headers
        $headers = [];
        foreach ($decoded as $item) {
            if (is_array($item)) {
                foreach (array_keys($item) as $k) {
                    if (!in_array((string)$k, $headers, true)) {
                        $headers[] = (string)$k;
                    }
                }
            }
        }

        $rows = [];
        $lineNum = 1;
        foreach ($decoded as $item) {
            if (!is_array($item)) continue;
            $rowObj = ['_line' => $lineNum];
            foreach ($headers as $h) {
                $val = $item[$h] ?? '';
                if (is_array($val) || is_object($val)) {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                }
                $rowObj[$h] = (string)$val;
            }
            $rows[] = $rowObj;
            $lineNum++;
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Extract structured headers and data rows from a PhpSpreadsheet instance
     */
    private static function extractRowsFromSpreadsheet(Spreadsheet $spreadsheet): array {
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        if ($highestRow < 1) {
            return ['headers' => [], 'rows' => []];
        }

        // Read headers (Row 1)
        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $val = trim((string)$sheet->getCell([$col, 1])->getValue());
            if ($val !== '') {
                $headers[$col] = $val;
            }
        }

        $rows = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowObj = ['_line' => $row];
            $hasData = false;
            foreach ($headers as $colIndex => $h) {
                $cellVal = (string)$sheet->getCell([$colIndex, $row])->getFormattedValue();
                $cellVal = trim($cellVal);
                if ($cellVal !== '') {
                    $hasData = true;
                }
                $rowObj[$h] = $cellVal;
            }
            if ($hasData) {
                $rows[] = $rowObj;
            }
        }

        return ['headers' => array_values($headers), 'rows' => $rows];
    }
}
