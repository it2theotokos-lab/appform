<?php
echo "Running HealthCheck Tests...\n";
assert(file_exists(__DIR__ . '/../bin/health-check.php') === true, "health-check.php must exist.");
return true;

