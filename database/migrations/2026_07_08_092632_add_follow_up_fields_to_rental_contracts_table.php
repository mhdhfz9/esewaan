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
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->foreignId('parent_contract_id')
                ->nullable()
                ->after('id')
                ->constrained('rental_contracts')
                ->nullOnDelete();
            $table->text('remark')->nullable()->after('catatan_hq');
            $table->timestamp('superseded_at')->nullable()->after('hq_approved_at');
            $table->foreignId('superseded_by_contract_id')
                ->nullable()
                ->after('superseded_at')
                ->constrained('rental_contracts')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_contract_id');
            $table->dropConstrainedForeignId('superseded_by_contract_id');
            $table->dropColumn(['remark', 'superseded_at']);
        });
    }
};
