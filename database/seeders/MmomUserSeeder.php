<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MmomUserSeeder extends Seeder
{
    public function run(): void
    {
        $passwordHash = '$2y$10$XhlB2Dp7M66PKtRvrZDDl.TXx64HCPusVOyH4s.xEgwC7T72Q34W.'; // password123

        // 1. Crear Administrador
        $adminId = Str::uuid()->toString();
        DB::table('identity.users')->updateOrInsert(
            ['email' => 'admin@mmom.com'],
            [
                'id' => $adminId,
                'display_name' => 'Administrador MMOM',
                'password_hash' => $passwordHash,
                'is_active' => true,
                'two_factor_confirmed_at' => now(),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $admin = DB::table('identity.users')->where('email', 'admin@mmom.com')->first();
        DB::table('identity.user_roles')->updateOrInsert(
            ['user_id' => $admin->id, 'role' => 'admin'],
            ['granted_at' => now()]
        );

        // 2. Crear 7 Jugadores (uno para cada discoteca)
        for ($i = 1; $i <= 7; $i++) {
            $playerId = Str::uuid()->toString();
            $email = "jugador{$i}@mmom.com";

            DB::table('identity.users')->updateOrInsert(
                ['email' => $email],
                [
                    'id' => $playerId,
                    'display_name' => "Gerente Discoteca {$i}",
                    'password_hash' => $passwordHash,
                    'is_active' => true,
                    'two_factor_confirmed_at' => now(),
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $user = DB::table('identity.users')->where('email', $email)->first();
            DB::table('identity.user_roles')->updateOrInsert(
                ['user_id' => $user->id, 'role' => 'player'],
                ['granted_at' => now()]
            );
        }
    }
}
