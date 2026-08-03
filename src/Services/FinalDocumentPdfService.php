<?php
namespace App\Services;

use App\Core\Database;
use App\Models\DocumentInstance;
use App\Models\DocumentInstanceValue;
use App\Models\DocumentSignature;
use setasign\Fpdi\Fpdi;

class FinalDocumentPdfService {
    public static function generate(int $instanceId, int $userId): array {
        $db = Database::getInstance();

        // 1. Concurrency Check: lock row or transition pending -> processing
        // First check existing file status
        $stmtCheck = $db->prepare("SELECT generation_status FROM document_final_files WHERE document_instance_id = ?");
        $stmtCheck->execute([$instanceId]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            if ($existing['generation_status'] === 'processing') {
                return ['success' => false, 'error' => 'duplicate_processing', 'message' => 'Η παραγωγή του τελικού αρχείου βρίσκεται ήδη σε εξέλιξη.'];
            }
            if ($existing['generation_status'] === 'completed') {
                return ['success' => false, 'error' => 'already_completed', 'message' => 'Το τελικό PDF έχει ήδη δημιουργηθεί.'];
            }
            // Update to processing
            $stmtUp = $db->prepare("UPDATE document_final_files SET generation_status = 'processing', generation_error = NULL, updated_at = NOW() WHERE document_instance_id = ?");
            $stmtUp->execute([$instanceId]);
        } else {
            // First fetch template_version_id from document_instance safely
            $stmtGetVer = $db->prepare("SELECT template_version_id FROM document_instances WHERE id = ?");
            $stmtGetVer->execute([$instanceId]);
            $verId = (int)$stmtGetVer->fetchColumn();

            if (!$verId) {
                return ['success' => false, 'error' => 'invalid_instance', 'message' => 'Το έγγραφο δεν βρέθηκε ή δεν έχει έκδοση προτύπου.'];
            }

            // Insert initial processing record
            $stmtIns = $db->prepare("
                INSERT INTO document_final_files (document_instance_id, file_path, original_filename, mime_type, file_size, sha256_hash, generated_by, template_version_id, generation_status, page_count)
                VALUES (?, '', '', 'application/pdf', 0, '', ?, ?, 'processing', 1)
            ");
            $stmtIns->execute([$instanceId, $userId, $verId]);
        }

        try {
            // 2. Fetch Document details
            $instance = DocumentInstance::findById($instanceId);
            if (!$instance) {
                throw new \Exception("Το έγγραφο δεν βρέθηκε.");
            }

            // 3. Resolve PDF Background Path
            $db = Database::getInstance();
            $stmtTpl = $db->prepare("
                SELECT t.source_type, t.original_file_path, t.converted_pdf_path, v.pdf_file_path, v.page_count
                FROM document_instances i
                JOIN document_templates t ON i.document_template_id = t.id
                JOIN document_template_versions v ON i.template_version_id = v.id
                WHERE i.id = ?
            ");
            $stmtTpl->execute([$instanceId]);
            $tpl = $stmtTpl->fetch(\PDO::FETCH_ASSOC);

            if (!$tpl) {
                throw new \Exception("Τα στοιχεία του προτύπου δεν βρέθηκαν.");
            }

            $sourcePdf = '';
            if (strtolower($tpl['source_type']) === 'pdf') {
                $sourcePdf = $tpl['original_file_path'];
            } else {
                $sourcePdf = $tpl['pdf_file_path'] ?: $tpl['converted_pdf_path'];
            }

            if (!$sourcePdf || !file_exists($sourcePdf)) {
                throw new \Exception("Το αρχείο PDF του προτύπου δεν βρέθηκε.");
            }

            // 4. Fetch values and signatures
            $values = DocumentInstanceValue::getValuesForInstance($instanceId);
            $signatures = DocumentSignature::getForInstance($instanceId);
            $fields = json_decode($instance['fields_schema_json'], true) ?: [];

            // Verify required signatures are present
            foreach ($fields as $field) {
                if ($field['type'] === 'signature' && !empty($field['required'])) {
                    $sigFound = false;
                    foreach ($signatures as $sig) {
                        if ($sig['field_key'] === $field['key']) {
                            $sigFound = true;
                            break;
                        }
                    }
                    if (!$sigFound) {
                        throw new \Exception("Απαιτείται υπογραφή για το πεδίο: " . $field['label']);
                    }
                }
            }

            // 5. Initialize FPDI PDF builder
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($sourcePdf);

            // Import all pages of original PDF template
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templatePage = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templatePage);
                
                // Add page keeping original dimensions and orientation
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templatePage);

                // Draw overlay fields matching current page
                $pageFields = array_filter($fields, function($f) use ($pageNo) {
                    return (int)($f['page'] ?? 1) === $pageNo;
                });

                foreach ($pageFields as $f) {
                    $key = $f['key'];
                    $type = $f['type'];
                    
                    // Coordinates conversion relative to original page dimensions (in mm)
                    $x = ($f['x_ratio'] ?? 0) * $size['width'];
                    $y = ($f['y_ratio'] ?? 0) * $size['height'];
                    $w = ($f['width_ratio'] ?? 0) * $size['width'];
                    $h = ($f['height_ratio'] ?? 0) * $size['height'];

                    if ($type === 'signature') {
                        // Find signed image PNG file
                        $sigRow = null;
                        foreach ($signatures as $sig) {
                            if ($sig['field_key'] === $key) {
                                $sigRow = $sig;
                                break;
                            }
                        }

                        if ($sigRow) {
                            $sigFilePath = "storage/document_signatures/" . $sigRow['signature_image'];
                            if (file_exists($sigFilePath)) {
                                // Draw signature image preserving transparency
                                $pdf->Image($sigFilePath, $x, $y, $w, $h, 'PNG');
                            }
                        }
                    } else {
                        // Draw text values
                        $val = $values[$key] ?? $f['validation']['default'] ?? '';
                        if ($type === 'consent') {
                            $val = ($val === '1' || $val === 1 || $val === 'true') ? 'X' : '';
                        }
                        
                        if ($val !== '') {
                            // Map Repository Snapshot Label
                            if ($type === 'repository_select' || !empty($f['repo_slug'])) {
                                // Try parsing repository label snapshot if stored inside value_text or value_json
                                $db = Database::getInstance();
                                $stmtVal = $db->prepare("SELECT value_text FROM document_instance_values WHERE document_instance_id = ? AND field_key = ?");
                                $stmtVal->execute([$instanceId, $key]);
                                $storedVal = $stmtVal->fetchColumn();
                                if ($storedVal) {
                                    $val = $storedVal;
                                }
                            }

                            // Convert Greek text encoding from UTF-8 to CP1253 (for standard FPDF fonts mapping)
                            $encodedVal = iconv('UTF-8', 'windows-1253//TRANSLIT', $val);

                            // Calculate optimal font size based on box height (h) and width (w)
                            $fontSizePt = max(7, min(12, round($h * 2.2)));
                            $pdf->SetFont('times', '', $fontSizePt);
                            $pdf->SetTextColor(0, 0, 0);

                            // Set position with slight top offset for baseline alignment matching browser canvas
                            $pdf->SetXY($x, $y + 1);
                            
                            // Draw MultiCell wrapping text or Cell matching text block dimensions
                            if ($type === 'textarea') {
                                $pdf->MultiCell($w, $h / 2 ?: 5, $encodedVal, 0, 'L');
                            } else {
                                $pdf->Cell($w, $h - 1, $encodedVal, 0, 0, 'L');
                            }
                        }
                    }
                }

                // Draw configuration-safe page identifier footer text
                $footerText = "{$instance['document_number']} | Σελίδα {$pageNo}/{$pageCount} | " . date('d/m/Y H:i');
                $pdf->SetFont('times', '', 8);
                $pdf->SetTextColor(120, 120, 120);
                $pdf->SetXY(10, $size['height'] - 8);
                $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1253//TRANSLIT', $footerText), 0, 0, 'L');
            }

            // 6. Output PDF to local private storage directory
            $year = date('Y');
            $month = date('m');
            $dir = "storage/document_final_pdfs/{$year}/{$month}";
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Safe sanitized file name structure
            $safeDocNumber = preg_replace('/[^a-zA-Z0-9_\-]/', '', $instance['document_number']);
            $filename = "{$safeDocNumber}-final.pdf";
            $tempFilePath = "{$dir}/{$safeDocNumber}-final.tmp";
            $finalFilePath = "{$dir}/{$filename}";

            // Write temporary file
            $pdf->Output('F', $tempFilePath);

            // 7. Validate temporary generated PDF file
            if (!file_exists($tempFilePath) || filesize($tempFilePath) === 0) {
                throw new \Exception("Η εγγραφή του προσωρινού αρχείου PDF απέτυχε.");
            }

            // Validate PDF header signature
            $handle = fopen($tempFilePath, 'rb');
            $header = fread($handle, 4);
            fclose($handle);
            if ($header !== '%PDF') {
                @unlink($tempFilePath);
                throw new \Exception("Το αρχείο PDF είναι κατεστραμμένο ή δεν ξεκινά με %PDF.");
            }

            // Atomically rename temporary file to final path
            if (file_exists($finalFilePath)) {
                @unlink($finalFilePath);
            }
            rename($tempFilePath, $finalFilePath);

            // Calculate metadata values
            $fileSize = filesize($finalFilePath);
            $sha256 = hash_file('sha256', $finalFilePath);

            // Update Database Catalog
            $stmtUpd = $db->prepare("
                UPDATE document_final_files 
                SET file_path = ?, original_filename = ?, mime_type = 'application/pdf', file_size = ?, sha256_hash = ?, generated_at = NOW(), template_version_id = ?, generation_status = 'completed', page_count = ?
                WHERE document_instance_id = ?
            ");
            $stmtUpd->execute([$finalFilePath, $filename, $fileSize, $sha256, $instance['template_version_id'], $pageCount, $instanceId]);

            // Update Instance state status to finalized
            $stmtInst = $db->prepare("UPDATE document_instances SET status = 'finalized', finalized_at = NOW() WHERE id = ?");
            $stmtInst->execute([$instanceId]);

            // Auditing events logs
            self::logAuditStatic($db, 'final_pdf.generation.completed', 'document_instances', $instanceId, [
                'document_id' => $instanceId,
                'template_version_id' => $instance['template_version_id'],
                'output_filename' => $filename,
                'file_size' => $fileSize,
                'sha256' => $sha256,
                'page_count' => $pageCount
            ], $userId);

            self::logAuditStatic($db, 'document.finalized', 'document_instances', $instanceId, [
                'document_number' => $instance['document_number']
            ], $userId);

            // Notify Creator
            try {
                \App\Services\LifecycleNotificationService::notifyDocumentLifecycle(
                    'pdf_ready',
                    [
                        'id' => $instanceId,
                        'document_number' => $instance['document_number'],
                        'created_by' => $instance['created_by']
                    ],
                    $userId
                );
            } catch (\Exception $notifEx) {}

            return ['success' => true, 'file_path' => $finalFilePath, 'sha256' => $sha256];

        } catch (\Exception $e) {
            // Update to failed state
            $stmtFail = $db->prepare("UPDATE document_final_files SET generation_status = 'failed', generation_error = ?, updated_at = NOW() WHERE document_instance_id = ?");
            $stmtFail->execute([$e->getMessage(), $instanceId]);

            self::logAuditStatic($db, 'final_pdf.generation.failed', 'document_instances', $instanceId, [
                'error' => $e->getMessage()
            ], $userId);

            // Notify Admin
            try {
                \App\Services\LifecycleNotificationService::notifyDocumentLifecycle(
                    'pdf_failed',
                    [
                        'id' => $instanceId,
                        'document_number' => $instance['document_number'],
                        'created_by' => $instance['created_by']
                    ],
                    $userId,
                    $e->getMessage()
                );
            } catch (\Exception $notifEx) {}

            return ['success' => false, 'error' => 'generation_failed', 'message' => $e->getMessage()];
        }
    }

