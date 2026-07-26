<?php
// Release Builder & Package Validator Tests

use App\Services\Update\PackageValidatorService;

echo "Running ReleaseBuilder Tests...\n";

// Test 1: Vulnerability checks for traversal and absolute keys
assert(PackageValidatorService::hasSecurityVulnerability('../../etc/passwd') === true, "Test 1 Failed: Traversal was not detected.");
assert(PackageValidatorService::hasSecurityVulnerability('C:/path/file.php') === true, "Test 1 Failed: Windows drive letter was not detected.");
assert(PackageValidatorService::hasSecurityVulnerability('/absolute/path') === true, "Test 1 Failed: Absolute path was not detected.");
assert(PackageValidatorService::hasSecurityVulnerability("path\0name") === true, "Test 1 Failed: Null byte was not detected.");
assert(PackageValidatorService::hasSecurityVulnerability('src/Core/App.php') === false, "Test 1 Failed: Safe path was flagged as unsafe.");
echo "Test 1 Passed: Safe path filtering and traversals validated.\n";

// Test 2: Verify package checks on build update file
$updateZip = __DIR__ . '/../release/AppForm-1.0.0-update.zip';
if (file_exists($updateZip)) {
    $res = PackageValidatorService::validatePackage($updateZip);
    assert($res['success'] === true, "Test 2 Failed: Update package failed validation check: " . ($res['message'] ?? ''));
    echo "Test 2 Passed: Incremental update package verified successfully.\n";
} else {
    echo "Test 2 Skipped: Update zip was not found.\n";
}

return true;
