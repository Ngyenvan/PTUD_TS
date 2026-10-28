<?php
declare(strict_types=1);
namespace App\Core;
final class Audit {
    public static function write(string $action,?string $details=null):void {
        DB::run('INSERT INTO audit_logs (actor_id,action,details,created_at) VALUES (?,?,?,NOW())', [$_SESSION['uid']??null,$action,$details]);
    }
}
