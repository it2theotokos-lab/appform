<?php
echo "Running AnalyticsVersioning Tests...\n";
assert(class_exists('\App\Services\AnalyticsService') === true, "AnalyticsService must exist.");
return true;
