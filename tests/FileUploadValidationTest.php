<?php
// File Upload Validation and whitelists tests

use App\Core\UploadManager;

echo "Running FileUploadValidation Tests...\n";

// Test 1: UploadManager checks
assert(class_exists(UploadManager::class) === true, "Test 1 Failed: UploadManager class must be defined.");
echo "Test 1 Passed: UploadManager existence check.\n";

return true;
