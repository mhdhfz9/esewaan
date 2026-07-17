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
        if (Schema::hasTable('premises')) {
            return;
        }
        Schema::create('premises', function (Blueprint $table) {
            $table->id();
            $table->string('nama_ptj')->comment('Contoh: AADK Daerah Besut');
            $table->string('negeri', 100);
            $table->string('daerah', 100);
            $table->text('alamat_penuh');
            $table->string('jenis_bangunan', 100)->nullable()->comment('kompleks | rumah kedai');
            $table->decimal('koordinat_lat', 10, 8)->nullable();
            $table->decimal('koordinat_long', 11, 8)->nullable();
            $table->string('nama_pemilik')->nullable();
            $table->text('alamat_pemilik')->nullable();
            $table->string('no_telefon_pemilik', 50)->nullable();
            $table->string('bank_pemilik', 100)->nullable();
            $table->string('no_akaun_bank', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('premises');
    }
};
