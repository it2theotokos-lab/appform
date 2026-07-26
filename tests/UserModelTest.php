<?php
echo "Running UserModel Tests...\n";
assert(class_exists('\App\Models\User') === true, "User model class must exist.");
return true;
