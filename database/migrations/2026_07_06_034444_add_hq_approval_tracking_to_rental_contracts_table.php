<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->timestamp('hq_approved_at')->nullable()->after('withdrawal_resolution_note');
            $table->foreignId('admin_negeri_user_id')->nullable()->after('hq_approved_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_negeri_user_id');
            $table->dropColumn('hq_approved_at');
        });
    }
};
