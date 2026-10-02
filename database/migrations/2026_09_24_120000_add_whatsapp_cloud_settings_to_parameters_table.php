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
            $table->string('whatsapp_phone_number_id', 50)->nullable()->after('twilio_whatsapp_number');
            $table->text('whatsapp_access_token')->nullable()->after('whatsapp_phone_number_id');
            $table->string('whatsapp_business_account_id', 50)->nullable()->after('whatsapp_access_token');
            $table->string('whatsapp_api_version', 20)->default('v20.0')->after('whatsapp_business_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parameters', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_phone_number_id',
                'whatsapp_access_token',
                'whatsapp_business_account_id',
                'whatsapp_api_version',
            ]);
        });
    }
};
