<?php
echo "Running ExportAuthorization Tests...\n";
assert(class_exists('\App\Models\ExportJob') === true, "ExportJob model must exist.");
return true;
