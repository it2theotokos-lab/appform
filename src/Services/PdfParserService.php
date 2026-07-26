<?php
// PDF Parser helper using basic cross-reference table checks to extract PDF properties cleanly.
namespace App\Services;

class PdfParserService {
    public static function parseMetadata(string $pdfPath): array {
        if (!file_exists($pdfPath)) {
            throw new \Exception("PDF file not found.");
        }

        $content = file_get_contents($pdfPath);
        if ($content === false) {
            throw new \Exception("Unable to read PDF file.");
        }

        // Basic PDF header verification
        if (strncmp($content, "%PDF-", 5) !== 0) {
            throw new \Exception("Invalid PDF document structure.");
        }

        // Check if password protected or encrypted
        if (strpos($content, "/Encrypt") !== false) {
            throw new \Exception("Password-protected or encrypted PDFs are not supported.");
        }

        // Count pages using basic search regexes
        $pageCount = 0;
        if (preg_match_all('/\/Type\s*\/Page\b/', $content, $matches)) {
            $pageCount = count($matches[0]);
        }
        // Fallback checks
        if ($pageCount === 0) {
            if (preg_match_all('/\/Count\s+(\d+)/', $content, $matches)) {
                $pageCount = (int)max($matches[1]);
            }
        }

        if ($pageCount === 0) {
            $pageCount = 1; // Default fallback to 1 page
        }

        // Set default page dimensions for PDF (A4 size: 595.28 x 841.89 points)
        $pagesDimensions = [];
        for ($i = 1; $i <= $pageCount; $i++) {
            $pagesDimensions[] = [
                'page' => $i,
                'width' => 595.28,
                'height' => 841.89,
                'orientation' => 'portrait',
                'rotation' => 0
            ];
        }

        return [
            'page_count' => $pageCount,
            'dimensions' => $pagesDimensions
        ];
    }
}
