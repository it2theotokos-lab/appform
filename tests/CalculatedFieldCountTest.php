<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Services\CalculationService;

echo "Running CalculatedFieldCountTest...\n";

$fieldsMap = [
    'field_AIGALEO' => ['type' => 'checkbox', 'key' => 'field_AIGALEO', 'options' => [
        ['value' => '1', 'label' => 'Opt 1'],
        ['value' => '2', 'label' => 'Opt 2'],
        ['value' => '3', 'label' => 'Opt 3'],
        ['value' => '4', 'label' => 'Opt 4'],
        ['value' => '5', 'label' => 'Opt 5'],
        ['value' => '6', 'label' => 'Opt 6'],
        ['value' => '7', 'label' => 'Opt 7'],
        ['value' => '8', 'label' => 'Opt 8'],
    ]],
    'field_ERGAZOMENOI' => ['type' => 'checkbox', 'key' => 'field_ERGAZOMENOI', 'options' => []],
    'field_KALLITHA' => ['type' => 'checkbox', 'key' => 'field_KALLITHA', 'options' => [
        ['value' => '1', 'label' => 'Opt 1'],
        ['value' => '2', 'label' => 'Opt 2']
    ]],
    'field_NUM' => ['type' => 'number', 'key' => 'field_NUM']
];

// ============================================================
// GROUP A: BRACED syntax  count({field_KEY})
// ============================================================

// TEST A1: 0 checked
$v = CalculationService::evaluate('count({field_AIGALEO})', ['field_AIGALEO' => []], $fieldsMap);
assert($v === 0.0, "A1 failed: expected 0, got $v");
echo "  ✅ A1 passed: {braced} 0 checked -> 0\n";

// TEST A2: 3 checked
$v = CalculationService::evaluate('count({field_AIGALEO})', ['field_AIGALEO' => ['1','2','3']], $fieldsMap);
assert($v === 3.0, "A2 failed: expected 3, got $v");
echo "  ✅ A2 passed: {braced} 3 checked -> 3\n";

// TEST A3: 7 checked (matches real values 98-104)
$v = CalculationService::evaluate('count({field_AIGALEO})', ['field_AIGALEO' => ['98','99','100','101','102','103','104']], $fieldsMap);
assert($v === 7.0, "A3 failed: expected 7, got $v");
echo "  ✅ A3 passed: {braced} 7 checked -> 7\n";

// TEST A4: count(A) + count(B)
$v = CalculationService::evaluate('count({field_AIGALEO}) + count({field_KALLITHA})', [
    'field_AIGALEO'  => ['1','2','3','4'],
    'field_KALLITHA' => ['1','2','3'],
], $fieldsMap);
assert($v === 7.0, "A4 failed: expected 7, got $v");
echo "  ✅ A4 passed: {braced} count(A)+count(B) = 4+3 -> 7\n";

// ============================================================
// GROUP B: BARE syntax  count(field_KEY)  — actual DB formula!
// ============================================================

// TEST B1: 0 selected — formula as stored in DB
$v = CalculationService::evaluate('count(field_ERGAZOMENOI)', ['field_ERGAZOMENOI' => []], $fieldsMap);
assert($v === 0.0, "B1 failed: expected 0, got $v");
echo "  ✅ B1 passed: bare syntax 0 checked -> 0\n";

// TEST B2: 1 selected
$v = CalculationService::evaluate('count(field_ERGAZOMENOI)', ['field_ERGAZOMENOI' => ['101']], $fieldsMap);
assert($v === 1.0, "B2 failed: expected 1, got $v");
echo "  ✅ B2 passed: bare syntax 1 checked -> 1\n";

// TEST B3: 5 selected (values 101-105, matching real user test)
$v = CalculationService::evaluate('count(field_ERGAZOMENOI)', ['field_ERGAZOMENOI' => ['101','102','103','104','105']], $fieldsMap);
assert($v === 5.0, "B3 failed: expected 5, got $v");
echo "  ✅ B3 passed: bare syntax 5 checked -> 5\n";

// TEST B4: uncheck one -> 4
$v = CalculationService::evaluate('count(field_ERGAZOMENOI)', ['field_ERGAZOMENOI' => ['101','102','103','104']], $fieldsMap);
assert($v === 4.0, "B4 failed: expected 4, got $v");
echo "  ✅ B4 passed: bare syntax uncheck one -> 4\n";

// TEST B5: count(field_A) + count(field_B) bare syntax
$v = CalculationService::evaluate('count(field_AIGALEO) + count(field_KALLITHA)', [
    'field_AIGALEO'  => ['1','2','3','4'],
    'field_KALLITHA' => ['1','2','3'],
], $fieldsMap);
assert($v === 7.0, "B5 failed: expected 7, got $v");
echo "  ✅ B5 passed: bare syntax count(A)+count(B) = 4+3 -> 7\n";

