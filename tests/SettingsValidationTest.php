<?php
echo "Running SettingsValidation Tests...\n";
assert(class_exists('\App\Models\SystemSetting') === true, "SystemSetting model must exist.");
return true;