    public static function generateDraftPdf(int $instanceId): string {
        $db = Database::getInstance();
        $instance = DocumentInstance::findById($instanceId);
        if (!$instance) {
            throw new \Exception("Το έγγραφο δεν βρέθηκε.");
        }

        $stmtTpl = $db->prepare("
            SELECT t.source_type, t.original_file_path, t.converted_pdf_path, v.pdf_file_path
            FROM document_instances i
            JOIN document_templates t ON i.document_template_id = t.id
            JOIN document_template_versions v ON i.template_version_id = v.id
            WHERE i.id = ?
        ");
        $stmtTpl->execute([$instanceId]);
        $tpl = $stmtTpl->fetch(\PDO::FETCH_ASSOC);

        $sourcePdf = '';
        if (strtolower($tpl['source_type']) === 'pdf') {
            $sourcePdf = $tpl['original_file_path'];
        } else {
            $sourcePdf = $tpl['pdf_file_path'] ?: $tpl['converted_pdf_path'];
        }

        if (!$sourcePdf || !file_exists($sourcePdf)) {
            throw new \Exception("Το αρχείο PDF του προτύπου δεν βρέθηκε.");
        }

        $values = DocumentInstanceValue::getValuesForInstance($instanceId);
        $signatures = DocumentSignature::getForInstance($instanceId);
        $fields = json_decode($instance['fields_schema_json'], true) ?: [];

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($sourcePdf);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templatePage = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templatePage);
            
            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templatePage);

