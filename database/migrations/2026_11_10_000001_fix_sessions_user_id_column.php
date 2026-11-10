<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sessions')) {
            try {
                DB::statement('ALTER TABLE sessions ALTER COLUMN user_id TYPE varchar(255);');
            } catch (\Throwable $e) {
                // Si la columna ya es varchar o no existe, continuar
            }
        }
    }

    public function down(): void
    {
        //
    }
};
