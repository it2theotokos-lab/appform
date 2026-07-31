<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\View;
use App\Models\Form;
use App\Models\PdfDesign;
use Dompdf\Dompdf;
use Dompdf\Options;
use PDO;

class PdfDesignerController extends Controller {

    private function requireAdmin(): void {
        if (!Auth::check() || Auth::role() !== 'administrator') {
            header('Location: /login');
            exit;
        }
    }

    private function requireLogin(): void {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
    }

    // ─── GET /admin/pdf-designer ───────────────────────────────────────────────
    public function index($params = []): void {
        $this->requireAdmin();
        $forms = Form::getAll();

        View::render('pdf_designer/index', [
            'title' => __('PDF Form Designer'),
            'forms' => $forms,
        ]);
    }

    // ─── GET /admin/pdf-designer/editor/{formId} ─────────────────────────────────
    public function edit($params = []): void {
        $this->requireAdmin();
        $formId = (int)($params['formId'] ?? 0);
        $form = Form::findById($formId);

        if (!$form) {
            $this->redirect('/admin/pdf-designer');
        }

        $design = PdfDesign::getByFormId($formId);
        $elements = $design['elements'] ?? [];

        // Load latest schema_json from form_versions
        $db = Database::getInstance();
        $stmtVer = $db->prepare("SELECT schema_json FROM form_versions WHERE form_id = ? ORDER BY version_number DESC LIMIT 1");
        $stmtVer->execute([$formId]);
        $verRow = $stmtVer->fetch(PDO::FETCH_ASSOC);
        $form['schema_json'] = $verRow['schema_json'] ?? '[]';

        View::render('pdf_designer/editor', [
            'title'    => __('Design PDF') . ' - ' . htmlspecialchars($form['title']),
            'form'     => $form,
            'elements' => $elements,
        ]);
    }

