<?php
// Menu Item validation checks tests

use App\Models\NavigationMenuItem;

echo "Running MenuValidation Tests...\n";

// Test 1: Model check
assert(class_exists(NavigationMenuItem::class) === true, "Test 1 Failed: NavigationMenuItem Model must be defined.");
echo "Test 1 Passed: NavigationMenuItem Model check.\n";

return true;
