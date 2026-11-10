<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up():void { DB::connection('pgsql')->unprepared(self::readSql('013_e6_miec_mifiv.sql')); }
    public function down():void { DB::connection('pgsql')->unprepared(self::readSql('014_e6_rollback.sql')); }
    private static function readSql(string $filename): string
    {
        $path = database_path('sql/'.$filename);
        $sql = @file_get_contents($path);
        if ($sql === false) {
            throw new \RuntimeException('Falta el archivo SQL de MMOM: '.$path);
        }
        return $sql;
    }
};