    // ─── POST /admin/pdf-designer/editor/{formId}/save ──────────────────────────
    public function save($params = []): void {
        $this->requireAdmin();
        $this->checkCsrf();
        $formId = (int)($params['formId'] ?? 0);

        $rawJson = $_POST['design_data'] ?? '[]';
        $elements = json_decode($rawJson, true);

        if (!is_array($elements)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => __('Invalid design payload.')]);
            exit;
        }

        PdfDesign::save($formId, $elements);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => __('PDF Design saved successfully.')]);
        exit;
    }

    // ─── POST /admin/pdf-designer/upload-image ──────────────────────────────────
    public function uploadImage($params = []): void {
        $this->requireAdmin();
        $this->checkCsrf();

        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => __('Image upload failed.')]);
            exit;
        }

        $file = $_FILES['image'];

        // Strict mime/extension check
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes, true)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => __('Invalid image format. Allowed: JPG, PNG, WEBP.')]);
            exit;
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => __('Image size exceeds 5MB limit.')]);
            exit;
        }

        $ext = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        };

        $uploadDir = __DIR__ . '/../../storage/pdf_assets';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'img_' . bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $webUrl = '/admin/pdf-designer/assets/' . $filename;
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'url' => $webUrl]);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => __('Could not save uploaded image.')]);
        exit;
    }

    // ─── GET /admin/pdf-designer/assets/{filename} ─────────────────────────────
    public function serveAsset($params = []): void {
        $this->requireLogin();
        $filename = basename($params['filename'] ?? '');

        // Security check: only allow img_[a-f0-9]{32}\.(jpg|png|webp)
        if (!preg_match('/^img_[a-f0-9]{32}\.(jpg|png|webp)$/i', $filename)) {
            http_response_code(404);
            exit('File not found.');
        }

        $filePath = __DIR__ . '/../../storage/pdf_assets/' . $filename;
        if (!file_exists($filePath)) {
            http_response_code(404);
            exit('File not found.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=86400');
        readfile($filePath);
        exit;
    }

    // ─── GET /documents/submissions/{id}/pdf ────────────────────────────────────
    public function generateSubmissionPdf($params = []): void {
        $this->requireLogin();
        $submissionId = (int)($params['id'] ?? 0);

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM form_submissions WHERE id = ?");
        $stmt->execute([$submissionId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sub) {
            http_response_code(404);
            echo __('Submission not found.');
            exit;
        }

        // Authorization check: Admin, or submission owner
        $userId = Auth::id();
        $isAdmin = Auth::role() === 'administrator';
        if (!$isAdmin && (int)$sub['user_id'] !== $userId) {
            http_response_code(403);
            echo __('Forbidden. You do not have permission to view this PDF.');
            exit;
        }

        $formId = (int)$sub['form_id'];
        $form = Form::findById($formId);
        $design = PdfDesign::getByFormId($formId);

        if (!$form || !$design) {
            http_response_code(404);
            echo __('No PDF Design configured for this form.');
            exit;
        }

        $elements = $design['elements'] ?? [];
        $data = json_decode($sub['data_json'], true) ?: [];

        // Build HTML string for Dompdf
        $html = $this->buildPdfHtml($form, $elements, $data);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // ?mode=download → attachment (download); default → inline (browser view)
        $mode = $_GET['mode'] ?? 'inline';
        $isAttachment = ($mode === 'download');
        $filename = 'submission-' . $submissionId . '.pdf';
        $dompdf->stream($filename, ['Attachment' => $isAttachment]);
        exit;
    }

    private function buildPdfHtml(array $form, array $elements, array $subData): string {
        $db = Database::getInstance();
        $stmtVer = $db->prepare("SELECT schema_json FROM form_versions WHERE form_id = ? ORDER BY version_number DESC LIMIT 1");
        $stmtVer->execute([(int)$form['id']]);
        $verRow = $stmtVer->fetch(PDO::FETCH_ASSOC);
        $schemaJson = $verRow['schema_json'] ?? '[]';

        $formFields = [];
        $schema = json_decode($schemaJson, true) ?: [];
        $sections = $schema['sections'] ?? [];
        foreach ($sections as $sec) {
            foreach ($sec['fields'] ?? [] as $rf) {
                if (!empty($rf['key'])) {
                    $formFields[$rf['key']] = $rf['label'] ?? $rf['key'];
                }
                if (!empty($rf['name'])) {
                    $formFields[$rf['name']] = $rf['label'] ?? $rf['name'];
                }
            }
        }

        $elementsHtml = '';
        foreach ($elements as $el) {
            $x = (float)($el['x'] ?? 0);
            $y = (float)($el['y'] ?? 0);
            $w = (float)($el['w'] ?? 100);
            $h = (float)($el['h'] ?? 30);
            $fontSize = (int)($el['fontSize'] ?? 12);
            $align = htmlspecialchars($el['align'] ?? 'left');
            $type = $el['type'] ?? '';

            $style = "position: absolute; left: {$x}mm; top: {$y}mm; width: {$w}mm; font-size: {$fontSize}pt; text-align: {$align}; box-sizing: border-box;";

            if ($type === 'field') {
                $fieldName = $el['fieldName'] ?? '';
                $showLabel = !empty($el['showLabel']);
                $val = $subData[$fieldName] ?? '';
                if (is_array($val)) {
                    $val = implode(', ', $val);
                }
                $labelText = $showLabel ? '<strong>' . htmlspecialchars($formFields[$fieldName] ?? $fieldName) . ':</strong> ' : '';
                $content = $labelText . htmlspecialchars((string)$val);
                $elementsHtml .= "<div style=\"{$style}\">{$content}</div>\n";

            } elseif ($type === 'static_text') {
                $text = htmlspecialchars($el['text'] ?? '');
                $elementsHtml .= "<div style=\"{$style}\">{$text}</div>\n";

            } elseif ($type === 'image') {
                $url = $el['url'] ?? '';
                if ($url) {
                    // Resolve absolute filesystem path for Dompdf
                    if (str_starts_with($url, '/admin/pdf-designer/assets/')) {
                        $filename = basename($url);
                        $fullPath = realpath(__DIR__ . '/../../storage/pdf_assets/' . $filename);
                        if ($fullPath && file_exists($fullPath)) {
                            $url = $fullPath;
                        }
                    } elseif (str_starts_with($url, '/storage/pdf_assets/')) {
                        $filename = basename($url);
                        $fullPath = realpath(__DIR__ . '/../../storage/pdf_assets/' . $filename);
                        if ($fullPath && file_exists($fullPath)) {
                            $url = $fullPath;
                        }
                    } elseif (str_starts_with($url, '/')) {
                        $fullPath = realpath(__DIR__ . '/../../public' . $url);
                        if (!$fullPath) {
                            $fullPath = realpath(__DIR__ . '/../..' . $url);
                        }
                        if ($fullPath && file_exists($fullPath)) {
                            $url = $fullPath;
                        }
                    }
                    $elementsHtml .= "<div style=\"{$style} height: {$h}mm; overflow: hidden;\"><img src=\"{$url}\" style=\"width: 100%; height: 100%; object-fit: contain;\"></div>\n";
                }

            } elseif ($type === 'line') {
                $color = htmlspecialchars($el['color'] ?? '#000000');
                $borderWidth = (int)($el['borderWidth'] ?? 1);
                $elementsHtml .= "<div style=\"position: absolute; left: {$x}mm; top: {$y}mm; width: {$w}mm; border-top: {$borderWidth}px solid {$color};\"></div>\n";

            } elseif ($type === 'border') {
                $color = htmlspecialchars($el['color'] ?? '#000000');
                $borderWidth = (int)($el['borderWidth'] ?? 1);
                $elementsHtml .= "<div style=\"position: absolute; left: {$x}mm; top: {$y}mm; width: {$w}mm; height: {$h}mm; border: {$borderWidth}px solid {$color}; box-sizing: border-box;\"></div>\n";

            } elseif ($type === 'table') {
                $rows    = max(1, (int)($el['rows']    ?? 3));
                $cols    = max(1, (int)($el['cols']    ?? 3));
                $bWidth  = (int)($el['borderWidth'] ?? 1);
                $bColor  = htmlspecialchars($el['color'] ?? '#000000');
                $cells   = $el['cells'] ?? [];   // flat array row*cols
                $tdStyle = "border: {$bWidth}px solid {$bColor}; padding: 2px 4px; font-size: {$fontSize}pt; text-align: {$align}; word-break: break-word;";
                $tblStyle = "position: absolute; left: {$x}mm; top: {$y}mm; width: {$w}mm; border-collapse: collapse; font-size: {$fontSize}pt;";
                $tblHtml = "<table style=\"{$tblStyle}\">";
                $cellIdx = 0;
                for ($r = 0; $r < $rows; $r++) {
                    $tblHtml .= '<tr>';
                    for ($c = 0; $c < $cols; $c++) {
                        $cellText = htmlspecialchars($cells[$cellIdx] ?? '');
                        $tblHtml .= "<td style=\"{$tdStyle}\">{$cellText}</td>";
                        $cellIdx++;
                    }
                    $tblHtml .= '</tr>';
                }
                $tblHtml .= '</table>';
                $elementsHtml .= $tblHtml . "\n";

            } elseif ($type === 'bullet_list') {
                $rawItems  = $el['items'] ?? '';
                $lines     = array_filter(array_map('trim', explode("\n", $rawItems)));
                $liStyle   = "font-size: {$fontSize}pt; text-align: {$align};";
                $listHtml  = "<ul style=\"position: absolute; left: {$x}mm; top: {$y}mm; width: {$w}mm; margin: 0; padding-left: 6mm; font-size: {$fontSize}pt; text-align: {$align};\">";
                foreach ($lines as $line) {
                    $listHtml .= '<li style="' . $liStyle . '">' . htmlspecialchars($line) . '</li>';
                }
                $listHtml .= '</ul>';
                $elementsHtml .= $listHtml . "\n";
            }
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { size: A4 portrait; margin: 0; }
    body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; position: relative; width: 210mm; height: 297mm; }
    table { border-collapse: collapse; }
    td, th { box-sizing: border-box; }
</style>
</head>
<body>
    {$elementsHtml}
</body>
</html>
HTML;
    }
}
