<?php
// AppForm Front Controller

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
$env = getenv('APP_ENV') ?: 'production';

if (!is_file($autoload)) {
    if ($env === 'development') {
        // Fallback PSR-4 autoloader registered below
    } else {
        http_response_code(500);
        exit('Application dependencies are not installed.');
    }
} else {
    require_once $autoload;
}

// PSR-4 Autoloader mapping App\ namespace to src/
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// ── Global translation helper ────────────────────────────────────────────────
// IMPORTANT: Must be registered before App::__construct() so that any view
// rendered during error handling (e.g. errors/500) can call __() safely.
if (!function_exists('__')) {
    function __(string $key, array $replace = []): string {
        return \App\Services\Lang::get($key, $replace);
    }
}

// Gatekeeper installation checks
if (!\App\Services\InstallationService::isInstalled()) {
    \App\Core\Session::init();
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
    if ($path !== '/' && str_ends_with($path, '/')) {
        $path = rtrim($path, '/');
    }
    if (str_contains($path, '/install')) {
        $controller = new \App\Controllers\InstallerController();
        $controller->showInstall();
        exit;
    } else {
        header('Location: /install?step=1');
        exit;
    }
} else {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
    if ($path !== '/' && str_ends_with($path, '/')) {
        $path = rtrim($path, '/');
    }
    if (str_contains($path, '/install')) {
        if (($_GET['step'] ?? '') === 'success') {
            \App\Core\Session::init();
            $controller = new \App\Controllers\InstallerController();
            $controller->showInstall();
            exit;
        }
        http_response_code(403);
        exit('<h1>403 Forbidden</h1><p>Η εφαρμογή είναι ήδη εγκατεστημένη.</p>');
    }
}

// Bootstrap application
$app = new App\Core\App();

// Core Session Start
\App\Core\Session::init();

// ── Translation helper registered above (after PSR-4 autoloader) ────────────

// Router config initialization
$router = App\Core\App::$router;