            $pageFields = array_filter($fields, function($f) use ($pageNo) {
                return (int)($f['page'] ?? 1) === $pageNo;
            });

            foreach ($pageFields as $f) {
                $key = $f['key'];
                $type = $f['type'];
                
                $x = ($f['x_ratio'] ?? 0) * $size['width'];
                $y = ($f['y_ratio'] ?? 0) * $size['height'];
                $w = ($f['width_ratio'] ?? 0) * $size['width'];
                $h = ($f['height_ratio'] ?? 0) * $size['height'];

                if ($type === 'signature') {
                    $sigRow = null;
                    foreach ($signatures as $sig) {
                        if ($sig['field_key'] === $key) {
                            $sigRow = $sig;
                            break;
                        }
                    }

                    if ($sigRow) {
                        $sigFilePath = "storage/document_signatures/" . $sigRow['signature_image'];
                        if (file_exists($sigFilePath)) {
                            $pdf->Image($sigFilePath, $x, $y, $w, $h, 'PNG');
                        }
                    }
                } else {
                    $val = $values[$key] ?? $f['validation']['default'] ?? '';
                    if ($type === 'consent') {
                        $val = ($val === '1' || $val === 1 || $val === 'true') ? 'X' : '';
                    }
                    
                    if ($val !== '') {
                        if ($type === 'repository_select' || !empty($f['repo_slug'])) {
                            $stmtVal = $db->prepare("SELECT value_text FROM document_instance_values WHERE document_instance_id = ? AND field_key = ?");
                            $stmtVal->execute([$instanceId, $key]);
                            $storedVal = $stmtVal->fetchColumn();
                            if ($storedVal) {
                                $val = $storedVal;
                            }
                        }

                        $encodedVal = iconv('UTF-8', 'windows-1253//TRANSLIT', $val);
                        $fontSizePt = max(7, min(12, round($h * 2.2)));
                        $pdf->SetFont('times', '', $fontSizePt);
                        $pdf->SetTextColor(0, 0, 0);
                        $pdf->SetXY($x, $y + 1);
                        
                        if ($type === 'textarea') {
                            $pdf->MultiCell($w, $h / 2 ?: 5, $encodedVal, 0, 'L');
                        } else {
                            $pdf->Cell($w, $h - 1, $encodedVal, 0, 0, 'L');
                        }
                    }
                }
            }

            // Draw configuration-safe page identifier footer text
            $footerText = "{$instance['document_number']} | Σελίδα {$pageNo}/{$pageCount} | " . date('d/m/Y H:i') . " (Draft Preview)";
            $pdf->SetFont('times', '', 8);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->SetXY(10, $size['height'] - 8);
            $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1253//TRANSLIT', $footerText), 0, 0, 'L');
        }

        $dir = "storage/document_final_pdfs/drafts";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filePath = "{$dir}/{$instanceId}-draft.pdf";
        $pdf->Output('F', $filePath);
        return $filePath;
    }

    private static function logAuditStatic($db, string $action, string $entityType, ?int $entityId, array $metadata, int $userId) {
        try {
            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                json_encode($metadata, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
        } catch (\Exception $e) {}
    }
}
