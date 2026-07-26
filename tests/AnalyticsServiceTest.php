<?php
// Analytics Engine Tests

use App\Services\AnalyticsService;

echo "Running AnalyticsService Tests...\n";

// Test 1: Class check
assert(class_exists(AnalyticsService::class) === true, "Test 1 Failed: AnalyticsService must exist.");
echo "Test 1 Passed: AnalyticsService presence check.\n";

return true;
