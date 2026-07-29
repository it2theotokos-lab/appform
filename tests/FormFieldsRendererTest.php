<?php
/**
 * AppForm FormRenderer HTML & Fields Verification Tests
 */
require_once __DIR__ . '/../vendor/autoload.php';

if (\App\Core\App::$config === null) {
    $config = require __DIR__ . '/../config/config.php';
    \App\Core\App::$config = $config;
    new \App\Core\App();
}

use App\Core\Database;

echo "========================================\n";
echo "  APPFORM FORM RENDERER HTML TESTS\n";
echo "========================================\n";

// Set up mock form and scheme
$form = [
    'title' => 'Test Form Autocomplete/Tags',
    'description' => 'Test form description',
    'slug' => 'test-form-slug',
    'require_terms_acceptance' => false,
    'allow_drafts' => false
];

$schema = [
    'sections' => [
        [
            'title' => 'Section 1',
            'fields' => [
                [
                    'key' => 'field_auto',
                    'type' => 'repository_autocomplete',
                    'label' => 'Auto Field',
                    'placeholder' => 'Search item...',
                    'required' => true,
                    'repoAutoSourceId' => 1,
                    'repoAutoSearchField' => 'name',
                    'repoAutoValueField' => 'code',
                    'repoAutoMappings' => 'code:field_target_code,name:field_target_name'
                ],
                [
                    'key' => 'field_tags',
                    'type' => 'repository_tags',
                    'label' => 'Tags Field',
                    'placeholder' => 'Select tags...',
                    'required' => false,
                    'repoTagsSource1' => 2,
                    'repoTagsSource2' => 3,
                    'repoTagsSearch1' => 'tagname',
                    'repoTagsSearch2' => 'label'
                ],
                [
                    'key' => 'field_target_code',
                    'type' => 'text',
                    'label' => 'Target Code'
                ],
                [
                    'key' => 'field_target_name',
                    'type' => 'text',
                    'label' => 'Target Name'
                ]
            ]
        ]
    ]
];

$answers = [];
$versionId = 1;

// Capture output of view.php
ob_start();
// Set variables that the view expects
$isAdminPreview = false;
$isPublicView = true;
$repositories = []; // Mock
// Include the view file
include __DIR__ . '/../src/Views/forms/view.php';
$html = ob_get_clean();

// -------------------------------------------------------------------------
// TEST 1: HTML Output has Autocomplete and Tags fields rendered
// -------------------------------------------------------------------------
echo "Test 1: Verification of Autocomplete field rendering in HTML...\n";
assert(str_contains($html, 'class="form-control repository-autocomplete-field"'), "Test 1 Failed: Autocomplete input field class missing.");
assert(str_contains($html, 'id="field_auto"'), "Test 1 Failed: Autocomplete ID missing.");
assert(str_contains($html, 'name="field_auto"'), "Test 1 Failed: Autocomplete name missing.");
assert(str_contains($html, 'placeholder="Search item..."'), "Test 1 Failed: Autocomplete placeholder missing.");
assert(str_contains($html, 'data-repo-id="1"'), "Test 1 Failed: Autocomplete repo ID dataset missing.");
assert(str_contains($html, 'data-search-field="name"'), "Test 1 Failed: Autocomplete search field dataset missing.");
assert(str_contains($html, 'data-value-field="code"'), "Test 1 Failed: Autocomplete value field dataset missing.");
assert(str_contains($html, 'data-mappings="code:field_target_code,name:field_target_name"'), "Test 1 Failed: Autocomplete mappings dataset missing.");
echo "  ✅ Autocomplete field rendered correctly with all data properties\n";

echo "Test 2: Verification of Tags field rendering in HTML...\n";
assert(str_contains($html, 'class="form-control repository-tags-field"'), "Test 2 Failed: Tags input field class missing.");
assert(str_contains($html, 'id="field_tags"'), "Test 2 Failed: Tags ID missing.");
assert(str_contains($html, 'name="field_tags"'), "Test 2 Failed: Tags name missing.");
assert(str_contains($html, 'placeholder="Select tags..."'), "Test 2 Failed: Tags placeholder missing.");
assert(str_contains($html, 'data-repo1="2"'), "Test 2 Failed: Tags repo1 dataset missing.");
assert(str_contains($html, 'data-repo2="3"'), "Test 2 Failed: Tags repo2 dataset missing.");
assert(str_contains($html, 'data-search1="tagname"'), "Test 2 Failed: Tags search1 dataset missing.");
assert(str_contains($html, 'data-search2="label"'), "Test 2 Failed: Tags search2 dataset missing.");
echo "  ✅ Tags field rendered correctly with all data properties\n";

// -------------------------------------------------------------------------
// TEST 3: Validate integration of Tagify/Awesomplete in the JavaScript
// -------------------------------------------------------------------------
echo "Test 3: Verification of JS init script inclusion...\n";
assert(str_contains($html, 'document.querySelectorAll(\'.repository-autocomplete-field\').forEach(initAutocomplete)'), "Test 3 Failed: Autocomplete JS initializer not found.");
assert(str_contains($html, 'document.querySelectorAll(\'.repository-tags-field\').forEach(initTagify)'), "Test 3 Failed: Tags JS initializer not found.");
echo "  ✅ JavaScript initialization functions exist\n";

echo "========================================\n";
echo "  ALL FORM RENDERER TESTS PASSED ✅\n";
echo "========================================\n";
return true;
