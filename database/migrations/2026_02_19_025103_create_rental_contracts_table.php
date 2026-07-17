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
        if (Schema::hasTable('rental_contracts')) {
            return;
        }
        Schema::create('rental_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('premise_id')->constrained()->cascadeOnDelete();
            $table->string('no_fail_rujukan', 100)->nullable()->comment('Contoh: AADK/BKP/PB 200-3/02');
            $table->date('tarikh_mula');
            $table->date('tarikh_tamat');
            $table->decimal('kadar_sewa_bulanan', 10, 2)->default(0);
            $table->decimal('keluasan_mp', 10, 2)->nullable();
            $table->enum('status_aktif', ['aktif', 'tamat_tempoh', 'dalam_proses', 'batal'])->default('aktif');
            $table->string('peringkat_proses', 100)->nullable()->comment('Contoh: Semakan PUU, Lulus BPH, Menunggu MOF');
            $table->date('tarikh_surat_niat')->nullable()->comment('Tarikh PTJ hantar surat niat sambung');
            $table->text('catatan_hq')->nullable();
            $table->timestamps();
            $table->index('tarikh_tamat');
            $table->index('status_aktif');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_contracts');
    }
};
