<?php
namespace App\Services;

class HelpService {
    public static function resolveContext(): string {
        $uri = strtok($_SERVER['REQUEST_URI'], '?');
        
        if ($uri === '/dashboard') return 'dashboard';
        if (str_starts_with($uri, '/admin/users')) return 'users';
        if (str_starts_with($uri, '/admin/roles')) return 'roles';
        if (str_starts_with($uri, '/admin/repositories')) return 'repositories';
        if (str_starts_with($uri, '/admin/forms')) return 'forms';
        if (str_starts_with($uri, '/admin/document-templates')) return 'templates';
        if (str_starts_with($uri, '/admin/workflows')) return 'workflow';
        if (str_starts_with($uri, '/workflow/tasks')) return 'workflow_tasks';
        if (str_starts_with($uri, '/documents/drafts')) return 'drafts';
        if (str_starts_with($uri, '/documents/submissions')) return 'submissions';
        if (str_starts_with($uri, '/documents')) return 'documents';
        if (str_starts_with($uri, '/notifications')) return 'notifications';
        if (str_starts_with($uri, '/admin/analytics')) return 'analytics';
        if (str_starts_with($uri, '/admin/audit')) return 'audit';
        if (str_starts_with($uri, '/admin/settings')) return 'settings';

        return 'dashboard';
    }

    public static function getHelpContent(string $context): array {
        $path = __DIR__ . '/../Views/help/' . $context . '.php';
        if (file_exists($path)) {
            ob_start();
            require $path;
            $html = ob_get_clean();
            return [
                'success' => true,
                'html' => $html
            ];
        }
        return [
            'success' => false,
            'html' => '<p class="text-muted">' . __('No help instructions available for this page.') . '</p>'
        ];
    }
}
