<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $path = base_path('database/sql/003_e2_mgup.sql');
        DB::unprepared(file_get_contents($path));
    }

    public function down(): void
    {
        DB::unprepared(file_get_contents(base_path('database/sql/004_e2_rollback.sql')));
    }
};
