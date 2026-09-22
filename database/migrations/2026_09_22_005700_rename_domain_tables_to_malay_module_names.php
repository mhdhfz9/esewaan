<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align domain table names with module labels in e-Sewaan (snake_case).
     *
     * Kontrak Sewaan and Status Permohonan both read from `status_permohonan`
     * (full permohonan → kontrak lifecycle on one table).
     */
    public function up(): void
    {
        if (Schema::hasTable('premises') && ! Schema::hasTable('premis')) {
            Schema::rename('premises', 'premis');
        }

        if (Schema::hasTable('rental_contracts') && ! Schema::hasTable('status_permohonan')) {
            Schema::rename('rental_contracts', 'status_permohonan');
        }

        if (Schema::hasTable('contract_documents') && ! Schema::hasTable('dokumen_kontrak')) {
            Schema::rename('contract_documents', 'dokumen_kontrak');
        }

        if (Schema::hasTable('activity_logs') && ! Schema::hasTable('log_aktiviti')) {
            Schema::rename('activity_logs', 'log_aktiviti');
        }

        if (Schema::hasTable('process_logs') && ! Schema::hasTable('log_proses')) {
            Schema::rename('process_logs', 'log_proses');
        }

        if (Schema::hasTable('rental_contract_notification_views') && ! Schema::hasTable('notifikasi_status_permohonan')) {
            Schema::rename('rental_contract_notification_views', 'notifikasi_status_permohonan');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notifikasi_status_permohonan') && ! Schema::hasTable('rental_contract_notification_views')) {
            Schema::rename('notifikasi_status_permohonan', 'rental_contract_notification_views');
        }

        if (Schema::hasTable('log_proses') && ! Schema::hasTable('process_logs')) {
            Schema::rename('log_proses', 'process_logs');
        }

        if (Schema::hasTable('log_aktiviti') && ! Schema::hasTable('activity_logs')) {
            Schema::rename('log_aktiviti', 'activity_logs');
        }

        if (Schema::hasTable('dokumen_kontrak') && ! Schema::hasTable('contract_documents')) {
            Schema::rename('dokumen_kontrak', 'contract_documents');
        }

        if (Schema::hasTable('status_permohonan') && ! Schema::hasTable('rental_contracts')) {
            Schema::rename('status_permohonan', 'rental_contracts');
        }

        if (Schema::hasTable('premis') && ! Schema::hasTable('premises')) {
            Schema::rename('premis', 'premises');
        }
    }
};
