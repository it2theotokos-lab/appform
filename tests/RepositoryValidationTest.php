<?php
echo "Running RepositoryValidation Tests...\n";
assert(class_exists('\App\Models\Repository') === true, "Repository model class must exist.");
return true;