// Guest Routes
$router->get('/login', [\App\Controllers\AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [\App\Controllers\AuthController::class, 'login'], ['guest']);

// Auth Routes
$router->post('/logout', [\App\Controllers\AuthController::class, 'logout'], ['auth']);
$router->get('/dashboard', [\App\Controllers\DashboardController::class, 'index'], ['auth']);

// --- PHASE 2 ROUTES ---

// Users CRUD & Organization Structure
$router->get('/admin/users', [\App\Controllers\UserController::class, 'index'], ['auth', 'permission:users.view']);
$router->get('/admin/users/organization', [\App\Controllers\OrgStructureController::class, 'index'], ['auth', 'permission:users.view']);
$router->post('/admin/organization/units', [\App\Controllers\OrgStructureController::class, 'store'], ['auth', 'permission:users.edit']);
$router->post('/admin/organization/units/{id}/update', [\App\Controllers\OrgStructureController::class, 'update'], ['auth', 'permission:users.edit']);
$router->post('/admin/organization/units/{id}/delete', [\App\Controllers\OrgStructureController::class, 'destroy'], ['auth', 'permission:users.edit']);
$router->get('/admin/users/create', [\App\Controllers\UserController::class, 'create'], ['auth', 'permission:users.create']);
$router->post('/admin/users/create', [\App\Controllers\UserController::class, 'store'], ['auth', 'permission:users.create']);
$router->get('/admin/users/{id}/edit', [\App\Controllers\UserController::class, 'edit'], ['auth', 'permission:users.edit']);
$router->put('/admin/users/{id}/edit', [\App\Controllers\UserController::class, 'update'], ['auth', 'permission:users.edit']);
$router->post('/admin/users/{id}/toggle-status', [\App\Controllers\UserController::class, 'toggleStatus'], ['auth', 'permission:users.edit']);
$router->post('/admin/users/{id}/reset-password', [\App\Controllers\UserController::class, 'resetPassword'], ['auth', 'permission:users.edit']);
$router->delete('/admin/users/{id}/delete', [\App\Controllers\UserController::class, 'delete'], ['auth', 'permission:users.delete']);

// File Sharing Module
$router->get('/admin/files', [\App\Controllers\FileSharingController::class, 'index'], ['auth']);
$router->post('/admin/files/upload', [\App\Controllers\FileSharingController::class, 'store'], ['auth']);
$router->get('/admin/files/{id}/manage', [\App\Controllers\FileSharingController::class, 'manage'], ['auth']);
$router->post('/admin/files/{id}/permissions', [\App\Controllers\FileSharingController::class, 'updatePermissions'], ['auth']);
$router->get('/admin/files/{id}/preview', [\App\Controllers\FileSharingController::class, 'preview'], ['auth']);
$router->get('/admin/files/{id}/download', [\App\Controllers\FileSharingController::class, 'download'], ['auth']);
$router->post('/admin/files/{id}/delete', [\App\Controllers\FileSharingController::class, 'destroy'], ['auth']);

// Roles CRUD
$router->get('/admin/roles', [\App\Controllers\RoleController::class, 'index'], ['auth', 'permission:roles.manage']);
$router->post('/admin/roles', [\App\Controllers\RoleController::class, 'store'], ['auth', 'permission:roles.manage']);
$router->get('/admin/roles/{id}/edit', [\App\Controllers\RoleController::class, 'edit'], ['auth', 'permission:roles.manage']);
$router->put('/admin/roles/{id}/edit', [\App\Controllers\RoleController::class, 'update'], ['auth', 'permission:roles.manage']);
$router->delete('/admin/roles/{id}/delete', [\App\Controllers\RoleController::class, 'delete'], ['auth', 'permission:roles.manage']);

// Repositories CRUD
$router->get('/admin/repositories', [\App\Controllers\RepositoryController::class, 'index'], ['auth', 'permission:repositories.manage']);
$router->post('/admin/repositories', [\App\Controllers\RepositoryController::class, 'store'], ['auth', 'permission:repositories.manage']);
$router->get('/admin/repositories/{id}/edit', [\App\Controllers\RepositoryController::class, 'edit'], ['auth', 'permission:repositories.manage']);
$router->put('/admin/repositories/{id}/edit', [\App\Controllers\RepositoryController::class, 'update'], ['auth', 'permission:repositories.manage']);
$router->post('/admin/repositories/{id}/toggle-status', [\App\Controllers\RepositoryController::class, 'toggleStatus'], ['auth', 'permission:repositories.manage']);
$router->delete('/admin/repositories/{id}/delete', [\App\Controllers\RepositoryController::class, 'delete'], ['auth', 'permission:repositories.manage']);
$router->get('/admin/repositories/{id}', [\App\Controllers\RepositoryController::class, 'preview'], ['auth', 'permission:repositories.manage']);
$router->get('/api/repositories/autocomplete', [\App\Controllers\RepositoryController::class, 'apiAutocomplete'], ['auth']);
$router->get('/api/repositories/tags', [\App\Controllers\RepositoryController::class, 'apiTags'], ['auth']);

// Forms CRUD & Builder
$router->get('/admin/dashboard', [\App\Controllers\DashboardController::class, 'index'], ['auth']);
$router->get('/admin/health', [\App\Controllers\DashboardController::class, 'showHealthDashboard'], ['auth', 'permission:settings.view']);
$router->get('/admin/forms', [\App\Controllers\FormController::class, 'index'], ['auth', 'permission:forms.manage']);
$router->post('/admin/forms', [\App\Controllers\FormController::class, 'store'], ['auth', 'permission:forms.manage']);
$router->get('/admin/forms/{id}/edit', [\App\Controllers\FormController::class, 'edit'], ['auth', 'permission:forms.manage']);
$router->post('/admin/forms/{id}/update', [\App\Controllers\FormController::class, 'updateForm'], ['auth', 'permission:forms.manage']);
$router->get('/admin/forms/{id}/builder', [\App\Controllers\FormController::class, 'builder'], ['auth', 'permission:forms.edit']);
$router->post('/admin/forms/{id}/builder', [\App\Controllers\FormController::class, 'saveSchema'], ['auth', 'permission:forms.edit']);
$router->post('/admin/forms/{id}/publish', [\App\Controllers\FormController::class, 'publish'], ['auth', 'permission:forms.publish']);
$router->post('/admin/forms/{id}/archive', [\App\Controllers\FormController::class, 'archive'], ['auth', 'permission:forms.publish']);
$router->delete('/admin/forms/{id}/delete', [\App\Controllers\FormController::class, 'delete'], ['auth', 'permission:forms.delete']);
$router->post('/admin/forms/{id}/duplicate', [\App\Controllers\FormController::class, 'duplicateForm'], ['auth', 'permission:forms.manage']);
$router->get('/admin/forms/{id}/preview', [\App\Controllers\FormController::class, 'adminPreview'], ['auth', 'permission:forms.manage']);
$router->get('/admin/forms/{id}/export', [\App\Controllers\FormController::class, 'export'], ['auth', 'permission:forms.manage']);

// Form Notifications Setup
$router->get('/admin/forms/{id}/notifications', [\App\Controllers\NotificationController::class, 'adminIndex'], ['auth', 'permission:forms.edit']);
$router->post('/admin/settings/global-notifications/update', [\App\Controllers\SettingsController::class, 'updateGlobalNotification'], ['auth', 'permission:settings.view']);
$router->post('/admin/forms/{id}/notifications/create', [\App\Controllers\NotificationController::class, 'store'], ['auth', 'permission:forms.edit']);
$router->post('/admin/forms/{id}/notifications/{notificationId}/update', [\App\Controllers\NotificationController::class, 'update'], ['auth', 'permission:forms.edit']);
$router->post('/admin/forms/{id}/notifications/{notificationId}/delete', [\App\Controllers\NotificationController::class, 'delete'], ['auth', 'permission:forms.edit']);
$router->post('/admin/forms/{id}/notifications/{notificationId}/duplicate', [\App\Controllers\NotificationController::class, 'duplicate'], ['auth', 'permission:forms.edit']);
$router->post('/admin/forms/{id}/notifications/{notificationId}/test', [\App\Controllers\NotificationController::class, 'sendTest'], ['auth', 'permission:forms.edit']);
$router->post('/admin/forms/{id}/notifications/{notificationId}/move-up', [\App\Controllers\NotificationController::class, 'moveUp'], ['auth', 'permission:forms.edit']);
$router->post('/admin/forms/{id}/notifications/{notificationId}/move-down', [\App\Controllers\NotificationController::class, 'moveDown'], ['auth', 'permission:forms.edit']);

// Public Form view renderer
$router->get('/forms/{slug}', [\App\Controllers\FormController::class, 'showPublicForm'], ['auth', 'permission:forms.submit']);
$router->post('/forms/{slug}/draft', [\App\Controllers\SubmissionController::class, 'submitForm'], ['auth', 'permission:forms.submit']);
$router->post('/forms/{slug}/submit', [\App\Controllers\SubmissionController::class, 'submitForm'], ['auth', 'permission:forms.submit']);

// Secure Public Token-based access routes
$router->get('/f/submitted/success', [\App\Controllers\FormController::class, 'showPublicSuccess']);
$router->get('/f/{token}', [\App\Controllers\FormController::class, 'showPublicFormByToken']);
$router->post('/admin/forms/{id}/regenerate-token', [\App\Controllers\FormController::class, 'regeneratePublicToken'], ['auth', 'permission:forms.manage']);

// --- DOCUMENT TEMPLATES ROUTES (Stage 1 & 2 & 3) ---
$router->get('/admin/document-templates', [\App\Controllers\DocumentTemplateController::class, 'index'], ['auth', 'permission:document_templates.view']);
$router->get('/admin/document-templates/create', [\App\Controllers\DocumentTemplateController::class, 'create'], ['auth', 'permission:document_templates.create']);
$router->post('/admin/document-templates', [\App\Controllers\DocumentTemplateController::class, 'store'], ['auth', 'permission:document_templates.create']);
$router->get('/admin/document-templates/{id}', [\App\Controllers\DocumentTemplateController::class, 'show'], ['auth', 'permission:document_templates.view']);
$router->get('/admin/document-templates/{id}/preview', [\App\Controllers\DocumentTemplateController::class, 'preview'], ['auth', 'permission:document_templates.view']);
$router->get('/admin/document-templates/{id}/file/original', [\App\Controllers\DocumentTemplateController::class, 'downloadOriginal'], ['auth', 'permission:document_templates.view']);
$router->get('/admin/document-templates/{id}/file/converted', [\App\Controllers\DocumentTemplateController::class, 'downloadConverted'], ['auth', 'permission:document_templates.view']);
$router->post('/admin/document-templates/{id}/retry-conversion', [\App\Controllers\DocumentTemplateController::class, 'retryConversion'], ['auth', 'permission:document_templates.create']);
$router->get('/admin/document-templates/{id}/designer', [\App\Controllers\DocumentTemplateController::class, 'designer'], ['auth', 'permission:document_templates.edit']);
$router->post('/admin/document-templates/{id}/designer', [\App\Controllers\DocumentTemplateController::class, 'saveDesignerSchema'], ['auth', 'permission:document_templates.edit']);
$router->post('/admin/document-templates/{id}/publish', [\App\Controllers\DocumentTemplateController::class, 'publish'], ['auth', 'permission:document_templates.create']);
$router->post('/admin/document-templates/{id}/create-draft-version', [\App\Controllers\DocumentTemplateController::class, 'createDraftVersion'], ['auth', 'permission:document_templates.create']);

// --- DOCUMENT INSTANCES ROUTES (Stage 4) ---
$router->get('/documents', [\App\Controllers\DocumentInstanceController::class, 'index'], ['auth']);
$router->get('/documents/drafts', [\App\Controllers\DocumentInstanceController::class, 'drafts'], ['auth']);
$router->get('/documents/submissions', [\App\Controllers\DocumentInstanceController::class, 'submissions'], ['auth']);
$router->get('/documents/templates', [\App\Controllers\DocumentInstanceController::class, 'templatesList'], ['auth']);
$router->post('/documents/templates/{id}/start', [\App\Controllers\DocumentInstanceController::class, 'startInstance'], ['auth']);
$router->get('/documents/{id}', [\App\Controllers\DocumentInstanceController::class, 'show'], ['auth']);
$router->get('/documents/{id}/edit', [\App\Controllers\DocumentInstanceController::class, 'edit'], ['auth']);
$router->post('/documents/{id}/save', [\App\Controllers\DocumentInstanceController::class, 'save'], ['auth']);
$router->post('/documents/{id}/submit', [\App\Controllers\DocumentInstanceController::class, 'submit'], ['auth']);
$router->post('/documents/{id}/autosave', [\App\Controllers\DocumentInstanceController::class, 'autosave'], ['auth']);
$router->get('/documents/{id}/preview', [\App\Controllers\DocumentInstanceController::class, 'previewFile'], ['auth']);
$router->get('/documents/{id}/preview-pdf', [\App\Controllers\DocumentInstanceController::class, 'previewPdf'], ['auth']);
$router->get('/documents/{id}/preview-pdf/download', [\App\Controllers\DocumentInstanceController::class, 'previewPdfDownload'], ['auth']);
$router->get('/documents/{id}/source-pdf', [\App\Controllers\DocumentInstanceController::class, 'sourcePdf'], ['auth']);
$router->post('/documents/{id}/cancel', [\App\Controllers\DocumentInstanceController::class, 'cancel'], ['auth']);
$router->post('/documents/{id}/delete', [\App\Controllers\DocumentInstanceController::class, 'delete'], ['auth']);
$router->post('/documents/{id}/migrate-template-version', [\App\Controllers\DocumentInstanceController::class, 'migrate'], ['auth']);
$router->post('/documents/{id}/signature', [\App\Controllers\DocumentInstanceController::class, 'saveSignature'], ['auth']);
$router->get('/documents/{id}/signature/{key}', [\App\Controllers\DocumentInstanceController::class, 'getSignatureImage'], ['auth']);
$router->delete('/documents/{id}/signature/{key}', [\App\Controllers\DocumentInstanceController::class, 'clearSignature'], ['auth']);
$router->get('/documents/{id}/final-pdf', [\App\Controllers\DocumentInstanceController::class, 'finalPdf'], ['auth']);
$router->get('/documents/{id}/final-pdf/download', [\App\Controllers\DocumentInstanceController::class, 'finalPdfDownload'], ['auth']);
$router->post('/documents/{id}/retry-final-pdf', [\App\Controllers\DocumentInstanceController::class, 'retryFinalPdf'], ['auth']);

// Workflow Definitions Routes
$router->get('/admin/workflows', [\App\Controllers\WorkflowDefinitionController::class, 'index'], ['auth', 'permission:workflows.view']);
$router->get('/admin/workflows/create', [\App\Controllers\WorkflowDefinitionController::class, 'create'], ['auth', 'permission:workflows.create']);
$router->post('/admin/workflows', [\App\Controllers\WorkflowDefinitionController::class, 'store'], ['auth', 'permission:workflows.create']);
$router->get('/admin/workflows/{id}/edit', [\App\Controllers\WorkflowDefinitionController::class, 'edit'], ['auth', 'permission:workflows.edit']);
$router->post('/admin/workflows/{id}', [\App\Controllers\WorkflowDefinitionController::class, 'update'], ['auth', 'permission:workflows.edit']);
$router->post('/admin/workflows/{id}/publish', [\App\Controllers\WorkflowDefinitionController::class, 'publish'], ['auth', 'permission:workflows.publish']);
$router->post('/admin/workflows/{id}/archive', [\App\Controllers\WorkflowDefinitionController::class, 'archive'], ['auth', 'permission:workflows.archive']);

// Workflow Tasks Routes
$router->get('/workflow/tasks', [\App\Controllers\WorkflowTaskController::class, 'index'], ['auth']);
$router->get('/workflow/tasks/{id}', [\App\Controllers\WorkflowTaskController::class, 'show'], ['auth']);
$router->post('/workflow/tasks/{id}/decide', [\App\Controllers\WorkflowTaskController::class, 'decide'], ['auth']);
$router->post('/workflow/tasks/{id}/reassign', [\App\Controllers\WorkflowTaskController::class, 'reassign'], ['auth', 'permission:workflow_tasks.reassign']);
$router->post('/workflows/instances/{id}/cancel', [\App\Controllers\WorkflowTaskController::class, 'cancel'], ['auth', 'permission:workflow_instances.cancel']);


// REST API v2 endpoints
$router->get('/api/v2/forms', [\App\Controllers\ApiController::class, 'getForms']);
$router->get('/api/v2/submissions', [\App\Controllers\ApiController::class, 'getSubmissions']);

// --- PHASE 3 ROUTES ---
$router->get('/admin/settings/ldap', [\App\Controllers\SettingsController::class, 'showLdapSettings'], ['auth', 'permission:settings.view']);
$router->post('/admin/settings/ldap', [\App\Controllers\SettingsController::class, 'saveLdapSettings'], ['auth', 'permission:settings.view']);
$router->post('/admin/settings/ldap/test', [\App\Controllers\SettingsController::class, 'testLdapConnection'], ['auth', 'permission:settings.view']);
$router->get('/admin/settings/ldap/role-mappings', [\App\Controllers\SettingsController::class, 'showLdapRoleMappings'], ['auth', 'permission:settings.view']);
$router->post('/admin/settings/ldap/role-mappings', [\App\Controllers\SettingsController::class, 'saveLdapRoleMapping'], ['auth', 'permission:settings.view']);
$router->post('/admin/settings/ldap/role-mappings/{id}/delete', [\App\Controllers\SettingsController::class, 'deleteLdapRoleMapping'], ['auth', 'permission:settings.view']);

// Assignments
$router->get('/admin/forms/{id}/assignments', [\App\Controllers\FormAssignmentController::class, 'edit'], ['auth', 'permission:forms.assign']);
$router->post('/admin/forms/{id}/assignments', [\App\Controllers\FormAssignmentController::class, 'update'], ['auth', 'permission:forms.assign']);

// User Submissions
$router->get('/my-submissions', [\App\Controllers\SubmissionController::class, 'mySubmissions'], ['auth', 'permission:submissions.view.own']);
$router->get('/my-submissions/{uuid}', [\App\Controllers\SubmissionController::class, 'viewSubmission'], ['auth', 'permission:submissions.view.own']);
$router->get('/my-submissions/{uuid}/files/{fileId}/download', [\App\Controllers\SubmissionController::class, 'downloadFile'], ['auth', 'permission:submissions.download.own']);

// Admin Submissions Reviews
$router->get('/admin/submissions', [\App\Controllers\SubmissionController::class, 'listSubmissions'], ['auth']);
$router->get('/admin/submissions/{uuid}', [\App\Controllers\SubmissionController::class, 'showReview'], ['auth', 'permission:submissions.review']);
$router->post('/admin/submissions/{uuid}/start-review', [\App\Controllers\SubmissionController::class, 'startReview'], ['auth', 'permission:submissions.review']);
$router->post('/admin/submissions/{uuid}/approve', [\App\Controllers\SubmissionController::class, 'approve'], ['auth', 'permission:submissions.review']);
$router->post('/admin/submissions/{uuid}/reject', [\App\Controllers\SubmissionController::class, 'reject'], ['auth', 'permission:submissions.review']);
$router->post('/admin/submissions/{uuid}/return', [\App\Controllers\SubmissionController::class, 'returnSubmission'], ['auth', 'permission:submissions.review']);
$router->post('/admin/submissions/{uuid}/return-to-draft', [\App\Controllers\SubmissionController::class, 'returnToDraft'], ['auth', 'permission:submissions.review']);
$router->post('/admin/submissions/{uuid}/delete', [\App\Controllers\SubmissionController::class, 'deleteSubmission'], ['auth', 'permission:submissions.delete']);

// Profile Management
$router->get('/admin/profile', [\App\Controllers\AuthController::class, 'showProfile'], ['auth']);
$router->post('/admin/profile', [\App\Controllers\AuthController::class, 'updateProfile'], ['auth']);
$router->post('/admin/profile/avatar/remove', [\App\Controllers\AuthController::class, 'removeAvatar'], ['auth']);
$router->post('/admin/profile/change-password', [\App\Controllers\AuthController::class, 'updatePassword'], ['auth']);

// Navigation Menus Builder
$router->get('/admin/menus', [\App\Controllers\MenuController::class, 'index'], ['auth', 'permission:menus.manage']);
$router->get('/admin/menus/{roleId}/edit', [\App\Controllers\MenuController::class, 'edit'], ['auth', 'permission:menus.manage']);
$router->post('/admin/menus/{menuId}/add-item', [\App\Controllers\MenuController::class, 'addItem'], ['auth', 'permission:menus.manage']);
$router->post('/admin/menus/items/{itemId}/update', [\App\Controllers\MenuController::class, 'updateItem'], ['auth', 'permission:menus.manage']);
$router->post('/admin/menus/items/{itemId}/reorder', [\App\Controllers\MenuController::class, 'reorderItem'], ['auth', 'permission:menus.manage']);
$router->post('/admin/menus/items/{itemId}/delete', [\App\Controllers\MenuController::class, 'deleteItem'], ['auth', 'permission:menus.manage']);

// --- PHASE 4 ROUTES ---

// Settings
$router->get('/admin/settings', [\App\Controllers\SettingsController::class, 'index'], ['auth', 'permission:settings.manage']);
$router->post('/admin/settings/update', [\App\Controllers\SettingsController::class, 'update'], ['auth', 'permission:settings.manage']);
$router->post('/admin/settings/smtp/update', [\App\Controllers\SettingsController::class, 'updateSmtp'], ['auth', 'permission:smtp.manage']);
$router->post('/admin/settings/smtp/test', [\App\Controllers\SettingsController::class, 'testSmtp'], ['auth', 'permission:smtp.manage']);
$router->post('/admin/settings/smtp/test-email', [\App\Controllers\SettingsController::class, 'sendTestEmail'], ['auth', 'permission:smtp.manage']);
$router->post('/admin/settings/backup/create', [\App\Controllers\SettingsController::class, 'createBackup'], ['auth', 'permission:backups.create']);
$router->get('/admin/settings/backup/{id}/download', [\App\Controllers\SettingsController::class, 'downloadBackup'], ['auth', 'permission:backups.view']);
$router->get('/admin/settings/backup/{id}/verify', [\App\Controllers\SettingsController::class, 'verifyBackup'], ['auth', 'permission:backups.view']);
$router->post('/admin/settings/backup/{id}/delete', [\App\Controllers\SettingsController::class, 'deleteBackup'], ['auth', 'permission:backups.delete']);
$router->post('/admin/settings/demo/import', [\App\Controllers\SettingsController::class, 'importDemoData'], ['auth', 'permission:demo_data.manage']);
$router->post('/admin/settings/demo/delete', [\App\Controllers\SettingsController::class, 'deleteDemoData'], ['auth', 'permission:demo_data.manage']);
$router->post('/admin/settings/restore/execute', [\App\Controllers\SettingsController::class, 'executeRestore'], ['auth', 'permission:restore.execute']);
$router->get('/admin/settings/cloud/auth/{provider}', [\App\Controllers\SettingsController::class, 'redirectToProvider'], ['auth', 'permission:settings.manage']);
$router->get('/admin/settings/cloud/callback', [\App\Controllers\SettingsController::class, 'handleProviderCallback'], ['auth', 'permission:settings.manage']);
$router->post('/admin/settings/cloud/sync/{id}', [\App\Controllers\SettingsController::class, 'syncBackupToCloud'], ['auth', 'permission:settings.manage']);
$router->post('/admin/settings/cloud/disconnect/{provider}', [\App\Controllers\SettingsController::class, 'disconnectProvider'], ['auth', 'permission:settings.manage']);
$router->post('/admin/settings/cloud/test/{provider}', [\App\Controllers\SettingsController::class, 'testProviderConnection'], ['auth', 'permission:settings.manage']);
$router->post('/admin/settings/queue/dispatch-test', [\App\Controllers\QueueController::class, 'dispatchTestJob'], ['auth', 'permission:queue.retry']);
$router->post('/admin/settings/queue/{id}/cancel', [\App\Controllers\QueueController::class, 'cancelJob'], ['auth', 'permission:queue.cancel']);
$router->post('/admin/settings/queue/{id}/retry', [\App\Controllers\QueueController::class, 'retryJob'], ['auth', 'permission:queue.retry']);
$router->post('/admin/settings/plugins/install', [\App\Controllers\PluginController::class, 'install'], ['auth', 'permission:plugins.install']);
$router->post('/admin/settings/plugins/{key}/enable', [\App\Controllers\PluginController::class, 'enable'], ['auth', 'permission:plugins.enable']);
$router->post('/admin/settings/plugins/{key}/disable', [\App\Controllers\PluginController::class, 'disable'], ['auth', 'permission:plugins.disable']);

// --- UPDATE & UPGRADE SYSTEM ROUTES ---
$router->get('/admin/settings/updates', [\App\Controllers\SettingsController::class, 'showUpdates'], ['auth', 'permission:updates.view']);
$router->post('/admin/settings/updates/check', [\App\Controllers\SettingsController::class, 'checkUpdates'], ['auth', 'permission:updates.manage']);
$router->post('/admin/settings/updates/start', [\App\Controllers\SettingsController::class, 'startUpdate'], ['auth', 'permission:updates.manage']);
$router->post('/admin/settings/updates/local', [\App\Controllers\SettingsController::class, 'localUpdate'], ['auth', 'permission:updates.manage']);
$router->get('/admin/settings/updates/status', [\App\Controllers\SettingsController::class, 'getStatus'], ['auth', 'permission:updates.view']);
$router->post('/admin/settings/updates/rollback', [\App\Controllers\SettingsController::class, 'rollbackUpdate'], ['auth', 'permission:updates.rollback']);
$router->post('/admin/settings/updates/force-release', [\App\Controllers\SettingsController::class, 'forceReleaseLock'], ['auth', 'permission:updates.manage']);
$router->get('/admin/settings/updates/logs/download', [\App\Controllers\SettingsController::class, 'downloadDiagnosticLogs'], ['auth', 'permission:updates.view']);

// ── Logo Management ────────────────────────────────────────────────────────────
$router->post('/admin/settings/logo/upload', [\App\Controllers\SettingsController::class, 'uploadLogo'], ['auth', 'permission:settings.manage']);
$router->post('/admin/settings/logo/delete', [\App\Controllers\SettingsController::class, 'deleteLogo'], ['auth', 'permission:settings.manage']);

// ── Favicon Management ─────────────────────────────────────────────────────────
$router->post('/admin/settings/favicon/upload', [\App\Controllers\SettingsController::class, 'uploadFavicon'], ['auth', 'permission:settings.manage']);
$router->post('/admin/settings/favicon/delete', [\App\Controllers\SettingsController::class, 'deleteFavicon'], ['auth', 'permission:settings.manage']);

// Audit Logs
$router->get('/admin/audit', [\App\Controllers\AuditController::class, 'index'], ['auth', 'permission:audit.view']);

// Notifications
$router->get('/notifications', [\App\Controllers\NotificationController::class, 'index'], ['auth']);
$router->post('/notifications/{id}/read', [\App\Controllers\NotificationController::class, 'read'], ['auth']);
$router->post('/notifications/read-all', [\App\Controllers\NotificationController::class, 'readAll'], ['auth']);

// Analytics
$router->get('/admin/analytics', [\App\Controllers\ReportController::class, 'analyticsIndex'], ['auth', 'permission:analytics.view']);
$router->get('/admin/analytics/forms/{id}', [\App\Controllers\ReportController::class, 'formAnalytics'], ['auth', 'permission:analytics.view']);
$router->get('/admin/analytics/forms/{id}/pdf', [\App\Controllers\ReportController::class, 'exportFormAnalyticsPdf'], ['auth', 'permission:analytics.view']);

// Exports
$router->get('/admin/exports/csv/{formId}', [\App\Controllers\ExportController::class, 'exportCsv'], ['auth', 'permission:exports.create']);
$router->get('/admin/exports/excel/{formId}', [\App\Controllers\ExportController::class, 'exportExcel'], ['auth', 'permission:exports.create']);
$router->get('/admin/exports/pdf/{uuid}', [\App\Controllers\ExportController::class, 'exportPdf'], ['auth', 'permission:exports.create']);

// Data Exchange Module
$router->get('/admin/data-exchange', [\App\Controllers\DataExchangeController::class, 'index'], ['auth', 'permission:data_exchange.view']);
$router->post('/admin/data-exchange/export', [\App\Controllers\DataExchangeController::class, 'export'], ['auth', 'permission:data_exchange.export']);
$router->post('/admin/data-exchange/import', [\App\Controllers\DataExchangeController::class, 'import'], ['auth', 'permission:data_exchange.import']);
$router->post('/admin/data-exchange/report', [\App\Controllers\DataExchangeController::class, 'generateReport'], ['auth', 'permission:data_exchange.reports']);
$router->get('/admin/data-exchange/template', [\App\Controllers\DataExchangeController::class, 'downloadTemplate'], ['auth', 'permission:data_exchange.import']);

// ── Language Switcher ──────────────────────────────────────────────────────
$router->post('/admin/set-language', function() {
    $lang = $_POST['lang'] ?? 'el';
    \App\Services\Lang::setLocale($lang);
    $redirect = $_POST['redirect'] ?? '/';
    // Sanitise redirect — must be a relative path starting with '/'
    if (!str_starts_with($redirect, '/') || str_contains($redirect, '://')) {
        $redirect = '/';
    }
    header('Location: ' . $redirect);
    exit;
}, ['auth']);

// Fallback home route redirection
$router->get('/', function() {
    if (\App\Core\Auth::check()) {
        $user = \App\Core\Auth::user();
        if ($user['role_slug'] === 'user') {
            header("Location: /dashboard");
        } else {
            header("Location: /dashboard");
        }
    } else {
        header("Location: /login");
    }
    exit;
});

// Global Contextual Help endpoint
$router->get('/help/context/{context}', function($params) {
    header('Content-Type: application/json');
    $context = $params['context'] ?? 'dashboard';
    $res = \App\Services\HelpService::getHelpContent($context);
    echo json_encode($res);
    exit;
}, ['auth']);

// Run Application
$app->run();
