<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SynapseAdminUserRoleSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasTable('roles') || !Schema::hasTable('users_roles')) {
            $this->command?->error("Tables manquantes (users / roles / users_roles).");
            return;
        }

        $userId = DB::table('users')->where('email', 'admin@synapse.com')->value('id');
        $roleId = DB::table('roles')->where('label', 'admin')->value('id');

        if (!$userId || !$roleId) {
            $this->command?->error("User admin ou rôle admin introuvable.");
            return;
        }

        $urTable = 'users_roles';
        $userCol = Schema::hasColumn($urTable, 'users_id') ? 'users_id' : (Schema::hasColumn($urTable, 'user_id') ? 'user_id' : null);
        $roleCol = Schema::hasColumn($urTable, 'roles_id') ? 'roles_id' : (Schema::hasColumn($urTable, 'role_id') ? 'role_id' : null);

        if (!$userCol || !$roleCol) {
            $this->command?->error("Colonnes inattendues dans users_roles. Attendu users_id & roles_id.");
            return;
        }

        $payload = [
            $userCol => $userId,
            $roleCol => $roleId,
        ];

        if (Schema::hasColumn($urTable, 'created_at')) $payload['created_at'] = now();
        if (Schema::hasColumn($urTable, 'updated_at')) $payload['updated_at'] = now();

        DB::table($urTable)->updateOrInsert(
            [$userCol => $userId, $roleCol => $roleId],
            $payload
        );

        $this->command?->info("✅ Admin assigné au rôle admin (user: admin@synapse.com).");
    }
}
