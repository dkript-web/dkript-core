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
            $table->string('mail_mailer', 30)->default('log')->after('twilio_whatsapp_number');
            $table->string('mail_host', 191)->nullable()->after('mail_mailer');
            $table->unsignedInteger('mail_port')->default(587)->nullable()->after('mail_host');
            $table->string('mail_username', 191)->nullable()->after('mail_port');
            $table->string('mail_password', 191)->nullable()->after('mail_username');
            $table->string('mail_encryption', 20)->default('tls')->nullable()->after('mail_password');
            $table->string('mail_from_address', 191)->nullable()->after('mail_encryption');
            $table->string('mail_from_name', 191)->nullable()->after('mail_from_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parameters', function (Blueprint $table) {
            $table->dropColumn([
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name',
            ]);
        });
    }
};