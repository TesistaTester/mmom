<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL-only. Use Laravel's configured pgsql connection.
        $sql = file_get_contents(database_path('sql/001_foundation.sql'));
        if ($sql === false) {
            throw new RuntimeException('Missing database/sql/001_foundation.sql');
        }
        DB::connection('pgsql')->unprepared($sql);
    }

    public function down(): void
    {
        $sql = file_get_contents(database_path('sql/001_rollback.sql'));
        if ($sql === false) {
            throw new RuntimeException('Missing database/sql/001_rollback.sql');
        }
        DB::connection('pgsql')->unprepared($sql);
    }
};
