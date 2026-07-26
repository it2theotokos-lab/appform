<?php
namespace App\Core;

class View {
    public static function render(string $viewPath, array $data = [], string $layout = 'app') {
        extract($data);
        
        // Find view file
        $viewFile = __DIR__ . '/../Views/' . $viewPath . '.php';
        if (!file_exists($viewFile)) {
            throw new \Exception("View file $viewFile not found");
        }

        // Render page inside output buffer
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render layout
        $layoutFile = __DIR__ . '/../Views/layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    public static function escape($data) {
        if (is_array($data)) {
            return array_map([self::class, 'escape'], $data);
        }
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }
}
