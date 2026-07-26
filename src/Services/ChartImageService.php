<?php
namespace App\Services;

class ChartImageService {

    /**
     * Generate SVG data URI for chart type based on distribution or numeric data
     */
    public static function generateSvgDataUri(array $question, string $chartType = 'pie'): string {
        $width = 450;
        $height = 220;
        
        $distribution = $question['distribution'] ?? [];
        if (empty($distribution) && $question['average'] === null) {
            return '';
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '" style="background:#ffffff; font-family:sans-serif;">';
        
        // Colors palette
        $colors = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#64748b'];

        if ($chartType === 'pie' || $chartType === 'doughnut') {
            $cx = 120;
            $cy = 110;
            $r = 75;
            $total = 0;
            foreach ($distribution as $d) {
                $total += $d['count'];
            }

            if ($total > 0) {
                $startAngle = 0;
                $colorIdx = 0;
                foreach ($distribution as $d) {
                    $sliceAngle = ($d['count'] / $total) * 2 * M_PI;
                    $endAngle = $startAngle + $sliceAngle;

                    $x1 = $cx + $r * cos($startAngle);
                    $y1 = $cy + $r * sin($startAngle);
                    $x2 = $cx + $r * cos($endAngle);
                    $y2 = $cy + $r * sin($endAngle);

                    $largeArc = $sliceAngle > M_PI ? 1 : 0;
                    $pathData = "M {$cx},{$cy} L {$x1},{$y1} A {$r},{$r} 0 {$largeArc},1 {$x2},{$y2} Z";
                    $color = $colors[$colorIdx % count($colors)];

                    $svg .= '<path d="' . $pathData . '" fill="' . $color . '" stroke="#ffffff" stroke-width="1.5" />';
                    $startAngle = $endAngle;
                    $colorIdx++;
                }

                if ($chartType === 'doughnut') {
                    $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . ($r * 0.5) . '" fill="#ffffff" />';
                }
            }

            // Legend
            $lx = 240;
            $ly = 30;
            $colorIdx = 0;
            foreach ($distribution as $d) {
                $color = $colors[$colorIdx % count($colors)];
                $svg .= '<rect x="' . $lx . '" y="' . $ly . '" width="12" height="12" rx="2" fill="' . $color . '" />';
                $label = htmlspecialchars(mb_strimwidth($d['value'], 0, 25, '...'));
                $svg .= '<text x="' . ($lx + 18) . '" y="' . ($ly + 10) . '" font-size="11" fill="#334155">' . $label . ' (' . $d['percentage'] . '%)</text>';
                $ly += 22;
                $colorIdx++;
                if ($ly > $height - 20) break;
            }

        } else {
            // Default Bar chart (Horizontal/Vertical)
            $y = 20;
            $maxCount = 1;
            foreach ($distribution as $d) {
                if ($d['count'] > $maxCount) $maxCount = $d['count'];
            }

            $colorIdx = 0;
            foreach ($distribution as $d) {
                $color = $colors[$colorIdx % count($colors)];
                $barWidth = max(5, round(($d['count'] / $maxCount) * 200));
                $label = htmlspecialchars(mb_strimwidth($d['value'], 0, 22, '...'));

                $svg .= '<text x="15" y="' . ($y + 14) . '" font-size="11" fill="#334155">' . $label . '</text>';
                $svg .= '<rect x="150" y="' . $y . '" width="' . $barWidth . '" height="18" rx="3" fill="' . $color . '" />';
                $svg .= '<text x="' . (155 + $barWidth) . '" y="' . ($y + 14) . '" font-size="11" font-weight="bold" fill="#0f172a">' . $d['count'] . ' (' . $d['percentage'] . '%)</text>';

                $y += 28;
                $colorIdx++;
                if ($y > $height - 25) break;
            }
        }

        $svg .= '</svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
