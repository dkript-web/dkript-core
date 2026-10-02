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
        Schema::table('parameters', function (Blueprint $table) {
            $table->boolean('auto_backup_enabled')->default(false)->after('mail_from_name');
            $table->string('auto_backup_frequency', 20)->default('daily')->after('auto_backup_enabled');
            $table->string('auto_backup_time', 10)->default('02:00')->after('auto_backup_frequency');
            $table->string('auto_backup_type', 20)->default('database')->after('auto_backup_time');
            $table->integer('auto_backup_max_retention')->default(7)->after('auto_backup_type');
            $table->timestamp('auto_backup_last_run_at')->nullable()->after('auto_backup_max_retention');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parameters', function (Blueprint $table) {
            $table->dropColumn([
                'auto_backup_enabled',
                'auto_backup_frequency',
                'auto_backup_time',
                'auto_backup_type',
                'auto_backup_max_retention',
                'auto_backup_last_run_at',
            ]);
        });
    }
};
