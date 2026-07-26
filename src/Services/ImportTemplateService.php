<?php
namespace App\Services;

use App\Core\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PDO;

class ImportTemplateService {

    public static function getFields(string $entity): array {
        switch ($entity) {
            case 'users':
                return [
                    ['name' => 'username', 'required' => true, 'desc' => 'Όνομα χρήστη (μοναδικό)'],
                    ['name' => 'email', 'required' => true, 'desc' => 'Διεύθυνση Email (μοναδικό)'],
                    ['name' => 'full_name', 'required' => true, 'desc' => 'Ονοματεπώνυμο'],
                    ['name' => 'role_id', 'required' => true, 'desc' => 'ID Ρόλου (1: Admin, 2: Manager, 3: Reviewer, 4: User)'],
                    ['name' => 'is_active', 'required' => false, 'desc' => '1 = Ενεργός, 0 = Ανενεργός (Default: 1)'],
                    ['name' => 'directory_department', 'required' => false, 'desc' => 'Τμήμα/Διεύθυνση χρήστη']
                ];
            case 'roles':
                return [
                    ['name' => 'name', 'required' => true, 'desc' => 'Όνομα Ρόλου'],
                    ['name' => 'slug', 'required' => true, 'desc' => 'Μοναδικό αναγνωριστικό (slug)'],
                    ['name' => 'description', 'required' => false, 'desc' => 'Περιγραφή ρόλου']
                ];
            case 'repositories':
                return [
                    ['name' => 'name', 'required' => true, 'desc' => 'Όνομα Repository'],
                    ['name' => 'slug', 'required' => true, 'desc' => 'Μοναδικό αναγνωριστικό (slug)'],
                    ['name' => 'description', 'required' => false, 'desc' => 'Περιγραφή repository'],
                    ['name' => 'data_json', 'required' => true, 'desc' => 'Στοιχεία σε μορφή JSON (π.χ. [{"value":"1","label":"Επιλογή"}])']
                ];
            case 'system_settings':
                return [
                    ['name' => 'setting_key', 'required' => true, 'desc' => 'Κλειδί ρύθμισης (μοναδικό)'],
                    ['name' => 'setting_value', 'required' => true, 'desc' => 'Τιμή ρύθμισης'],
                    ['name' => 'setting_type', 'required' => true, 'desc' => 'Τύπος (string, integer, boolean)'],
                    ['name' => 'is_public', 'required' => false, 'desc' => '1 = Δημόσια ρύθμιση, 0 = Ιδιωτική']
                ];
            default:
                return [
                    ['name' => 'name', 'required' => true, 'desc' => 'Όνομα στοιχείου'],
                    ['name' => 'description', 'required' => false, 'desc' => 'Περιγραφή στοιχείου']
                ];
        }
    }

    public static function getExamples(string $entity): array {
        switch ($entity) {
            case 'users':
                return [
                    ['username' => 'john_doe', 'email' => 'john@appform.local', 'full_name' => 'John Doe', 'role_id' => 4, 'is_active' => 1, 'directory_department' => 'IT'],
                    ['username' => 'jane_smith', 'email' => 'jane@appform.local', 'full_name' => 'Jane Smith', 'role_id' => 3, 'is_active' => 1, 'directory_department' => 'HR']
                ];
            case 'roles':
                return [
                    ['name' => 'Custom Reviewer', 'slug' => 'custom-reviewer', 'description' => 'Ειδικός αξιολογητής εγγράφων'],
                ];
            case 'repositories':
                return [
                    ['name' => 'Λίστα Τμημάτων', 'slug' => 'dept-list', 'description' => 'Repository τμημάτων οργανισμού', 'data_json' => '[{"value":"it","label":"Πληροφορική"},{"value":"hr","label":"Ανθρώπινο Δυναμικό"}]']
                ];
            case 'system_settings':
                return [
                    ['setting_key' => 'custom_portal_theme', 'setting_value' => 'dark', 'setting_type' => 'string', 'is_public' => 1]
                ];
            default:
                return [
                    ['name' => 'Παράδειγμα 1', 'description' => 'Περιγραφή παραδείγματος 1']
                ];
        }
    }

