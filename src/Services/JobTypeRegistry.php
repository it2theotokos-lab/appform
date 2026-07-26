<?php
namespace App\Services;

class JobTypeRegistry {
    private static $registry = [
        'send_email' => \App\Jobs\SendEmailJob::class,
        'generate_pdf' => \App\Jobs\GeneratePdfJob::class,
        'generate_excel' => \App\Jobs\GenerateExcelJob::class,
        'cloud_upload' => \App\Jobs\CloudUploadJob::class,
        'backup' => \App\Jobs\BackupJob::class,
        'restore' => \App\Jobs\RestoreJob::class,
        'example_reports.generate' => \App\Jobs\GenerateReportJob::class
    ];

    public static function resolve(string $alias): ?string {
        return self::$registry[$alias] ?? null;
    }

    public static function getAlias(string $class): ?string {
        $key = array_search($class, self::$registry);
        return $key !== false ? $key : null;
    }
}
