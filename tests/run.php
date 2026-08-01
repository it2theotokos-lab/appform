<?php
// Unified Test Runner

if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

new \App\Core\App();

// ── Global translation helper (mirrors public/index.php) ─────────────────────
// Required so tests that include view files can call __() safely.
if (!function_exists('__')) {
    function __(string $key, array $replace = []): string {
        return \App\Services\Lang::get($key, $replace);
    }
}

echo "=========================================\n";
echo "    AppForm Unified Test Runner          \n";
echo "=========================================\n\n";

$testFiles = [
    'RouterTest.php',
    'AuthTest.php',
    'ValidatorTest.php',
    'CsrfTest.php',
    'PermissionTest.php',
    'SessionTest.php',
    'DatabaseTest.php',
    'InstallerTest.php',
    'UserModelTest.php',
    'RolePermissionTest.php',
    'RepositoryValidationTest.php',
    'RepositoryCreationTest.php',
    'FormSchemaValidationTest.php',
    'FormVersioningTest.php',
    'FormFieldsRendererTest.php',
    'FormAccessTest.php',
    'SubmissionAccessTest.php',
    'VersionProviderTest.php',
    'UpdateSystemTest.php',
    'ReleaseBuilderTest.php',
    'ReleaseProviderTest.php',
    'UpdateEngineTest.php',
    'UpdateControllerTest.php',
    'SubmissionDraftTest.php',
    'SubmissionValidationTest.php',
    'SubmissionWorkflowTest.php',
    'SubmissionOwnershipTest.php',
    'FileUploadValidationTest.php',
    'FileDownloadAuthorizationTest.php',
    'MenuValidationTest.php',
    'MenuPermissionTest.php',
    'StatusHistoryTest.php',
    'AnalyticsServiceTest.php',
    'AnalyticsVersioningTest.php',
    'ReportFilterTest.php',
    'CsvExportTest.php',
    'CsvInjectionTest.php',
    'ExcelExportTest.php',
    'PdfExportTest.php',
    'ExportAuthorizationTest.php',
    'AuditLogViewerTest.php',
    'SettingsValidationTest.php',
    'NotificationOwnershipTest.php',
    'DashboardAuthorizationTest.php',
    'HealthCheckTest.php',
    'ReleaseCompletenessTest.php',
    'IntegrityTests.php',
    'SettingsUpdateRegressionTest.php',
    'UpdateDownloadTest.php',
    'ProfileAvatarTest.php',
    'UpdateMigrationTest.php',
    'LocalUpdateAcceptanceTest.php',
    'OrgStructureTest.php',
    'FileSharingTest.php',
    'UserControllerTest.php',
];


$failed = false;

foreach ($testFiles as $file) {
    $path = __DIR__ . '/' . $file;
    if (!file_exists($path)) {
        echo "[-] Error: Test file not found: $file\n";
        $failed = true;
        continue;
    }

    echo "Running $file...\n";
    
    // We can execute it in CLI by running a subprocess or buffering PHP include
    // Since php CLI might not be in PATH, we include them in this script.
    // To isolate variables, we can do it in a closure
    $runTest = function($testPath) {
        try {
            ob_start();
            $resultCode = include $testPath;
            $output = ob_get_clean();
            
            echo $output;
            return true;
        } catch (\Throwable $e) {
            ob_get_clean();
            echo "[-] Test Failed with Exception: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
            return false;
        }
    };

    $success = $runTest($path);
    if (!$success) {
        $failed = true;
    }
    echo "-----------------------------------------\n";
}

if ($failed) {
    echo "\n[FAIL] Some tests failed. Please review outputs.\n";
    exit(1);
} else {
    echo "\n[PASS] All tests passed successfully!\n";
    exit(0);
}