    public static function generateCsv(string $entity): string {
        $fields = self::getFields($entity);
        $examples = self::getExamples($entity);

        $headers = array_map(fn($f) => $f['name'] . ($f['required'] ? '*' : ''), $fields);
        
        $rows = [];
        foreach ($examples as $ex) {
            $row = [];
            foreach ($fields as $f) {
                $val = $ex[$f['name']] ?? '';
                if (is_string($val) && (str_starts_with($val, '=') || str_starts_with($val, '+') || str_starts_with($val, '-') || str_starts_with($val, '@'))) {
                    $val = "'" . $val;
                }
                $row[] = $val;
            }
            $rows[] = $row;
        }

        ob_start();
        // Add UTF-8 BOM
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers, ';');
        foreach ($rows as $row) {
            fputcsv($out, $row, ';');
        }
        fclose($out);
        return ob_get_clean();
    }

    public static function generateXlsx(string $entity): string {
        $fields = self::getFields($entity);
        $examples = self::getExamples($entity);

        $spreadsheet = new Spreadsheet();
        
        // --- Worksheet 1: Δεδομένα ---
        $sheetData = $spreadsheet->getActiveSheet();
        $sheetData->setTitle('Δεδομένα');

        // Headers
        $col = 'A';
        foreach ($fields as $f) {
            $headerText = $f['name'] . ($f['required'] ? '*' : '');
            $sheetData->setCellValue($col . '1', $headerText);
            
            // Format headers
            $style = $sheetData->getStyle($col . '1');
            $style->getFont()->setBold(true);
            if ($f['required']) {
                $style->getFont()->getColor()->setRGB('FF0000'); // Red for required fields
            }
            $col++;
        }

        // Examples
        $rowNum = 2;
        foreach ($examples as $ex) {
            $col = 'A';
            foreach ($fields as $f) {
                $val = $ex[$f['name']] ?? '';
                if (is_string($val) && (str_starts_with($val, '=') || str_starts_with($val, '+') || str_starts_with($val, '-') || str_starts_with($val, '@'))) {
                    $val = "'" . $val;
                }
                $sheetData->setCellValue($col . $rowNum, $val);
                $col++;
            }
            $rowNum++;
        }

        // Auto widths & filters
        $lastCol = chr(ord('A') + count($fields) - 1);
        $sheetData->setAutoFilter("A1:{$lastCol}1");
        $sheetData->freezePane('A2');

        for ($c = 'A'; $c <= $lastCol; $c++) {
            $sheetData->getColumnDimension($c)->setAutoSize(true);
        }

        // --- Worksheet 2: Οδηγίες ---
        $sheetInst = $spreadsheet->createSheet();
        $sheetInst->setTitle('Οδηγίες');
        
        $sheetInst->setCellValue('A1', 'Όνομα Πεδίου');
        $sheetInst->setCellValue('B1', 'Υποχρεωτικό;');
        $sheetInst->setCellValue('C1', 'Περιγραφή / Οδηγίες');
        
        $sheetInst->getStyle('A1:C1')->getFont()->setBold(true);
        $sheetInst->getStyle('A1:C1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');

        $rowNum = 2;
        foreach ($fields as $f) {
            $sheetInst->setCellValue('A' . $rowNum, $f['name']);
            $sheetInst->setCellValue('B' . $rowNum, $f['required'] ? 'ΝΑΙ' : 'ΟΧΙ');
            $sheetInst->setCellValue('C' . $rowNum, $f['desc']);
            
            if ($f['required']) {
                $sheetInst->getStyle('B' . $rowNum)->getFont()->setBold(true)->getColor()->setRGB('FF0000');
            }
            $rowNum++;
        }

        $sheetInst->getColumnDimension('A')->setAutoSize(true);
        $sheetInst->getColumnDimension('B')->setAutoSize(true);
        $sheetInst->getColumnDimension('C')->setAutoSize(true);

        // Save
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }
}
