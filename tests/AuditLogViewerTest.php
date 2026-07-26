<?php
echo "Running AuditLogViewer Tests...\n";
assert(class_exists('\App\Controllers\AuditController') === true, "AuditController must exist.");
return true;
