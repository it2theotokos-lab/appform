<?php
namespace App\Services\Update;

class UpdateStateMachine {
    // List of all valid states
    public const STATE_PENDING = 'pending';
    public const STATE_CHECKING_RELEASE = 'checking_release';
    public const STATE_DOWNLOADING = 'downloading';
    public const STATE_VERIFYING_PACKAGE = 'verifying_package';
    public const STATE_EXTRACTING = 'extracting';
    public const STATE_RUNNING_PRECHECKS = 'running_prechecks';
    public const STATE_WAITING_FOR_LOCK = 'waiting_for_lock';
    public const STATE_MAINTENANCE_ENABLED = 'maintenance_enabled';
    public const STATE_BACKING_UP_FILES = 'backing_up_files';
    public const STATE_BACKING_UP_DATABASE = 'backing_up_database';
    public const STATE_VALIDATING_BACKUPS = 'validating_backups';
    public const STATE_RUNNING_MIGRATIONS = 'running_migrations';
    public const STATE_RUNNING_SEED_UPDATES = 'running_seed_updates';
    public const STATE_STAGING_FILES = 'staging_files';
    public const STATE_INSTALLING_FILES = 'installing_files';
    public const STATE_CLEARING_CACHE = 'clearing_cache';
    public const STATE_VALIDATING_APPLICATION = 'validating_application';
    public const STATE_COMPLETED = 'completed';
    public const STATE_FAILED = 'failed';
    public const STATE_ROLLING_BACK = 'rolling_back';
    public const STATE_RESTORING_DATABASE = 'restoring_database';
    public const STATE_RESTORING_FILES = 'restoring_files';
    public const STATE_VALIDATING_ROLLBACK = 'validating_rollback';
    public const STATE_ROLLED_BACK = 'rolled_back';
    public const STATE_ROLLBACK_FAILED = 'rollback_failed';

    private static $allowedTransitions = [
        self::STATE_PENDING => [self::STATE_CHECKING_RELEASE, self::STATE_FAILED],
        self::STATE_CHECKING_RELEASE => [self::STATE_DOWNLOADING, self::STATE_FAILED],
        self::STATE_DOWNLOADING => [self::STATE_VERIFYING_PACKAGE, self::STATE_FAILED],
        self::STATE_VERIFYING_PACKAGE => [self::STATE_EXTRACTING, self::STATE_FAILED],
        self::STATE_EXTRACTING => [self::STATE_RUNNING_PRECHECKS, self::STATE_FAILED],
        self::STATE_RUNNING_PRECHECKS => [self::STATE_WAITING_FOR_LOCK, self::STATE_FAILED],
        self::STATE_WAITING_FOR_LOCK => [self::STATE_MAINTENANCE_ENABLED, self::STATE_FAILED],
        self::STATE_MAINTENANCE_ENABLED => [self::STATE_BACKING_UP_FILES, self::STATE_FAILED],
        self::STATE_BACKING_UP_FILES => [self::STATE_BACKING_UP_DATABASE, self::STATE_FAILED],
        self::STATE_BACKING_UP_DATABASE => [self::STATE_VALIDATING_BACKUPS, self::STATE_FAILED],
        self::STATE_VALIDATING_BACKUPS => [self::STATE_RUNNING_MIGRATIONS, self::STATE_FAILED],
        self::STATE_RUNNING_MIGRATIONS => [self::STATE_RUNNING_SEED_UPDATES, self::STATE_FAILED],
        self::STATE_RUNNING_SEED_UPDATES => [self::STATE_STAGING_FILES, self::STATE_FAILED],
        self::STATE_STAGING_FILES => [self::STATE_INSTALLING_FILES, self::STATE_FAILED],
        self::STATE_INSTALLING_FILES => [self::STATE_CLEARING_CACHE, self::STATE_FAILED],
        self::STATE_CLEARING_CACHE => [self::STATE_VALIDATING_APPLICATION, self::STATE_FAILED],
        self::STATE_VALIDATING_APPLICATION => [self::STATE_COMPLETED, self::STATE_FAILED],
        self::STATE_FAILED => [self::STATE_ROLLING_BACK],
        self::STATE_ROLLING_BACK => [self::STATE_RESTORING_DATABASE, self::STATE_ROLLBACK_FAILED],
        self::STATE_RESTORING_DATABASE => [self::STATE_RESTORING_FILES, self::STATE_ROLLBACK_FAILED],
        self::STATE_RESTORING_FILES => [self::STATE_VALIDATING_ROLLBACK, self::STATE_ROLLBACK_FAILED],
        self::STATE_VALIDATING_ROLLBACK => [self::STATE_ROLLED_BACK, self::STATE_ROLLBACK_FAILED],
        self::STATE_COMPLETED => [],
        self::STATE_ROLLED_BACK => [],
        self::STATE_ROLLBACK_FAILED => []
    ];

    public static function isValidTransition(string $from, string $to): bool {
        if (!isset(self::$allowedTransitions[$from])) {
            return false;
        }
        return in_array($to, self::$allowedTransitions[$from], true);
    }

    public static function validateTransition(string $from, string $to) {
        if (!self::isValidTransition($from, $to)) {
            throw new \InvalidArgumentException("Invalid state transition from '{$from}' to '{$to}'");
        }
    }
}
