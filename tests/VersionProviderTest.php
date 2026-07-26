<?php
// Version Provider Tests

use App\Services\VersionService;

echo "Running VersionProvider Tests...\n";

// Test 1: Load and format version data correctly
$data = VersionService::getVersionData();
assert(isset($data['version']), "Test 1 Failed: version key is missing.");
assert(isset($data['build']), "Test 1 Failed: build key is missing.");
assert(isset($data['channel']), "Test 1 Failed: channel key is missing.");
echo "Test 1 Passed: Version metadata format validation.\n";

// Test 2: Version string is semantic versioning format
$verString = VersionService::getVersionString();
assert(preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?$/', $verString), "Test 2 Failed: Version string must match semver.");
echo "Test 2 Passed: Semantic version format matching: $verString\n";

return true;
