<?php
declare(strict_types=1);
namespace App\Core;
use PDO;
final class DB {
    private static ?PDO $pdo = null;
    public static function get(): PDO {
        if (self::$pdo) return self::$pdo;
        $dsn = 'mysql:host='.cfg('db_host').';port='.cfg('db_port').';dbname='.cfg('db_name').';charset=utf8mb4';
        self::$pdo = new PDO($dsn, (string)cfg('db_user'), (string)cfg('db_password'), [
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false,
        ]);
        return self::$pdo;
    }
    public static function all(string $sql, array $params=[]): array {
        $stmt = self::get()->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }
    public static function one(string $sql, array $params=[]): ?array {
        $stmt = self::get()->prepare($sql); $stmt->execute($params); return $stmt->fetch() ?: null;
    }
    public static function run(string $sql, array $params=[]): int {
        $stmt = self::get()->prepare($sql); $stmt->execute($params); return $stmt->rowCount();
    }
    public static function tx(callable $fn): mixed {
        $db=self::get(); $db->beginTransaction();
        try { $out=$fn($db); $db->commit(); return $out; }
        catch (\Throwable $ex) { if ($db->inTransaction()) $db->rollBack(); throw $ex; }
    }
}
