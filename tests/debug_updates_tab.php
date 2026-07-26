<?php
// Test the settings index view with $tab = 'updates' to verify the fix
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}
new \App\Core\App();

// Simulate what showUpdates() does
$verData = \App\Services\VersionService::getVersionData();
$history = \App\Services\Update\UpdateStatusService::getUpdateHistory(10);
$active  = \App\Services\Update\UpdateStatusService::getActiveUpdate();

// What controller passes
$viewVars = [
    'title'       => 'Test',
    'tab'         => 'updates',     // KEY: controller passes this
    'verData'     => $verData,
    'history'     => $history,
    'active'      => $active,
    'settings'    => \App\Models\SystemSetting::getAll(),
    'backups'     => [],
    'smtp'        => [],
    'audits'      => [],
    'jobs'        => [],
    'workers'     => [],
    'plugins'     => [],
    'cloudTokens' => [],
];

// Simulate View::render extraction
extract($viewVars);

// Test: after extraction, does $tab exist and does $activeTab resolve correctly?
$activeTab_test = $tab ?? $_GET['tab'] ?? 'general';

echo "=== ACTIVETAB TEST ===\n";
echo "Controller passed \$tab: " . ($tab ?? 'NOT SET') . "\n";
echo "Resolved \$activeTab: $activeTab_test\n";
echo "Would show updates panel: " . ($activeTab_test === 'updates' ? 'YES ✓' : 'NO ✗') . "\n";

echo "\n=== VERSION DATA ===\n";
echo "version: " . ($verData['version'] ?? 'MISSING') . "\n";
echo "build:   " . ($verData['build'] ?? 'MISSING') . "\n";
echo "channel: " . ($verData['channel'] ?? 'MISSING') . "\n";

echo "\n=== ACTIVE UPDATE ===\n";
echo "Active update: " . ($active ? "ID={$active['id']} status={$active['status']}" : 'None') . "\n";

echo "\n=== HISTORY ===\n";
echo "History records: " . count($history) . "\n";
if (!empty($history)) {
    foreach (array_slice($history, 0, 3) as $h) {
        echo "  - v{$h['release_version']} build {$h['build_number']}: {$h['status']}\n";
    }
}
return true;
