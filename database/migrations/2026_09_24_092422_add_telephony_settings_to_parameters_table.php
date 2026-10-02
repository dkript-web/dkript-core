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
            $table->string('sms_provider', 30)->default('log')->after('error_display_mode');
            $table->string('twilio_account_sid', 100)->nullable()->after('sms_provider');
            $table->string('twilio_auth_token', 100)->nullable()->after('twilio_account_sid');
            $table->string('twilio_phone_number', 50)->nullable()->after('twilio_auth_token');
            $table->string('twilio_whatsapp_number', 50)->nullable()->after('twilio_phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parameters', function (Blueprint $table) {
            $table->dropColumn([
                'sms_provider',
                'twilio_account_sid',
                'twilio_auth_token',
                'twilio_phone_number',
                'twilio_whatsapp_number',
            ]);
        });
    }
};
