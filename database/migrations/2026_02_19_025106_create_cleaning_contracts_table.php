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
        if (Schema::hasTable('cleaning_contracts')) {
            return;
        }
        Schema::create('cleaning_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('premise_id')->constrained()->cascadeOnDelete();
            $table->string('nama_kontraktor');
            $table->string('no_pendaftaran_syarikat', 50)->nullable();
            $table->date('tarikh_mula');
            $table->date('tarikh_tamat');
            $table->decimal('nilai_kontrak_bulanan', 10, 2)->default(0);
            $table->decimal('nilai_kontrak_keseluruhan', 12, 2)->nullable();
            $table->string('no_fail_kontrak', 100)->nullable();
            $table->enum('status', ['aktif', 'tamat', 'baru'])->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleaning_contracts');
    }
};
