<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin_hq', 'admin_negeri'])->default('admin_negeri')->after('password');
            }
            if (! Schema::hasColumn('users', 'negeri')) {
                $table->string('negeri', 100)->nullable()->after('role')->comment('Untuk filter pengguna ikut negeri');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('users', 'negeri')) {
                $table->dropColumn('negeri');
            }
        });
    }
};
