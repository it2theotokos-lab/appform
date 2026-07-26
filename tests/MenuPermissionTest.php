<?php
// Menu visibility permissions check tests

use App\Models\NavigationMenu;

echo "Running MenuPermission Tests...\n";

// Test 1: Model check
assert(class_exists(NavigationMenu::class) === true, "Test 1 Failed: NavigationMenu Model must be defined.");
echo "Test 1 Passed: NavigationMenu Model check.\n";

return true;
