<?php
namespace App\Services;

class ExcelExportService {
    public static function export(array $headers, array $rows): string {
        if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Real XLSX Export
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Header format
            $col = 'A';
            foreach ($headers as $h) {
                // Formula injection protection
                if (str_starts_with($h, '=') || str_starts_with($h, '+') || str_starts_with($h, '-') || str_starts_with($h, '@')) {
                    $h = "'" . $h;
                }
                $sheet->setCellValue($col . '1', $h);
                $sheet->getStyle($col . '1')->getFont()->setBold(true);
                $col++;
            }

            // Data Rows
            $rowNum = 2;
            foreach ($rows as $row) {
                $col = 'A';
                foreach ($row as $val) {
                    if (is_string($val)) {
                        // Formula injection protection
                        if (str_starts_with($val, '=') || str_starts_with($val, '+') || str_starts_with($val, '-') || str_starts_with($val, '@')) {
                            $val = "'" . $val;
                        }
                    }
                    $sheet->setCellValue($col . $rowNum, $val);
                    $col++;
                }
                $rowNum++;
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            ob_start();
            $writer->save('php://output');
            return ob_get_clean();
        }

        // Fallback: XML Spreadsheet 2003
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ' .
                'xmlns:o="urn:schemas-microsoft-com:office:office" ' .
                'xmlns:x="urn:schemas-microsoft-com:office:excel" ' .
                'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $xml .= '  <Worksheet ss:Name="Submissions">' . "\n";
        $xml .= '    <Table>' . "\n";
        $xml .= '      <Row>' . "\n";
        foreach ($headers as $h) {
            $xml .= '        <Cell><Data ss:Type="String">' . htmlspecialchars($h) . '</Data></Cell>' . "\n";
        }
        $xml .= '      </Row>' . "\n";
        foreach ($rows as $row) {
            $xml .= '      <Row>' . "\n";
            foreach ($row as $val) {
                $type = is_numeric($val) ? 'Number' : 'String';
                $xml .= '        <Cell><Data ss:Type="' . $type . '">' . htmlspecialchars($val ?? '') . '</Data></Cell>' . "\n";
            }
            $xml .= '      </Row>' . "\n";
        }
        $xml .= '    </Table>' . "\n";
        $xml .= '  </Worksheet>' . "\n";
        $xml .= '</Workbook>' . "\n";
        return $xml;
    }
}
