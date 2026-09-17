<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_documents', function (Blueprint $table) {
            $table->unsignedTinyInteger('semakan_round')->nullable()->after('jenis');
        });

        Schema::table('contract_documents', function (Blueprint $table) {
            $table->string('jenis', 50)->default('lain')->change();
        });
    }

    public function down(): void
    {
        Schema::table('contract_documents', function (Blueprint $table) {
            $table->dropColumn('semakan_round');
        });

        Schema::table('contract_documents', function (Blueprint $table) {
            $table->enum('jenis', ['surat_tawaran', 'gambar_premis', 'lain'])->default('lain')->change();
        });
    }
};
