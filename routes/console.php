<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$backupEvent = Schedule::command('dkript:auto-backup')
    ->description('Respaldo automático programado del sistema Dkript (DB y Medios)');

try {
    if (Schema::hasTable('parameters')) {
        $settings = \App\Models\Parameter::getSystemSettings();
        $time = $settings->auto_backup_time ?: '02:00';
        $freq = $settings->auto_backup_frequency ?: 'daily';

        if ($freq === 'weekly') {
            $backupEvent->weeklyOn(1, $time);
        } elseif ($freq === 'monthly') {
            $backupEvent->monthlyOn(1, $time);
        } else {
            $backupEvent->dailyAt($time);
        }
    } else {
        $backupEvent->dailyAt('02:00');
    }
} catch (\Throwable $e) {
    $backupEvent->dailyAt('02:00');
}