// ============================================================
// GROUP C: Regression – existing math functions still work
// ============================================================
$v = CalculationService::evaluate('sum({field_NUM}, 10)', ['field_NUM' => 5], $fieldsMap);
assert($v === 15.0, "C1 (sum) failed");
$v = CalculationService::evaluate('min({field_NUM}, 10)', ['field_NUM' => 5], $fieldsMap);
assert($v === 5.0, "C1 (min) failed");
$v = CalculationService::evaluate('max({field_NUM}, 10)', ['field_NUM' => 5], $fieldsMap);
assert($v === 10.0, "C1 (max) failed");
$v = CalculationService::evaluate('round(5.678, 2)', [], $fieldsMap);
assert($v === 5.68, "C1 (round) failed");
$v = CalculationService::evaluate('abs(0 - 12)', [], $fieldsMap);
assert($v === 12.0, "C1 (abs) failed");
echo "  ✅ C1 passed: sum, min, max, round, abs regression OK\n";

return true;


// TEST 1: 0 checked
$v1 = CalculationService::evaluate('count({field_AIGALEO})', ['field_AIGALEO' => []], $fieldsMap);
assert($v1 === 0.0, "Test 1 failed: expected 0, got $v1");
echo "  ✅ Test 1 passed (0 checked -> 0)\n";

// TEST 2: 3 checked
$v2 = CalculationService::evaluate('count({field_AIGALEO})', ['field_AIGALEO' => ['1', '2', '3']], $fieldsMap);
assert($v2 === 3.0, "Test 2 failed: expected 3, got $v2");
echo "  ✅ Test 2 passed (3 checked -> 3)\n";

// TEST 3: 8 checked
$v3 = CalculationService::evaluate('count({field_AIGALEO})', ['field_AIGALEO' => ['1', '2', '3', '4', '5', '6', '7', '8']], $fieldsMap);
assert($v3 === 8.0, "Test 3 failed: expected 8, got $v3");
echo "  ✅ Test 3 passed (8 checked -> 8)\n";

// TEST 4: Uncheck 2 (6 checked)
$v4 = CalculationService::evaluate('count({field_AIGALEO})', ['field_AIGALEO' => ['1', '3', '4', '5', '7', '8']], $fieldsMap);
assert($v4 === 6.0, "Test 4 failed: expected 6, got $v4");
echo "  ✅ Test 4 passed (uncheck 2 -> 6)\n";

// TEST 4b: 7 checked (values 98, 99, 100, 101, 102, 103, 104)
$v4b = CalculationService::evaluate('count({field_AIGALEO})', ['field_AIGALEO' => ['98', '99', '100', '101', '102', '103', '104']], $fieldsMap);
assert($v4b === 7.0, "Test 4b failed: expected 7, got $v4b");
echo "  ✅ Test 4b passed (7 selected options -> 7.00)\n";

// TEST 5: count(field_AIGALEO) + count(field_KALLITHA)
$v5 = CalculationService::evaluate('count({field_AIGALEO}) + count({field_KALLITHA})', [
    'field_AIGALEO' => ['1', '2', '3'],
    'field_KALLITHA' => ['1', '2']
], $fieldsMap);
assert($v5 === 5.0, "Test 5 failed: expected 5, got $v5");
echo "  ✅ Test 5 passed (count(A) + count(B) -> 5)\n";

// TEST 6: Existing math functions sum, min, max, round, abs
$v6_sum = CalculationService::evaluate('sum({field_NUM}, 10)', ['field_NUM' => 5], $fieldsMap);
assert($v6_sum === 15.0, "Test 6 (sum) failed");

$v6_min = CalculationService::evaluate('min({field_NUM}, 10)', ['field_NUM' => 5], $fieldsMap);
assert($v6_min === 5.0, "Test 6 (min) failed");

$v6_max = CalculationService::evaluate('max({field_NUM}, 10)', ['field_NUM' => 5], $fieldsMap);
assert($v6_max === 10.0, "Test 6 (max) failed");

$v6_round = CalculationService::evaluate('round(5.678, 2)', [], $fieldsMap);
assert($v6_round === 5.68, "Test 6 (round) failed");

$v6_abs = CalculationService::evaluate('abs(0 - 12)', [], $fieldsMap);
assert($v6_abs === 12.0, "Test 6 (abs) failed");
echo "  ✅ Test 6 passed (sum, min, max, round, abs working as before)\n";

return true;
