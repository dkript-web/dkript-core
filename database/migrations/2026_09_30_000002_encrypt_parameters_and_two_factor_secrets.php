<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ampliar columnas en 'parameters' para soportar payloads cifrados
        Schema::table('parameters', function (Blueprint $table) {
            $table->text('mail_password')->nullable()->change();
            $table->text('twilio_auth_token')->nullable()->change();
        });

        // 2. Migración de datos existentes e idempotente en 'parameters'
        $parameters = DB::table('parameters')->get();
        foreach ($parameters as $param) {
            $updates = [];
            foreach (['mail_password', 'twilio_auth_token', 'whatsapp_access_token'] as $field) {
                $value = $param->$field;
                if ($value !== null && $value !== '') {
                    try {
                        Crypt::decryptString($value);
                    } catch (\Throwable $e) {
                        $updates[$field] = Crypt::encryptString($value);
                    }
                }
            }
            if (!empty($updates)) {
                DB::table('parameters')->where('id', $param->id)->update($updates);
            }
        }

        // 3. Migración de datos existentes e idempotente en 'users' (2FA)
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $userUpdates = [];

            if ($user->two_factor_secret !== null && $user->two_factor_secret !== '') {
                try {
                    Crypt::decryptString($user->two_factor_secret);
                } catch (\Throwable $e) {
                    $userUpdates['two_factor_secret'] = Crypt::encryptString($user->two_factor_secret);
                }
            }

            if ($user->two_factor_recovery_codes !== null && $user->two_factor_recovery_codes !== '') {
                try {
                    Crypt::decrypt($user->two_factor_recovery_codes, false);
                } catch (\Throwable $e) {
                    $decoded = json_decode($user->two_factor_recovery_codes, true);
                    if (is_array($decoded)) {
                        $userUpdates['two_factor_recovery_codes'] = Crypt::encrypt(json_encode($decoded), false);
                    }
                }
            }

            if (!empty($userUpdates)) {
                DB::table('users')->where('id', $user->id)->update($userUpdates);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir cifrado en 'parameters' si es necesario
        $parameters = DB::table('parameters')->get();
        foreach ($parameters as $param) {
            $updates = [];
            foreach (['mail_password', 'twilio_auth_token', 'whatsapp_access_token'] as $field) {
                $value = $param->$field;
                if ($value !== null && $value !== '') {
                    try {
                        $updates[$field] = Crypt::decryptString($value);
                    } catch (\Throwable $e) {
                        // Conservar
                    }
                }
            }
            if (!empty($updates)) {
                DB::table('parameters')->where('id', $param->id)->update($updates);
            }
        }

        // Revertir cifrado en 'users'
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $userUpdates = [];
            if ($user->two_factor_secret !== null && $user->two_factor_secret !== '') {
                try {
                    $userUpdates['two_factor_secret'] = Crypt::decryptString($user->two_factor_secret);
                } catch (\Throwable $e) {
                    // Conservar
                }
            }
            if ($user->two_factor_recovery_codes !== null && $user->two_factor_recovery_codes !== '') {
                try {
                    $decryptedJson = Crypt::decrypt($user->two_factor_recovery_codes, false);
                    $userUpdates['two_factor_recovery_codes'] = $decryptedJson;
                } catch (\Throwable $e) {
                    // Conservar
                }
            }
            if (!empty($userUpdates)) {
                DB::table('users')->where('id', $user->id)->update($userUpdates);
            }
        }

        Schema::table('parameters', function (Blueprint $table) {
            $table->string('mail_password', 191)->nullable()->change();
            $table->string('twilio_auth_token', 100)->nullable()->change();
        });
    }
};
