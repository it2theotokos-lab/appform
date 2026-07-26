<?php
namespace App\Core;

use PDO;

abstract class Model {
    protected static $db;

    public static function init() {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
    }

    protected static function query(string $sql, array $params = []) {
        self::init();
        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    protected static function fetch(string $sql, array $params = []) {
        return self::query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }
}
Model::init();
