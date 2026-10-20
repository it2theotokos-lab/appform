<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\NotificationTemplateService;

function assertSameValue($actual, $expected, string $name): void {
    if ($actual !== $expected) {
        fwrite(STDERR, "FAIL {$name}: expected [{$expected}], got [{$actual}]\n");
        exit(1);
    }
    echo "PASS {$name}\n";
}

assertSameValue(
    NotificationTemplateService::formatSubmittedValue('yes', [
        'type' => 'select',
        'options' => [['value' => 'yes', 'label' => 'Yes'], ['value' => 'no', 'label' => 'No']],
    ]),
    'Yes',
    'select option uses display label'
);
assertSameValue(
    NotificationTemplateService::formatSubmittedValue('2026-11-11', ['type' => 'date']),
    '11/11/2026',
    'date is human readable'
);
assertSameValue(
    NotificationTemplateService::formatSubmittedValue('1', ['type' => 'checkbox', 'label' => 'Participation']),
    'Participation',
    'single checkbox uses meaningful label'
);
assertSameValue(
    NotificationTemplateService::formatSubmittedValue(null, ['type' => 'checkbox', 'label' => 'Participation']),
    '',
    'unchecked checkbox is omitted'
);
assertSameValue(
    NotificationTemplateService::formatSubmittedValue(['a', 'b'], [
        'type' => 'checkbox',
        'options' => [['value' => 'a', 'label' => 'Option A'], ['value' => 'b', 'label' => 'Option B']],
    ]),
    'Option A, Option B',
    'checkbox options use display labels'
);

