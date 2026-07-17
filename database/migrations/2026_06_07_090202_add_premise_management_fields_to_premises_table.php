<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('premises', function (Blueprint $table) {
            $table->string('status_premis', 50)->default('kosong')->after('jenis_bangunan');
            $table->date('tarikh_mula')->nullable()->after('status_premis');
            $table->date('tarikh_akhir')->nullable()->after('tarikh_mula');
            $table->decimal('kadar_sewa', 10, 2)->nullable()->after('tarikh_akhir');
            $table->string('no_hak_milik', 100)->nullable()->after('nama_pemilik');
            $table->string('status_pemilikan', 50)->nullable()->after('no_hak_milik');
            $table->text('catatan_hak_milik')->nullable()->after('status_pemilikan');
        });
    }

    public function down(): void
    {
        Schema::table('premises', function (Blueprint $table) {
            $table->dropColumn([
                'status_premis',
                'tarikh_mula',
                'tarikh_akhir',
                'kadar_sewa',
                'no_hak_milik',
                'status_pemilikan',
                'catatan_hak_milik',
            ]);
        });
    }
};
