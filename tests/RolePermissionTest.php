<?php
echo "Running RolePermission Tests...\n";
assert(class_exists('\App\Models\Role') === true, "Role model class must exist.");
return true;
