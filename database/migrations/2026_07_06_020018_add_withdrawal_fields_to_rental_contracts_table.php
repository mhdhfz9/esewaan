<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->string('withdrawal_status', 20)->nullable()->after('deleted_by_user_id');
            $table->text('withdrawal_reason')->nullable()->after('withdrawal_status');
            $table->timestamp('withdrawal_requested_at')->nullable()->after('withdrawal_reason');
            $table->foreignId('withdrawal_requested_by_user_id')->nullable()->after('withdrawal_requested_at')->constrained('users')->nullOnDelete();
            $table->timestamp('withdrawal_resolved_at')->nullable()->after('withdrawal_requested_by_user_id');
            $table->foreignId('withdrawal_resolved_by_user_id')->nullable()->after('withdrawal_resolved_at')->constrained('users')->nullOnDelete();
            $table->text('withdrawal_resolution_note')->nullable()->after('withdrawal_resolved_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('withdrawal_resolved_by_user_id');
            $table->dropConstrainedForeignId('withdrawal_requested_by_user_id');
            $table->dropColumn([
                'withdrawal_status',
                'withdrawal_reason',
                'withdrawal_requested_at',
                'withdrawal_resolved_at',
                'withdrawal_resolution_note',
            ]);
        });
    }
};
