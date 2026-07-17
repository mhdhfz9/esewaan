<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('rental_contracts', 'workflow_tahap')) {
                $table->string('workflow_tahap', 50)
                    ->default('menunggu_semakan_negeri')
                    ->after('catatan_hq')
                    ->comment('Aliran permohonan: menunggu_balasan_ptj | menunggu_semakan_negeri');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('rental_contracts', 'workflow_tahap')) {
                $table->dropColumn('workflow_tahap');
            }
        });
    }
};
