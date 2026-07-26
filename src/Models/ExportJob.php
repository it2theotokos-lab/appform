<?php
namespace App\Models;

use App\Core\Model;

class ExportJob extends Model {
    public static function findByUuid(string $uuid) {
        return self::fetch("SELECT * FROM export_jobs WHERE uuid = ?", [$uuid]);
    }
}
