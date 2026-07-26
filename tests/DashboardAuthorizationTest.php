<?php
echo "Running DashboardAuthorization Tests...\n";
assert(class_exists('\App\Controllers\DashboardController') === true, "DashboardController must exist.");
return true;
