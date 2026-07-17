<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rental_contracts')
            ->where('workflow_tahap', 'menunggu_balasan_ptj')
            ->update(['workflow_tahap' => 'menunggu_semakan_negeri']);

        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        $ptjUserIds = DB::table('users')->where('role', 'ptj_user')->pluck('id');

        if ($ptjUserIds->isNotEmpty()) {
            DB::table('rental_contracts')
                ->whereIn('submitted_by_user_id', $ptjUserIds)
                ->update(['submitted_by_user_id' => null]);

            DB::table('users')->whereIn('id', $ptjUserIds)->delete();
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin_hq', 'admin_negeri') NOT NULL DEFAULT 'admin_negeri'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin_hq', 'admin_negeri', 'ptj_user') NOT NULL DEFAULT 'admin_negeri'");
        }
    }
};
