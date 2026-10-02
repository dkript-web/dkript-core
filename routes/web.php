<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ParameterController;
use App\Http\Controllers\OtpPasswordResetController;
use App\Http\Controllers\EmailPasswordResetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\TwoFactorAuthController;
use App\Http\Controllers\UserSessionController;
use App\Http\Controllers\UserProfileController;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Rutas Públicas (Huéspedes)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    // Autenticación de Dos Factores (2FA Challenge)
    Route::get('/two-factor-challenge', [TwoFactorAuthController::class, 'showChallenge'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorAuthController::class, 'verifyChallenge'])->name('two-factor.verify');

    // Recuperación y Verificación OTP vía SMS y WhatsApp
    Route::get('/forgot-password', [OtpPasswordResetController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password/send-otp', [OtpPasswordResetController::class, 'sendOtp'])->name('password.send-otp');
    Route::get('/verify-otp', [OtpPasswordResetController::class, 'showVerifyOtp'])->name('password.verify-otp');
    Route::post('/verify-otp', [OtpPasswordResetController::class, 'verifyOtp'])->name('password.verify');
    Route::post('/resend-otp', [OtpPasswordResetController::class, 'resendOtp'])->name('password.resend-otp');
    Route::get('/reset-password', [OtpPasswordResetController::class, 'showResetPassword'])->name('password.reset-form');
    Route::post('/reset-password', [OtpPasswordResetController::class, 'resetPassword'])->name('password.update');

    // Recuperación Tradicional por Correo Electrónico (Email Token)
    Route::post('/forgot-password/email', [EmailPasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password-email/{token}', [EmailPasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password-email', [EmailPasswordResetController::class, 'resetPassword'])->name('password.update-email');
});

// Rutas Protegidas (Autenticadas + Control de Inactividad)
Route::middleware(['auth', 'inactivity.timeout'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Perfil de Usuario Autenticado (Edición de identidad con confirmación y Avatar)
    Route::put('/user/profile', [UserProfileController::class, 'update'])->name('user.profile.update');
    Route::post('/user/profile/avatar', [UserProfileController::class, 'uploadAvatar'])->name('user.profile.avatar');

    // Latido de sesión activa (Keep-Alive / Ping para prevención de inactividad)
    Route::post('/session/ping', function () {
        session(['last_activity_time' => time()]);
        return response()->json([
            'success' => true,
            'timestamp' => time(),
            'timeout_minutes' => (int) (\App\Models\Parameter::getSystemSettings()->session_timeout_minutes ?: 15),
        ]);
    })->name('session.ping');

    // Gestión de Autenticación de Dos Factores (2FA TOTP)
    Route::prefix('user/two-factor')->name('user.two-factor.')->group(function () {
        Route::post('/enable', [TwoFactorAuthController::class, 'enable'])->name('enable');
        Route::post('/confirm', [TwoFactorAuthController::class, 'confirm'])->name('confirm');
        Route::delete('/disable', [TwoFactorAuthController::class, 'disable'])->name('disable');
        Route::get('/recovery-codes', [TwoFactorAuthController::class, 'getRecoveryCodes'])->name('recovery-codes');
        Route::post('/recovery-codes/regenerate', [TwoFactorAuthController::class, 'regenerateRecoveryCodes'])->name('regenerate-codes');
    });

    // Gestor de Sesiones Activas & Dispositivos Conectados
    Route::prefix('user/sessions')->name('user.sessions.')->group(function () {
        Route::get('/', [UserSessionController::class, 'index'])->name('index');
        Route::delete('/{sessionId}', [UserSessionController::class, 'destroy'])->name('destroy');
        Route::post('/logout-others', [UserSessionController::class, 'logoutOthers'])->name('logout-others');
    });

    // Centro de Notificaciones In-App
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/', [NotificationController::class, 'clearAll'])->name('clear-all');
    });

    // Módulo 1: Dashboard
    Route::middleware('verify.option:1')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    // Módulo 2: Usuarios
    Route::middleware('verify.option:2')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/export/excel', [UserController::class, 'exportExcel'])->middleware('verify.position:2,5')->name('users.export.excel');
        Route::get('/users/export/pdf', [UserController::class, 'exportPdf'])->middleware('verify.position:2,4')->name('users.export.pdf');
        Route::post('/users', [UserController::class, 'store'])->middleware('verify.position:2,1')->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->middleware('verify.position:2,2')->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('verify.position:2,3')->name('users.destroy');
    });

    // Módulo 3: Roles y Permisos RBAC
    Route::middleware('verify.option:3')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/export/excel', [RoleController::class, 'exportExcel'])->middleware('verify.position:3,5')->name('roles.export.excel');
        Route::get('/roles/export/pdf', [RoleController::class, 'exportPdf'])->middleware('verify.position:3,4')->name('roles.export.pdf');
        Route::post('/roles', [RoleController::class, 'store'])->middleware('verify.position:3,1')->name('roles.store');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('verify.position:3,2')->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('verify.position:3,2')->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('verify.position:3,3')->name('roles.destroy');
    });

    // Módulo 4: Permisos
    Route::middleware('verify.option:4')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/export/excel', [PermissionController::class, 'exportExcel'])->middleware('verify.position:4,5')->name('permissions.export.excel');
        Route::get('/permissions/export/pdf', [PermissionController::class, 'exportPdf'])->middleware('verify.position:4,4')->name('permissions.export.pdf');
    });

    // Módulo 5: Parámetros del Sistema
    Route::middleware('verify.option:5')->group(function () {
        Route::get('/parameters', [ParameterController::class, 'index'])->name('parameters.index');
        Route::post('/parameters', [ParameterController::class, 'update'])->middleware('verify.position:5,2')->name('parameters.update');
        Route::post('/parameters/test-smtp', [ParameterController::class, 'testSmtp'])->middleware('verify.position:5,2')->name('parameters.test-smtp');
        Route::post('/parameters/test-whatsapp', [ParameterController::class, 'testWhatsApp'])->middleware('verify.position:5,2')->name('parameters.test-whatsapp');
        Route::post('/parameters/clear-cache', [ParameterController::class, 'clearCache'])->middleware('verify.position:5,2')->name('parameters.clear-cache');
        Route::post('/parameters/upload-image', [ParameterController::class, 'uploadImage'])->middleware('verify.position:5,1')->name('parameters.upload-image');
        Route::post('/parameters/rename-image', [ParameterController::class, 'renameImage'])->middleware('verify.position:5,2')->name('parameters.rename-image');
        Route::post('/parameters/delete-image', [ParameterController::class, 'deleteImage'])->middleware('verify.position:5,3')->name('parameters.delete-image');
        Route::get('/parameters/errors/preview/{code}', [\App\Http\Controllers\ErrorPreviewController::class, 'preview'])->name('parameters.errors.preview');
        Route::post('/parameters/errors/update', [ParameterController::class, 'updateErrorPage'])->middleware('verify.position:5,2')->name('parameters.errors.update');
        Route::post('/parameters/errors/upload-media', [ParameterController::class, 'uploadErrorMedia'])->middleware('verify.position:5,1')->name('parameters.errors.upload-media');
        Route::post('/parameters/errors/delete-media', [ParameterController::class, 'deleteErrorMedia'])->middleware('verify.position:5,3')->name('parameters.errors.delete-media');

        // Respaldos del Sistema (Backups)
        Route::get('/backups', [\App\Http\Controllers\BackupController::class, 'index'])->middleware('verify.position:5,4')->name('backups.index');
        Route::get('/backups/scheduler-status', [\App\Http\Controllers\BackupController::class, 'schedulerStatus'])->middleware('verify.position:5,4')->name('backups.scheduler.status');
        Route::post('/backups/auto-settings', [\App\Http\Controllers\BackupController::class, 'updateAutoBackupSettings'])->middleware('verify.position:5,2')->name('backups.auto-settings');
        Route::post('/backups/run-auto', [\App\Http\Controllers\BackupController::class, 'runAutoBackupNow'])->middleware('verify.position:5,1')->name('backups.run-auto');
        Route::post('/backups/database', [\App\Http\Controllers\BackupController::class, 'createDatabaseBackup'])->middleware('verify.position:5,1')->name('backups.database');
        Route::post('/backups/media', [\App\Http\Controllers\BackupController::class, 'createMediaBackup'])->middleware('verify.position:5,1')->name('backups.media');
        Route::get('/backups/download/{filename}', [\App\Http\Controllers\BackupController::class, 'download'])->where('filename', '.*')->middleware('verify.position:5,4')->name('backups.download');
        Route::delete('/backups/{filename}', [\App\Http\Controllers\BackupController::class, 'destroy'])->where('filename', '.*')->middleware('verify.position:5,3')->name('backups.destroy');
        Route::post('/backups/restore/{filename}', [\App\Http\Controllers\BackupController::class, 'restore'])->where('filename', '.*')->middleware('verify.position:5,5')->name('backups.restore');
    });

    // Módulo 6: Registros de Auditoría
    Route::middleware('verify.option:6')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/export/excel', [AuditLogController::class, 'exportExcel'])->middleware('verify.position:6,5')->name('audit-logs.export.excel');
        Route::get('/audit-logs/export/pdf', [AuditLogController::class, 'exportPdf'])->middleware('verify.position:6,4')->name('audit-logs.export.pdf');
        Route::delete('/audit-logs/clear', [AuditLogController::class, 'clear'])->middleware('verify.position:6,3')->name('audit-logs.clear');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->middleware('verify.position:6,4')->name('audit-logs.show');
        Route::delete('/audit-logs/{auditLog}', [AuditLogController::class, 'destroy'])->middleware('verify.position:6,3')->name('audit-logs.destroy');
    });
});
