<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parameter;
use App\Models\SystemImage;
use App\Services\NotificationService;
use App\Services\AuditService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class ParameterController extends Controller
{
    public function index()
    {
        $parameter = Parameter::getSystemSettings();
        $images = SystemImage::orderBy('id', 'asc')->get();

        $currentImage = $images->firstWhere('path', $parameter->system_logo) ?: $images->first();
        $currentImageName = $currentImage ? $currentImage->name : 'Logotipo del Sistema';

        return view('parameters.index', compact('parameter', 'images', 'currentImage', 'currentImageName'));
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg,gif', 'max:25600'],
            'name' => ['nullable', 'string', 'max:100'],
        ], [
            'image.required' => 'Debe seleccionar un archivo de imagen.',
            'image.file' => 'El archivo seleccionado debe ser un archivo válido.',
            'image.mimes' => 'El formato debe ser PNG, JPG, JPEG, WEBP, SVG o GIF.',
            'image.max' => 'La imagen no debe superar los 25 MB.',
            'image.uploaded' => 'El archivo supera el tamaño máximo permitido por el servidor.',
        ]);

        $file = $request->file('image');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'png');

        // Sanitización de seguridad para archivos SVG
        if ($extension === 'svg') {
            $svgContent = @file_get_contents($file->getRealPath());
            if ($svgContent && preg_match('/<\s*script|javascript:|onload|onerror|onclick|data:|<!ENTITY/i', $svgContent)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo SVG contiene scripts o elementos inseguros no permitidos.',
                ], 422);
            }
        }

        $destinationPath = public_path('uploads/branding');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $filename = time() . '_' . Str::slug($originalName) . '.' . $extension;

        $targetFile = $destinationPath . DIRECTORY_SEPARATOR . $filename;
        if (!@copy($file->getRealPath(), $targetFile)) {
            $file->move($destinationPath, $filename);
        }

        // Recorte automático de márgenes transparentes excesivos
        $this->trimTransparentMargins($targetFile, $extension);

        // Optimización complementaria en servidor con GD (si excede 1400px)
        if (extension_loaded('gd') && in_array(strtolower($extension), ['png', 'jpg', 'jpeg', 'webp']) && file_exists($targetFile)) {
            try {
                $imageInfo = @getimagesize($targetFile);
                if ($imageInfo) {
                    [$w, $h] = $imageInfo;
                    $isSquareOrPortrait = ($w / $h) <= 1.25;
                    $maxDim = $isSquareOrPortrait ? 800 : 1200;
                    if ($w > $maxDim || $h > $maxDim) {
                        if ($w > $h) {
                            $newH = (int)round(($h * $maxDim) / $w);
                            $newW = $maxDim;
                        } else {
                            $newW = (int)round(($w * $maxDim) / $h);
                            $newH = $maxDim;
                        }

                        $srcImg = match (strtolower($extension)) {
                            'png' => @imagecreatefrompng($targetFile),
                            'jpg', 'jpeg' => @imagecreatefromjpeg($targetFile),
                            'webp' => @imagecreatefromwebp($targetFile),
                            default => null,
                        };

                        if ($srcImg) {
                            $dstImg = imagecreatetruecolor($newW, $newH);
                            if (in_array(strtolower($extension), ['png', 'webp'])) {
                                imagealphablending($dstImg, false);
                                imagesavealpha($dstImg, true);
                            }
                            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $w, $h);

                            match (strtolower($extension)) {
                                'png' => imagepng($dstImg, $targetFile, 9),
                                'jpg', 'jpeg' => imagejpeg($dstImg, $targetFile, 88),
                                'webp' => imagewebp($dstImg, $targetFile, 88),
                            };

                            imagedestroy($srcImg);
                            imagedestroy($dstImg);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Preservar archivo sin interrumpir en caso de excepción GD
            }
        }

        $relativePath = 'uploads/branding/' . $filename;

        $displayName = $request->input('name') 
            ? trim($request->input('name')) 
            : ucwords(str_replace(['-', '_'], ' ', $originalName));

        $systemImage = SystemImage::create([
            'name' => $displayName,
            'path' => $relativePath,
            'is_preset' => false,
        ]);

        // Actualizar el logotipo actual en parameters
        $parameter = Parameter::first();
        if ($parameter) {
            $parameter->system_logo = $relativePath;
            $parameter->save();
        }

        \Illuminate\Support\Facades\Log::info('BRANDING_IMAGE_UPLOADED', [
            'actor_id' => $request->user()->id,
            'path' => $relativePath,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Imagen subida y guardada exitosamente.',
            'image' => $systemImage,
            'asset_url' => asset($relativePath),
        ]);
    }

    public function renameImage(Request $request)
    {
        $validated = $request->validate([
            'path' => ['required', 'string'],
            'name' => ['required', 'string', 'max:100'],
        ]);

        $image = SystemImage::updateOrCreate(
            ['path' => $validated['path']],
            ['name' => trim($validated['name'])]
        );

        return response()->json([
            'success' => true,
            'message' => 'Nombre de la imagen actualizado exitosamente.',
            'image' => $image,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'system_name' => ['required', 'string', 'max:255'],
            'system_logo' => ['nullable', 'string', 'max:255'],
            'show_brand_text' => ['nullable', 'in:0,1,true,false'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'records_per_page' => ['required', 'integer', 'min:5', 'max:100'],
            'session_timeout_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'maintenance_mode' => ['required', 'in:0,1'],
            'modal_style' => ['required', 'in:corporate,glassmorphism,window,minimal'],
            'error_display_mode' => ['nullable', 'in:scene,fullscreen'],
            'current_logo_name' => ['nullable', 'string', 'max:100'],
            'sms_provider' => ['nullable', 'in:log,twilio,meta_whatsapp,whatsapp_cloud'],
            'twilio_account_sid' => ['nullable', 'string', 'max:100'],
            'twilio_auth_token' => ['nullable', 'string', 'max:100'],
            'twilio_phone_number' => ['nullable', 'string', 'max:50'],
            'twilio_whatsapp_number' => ['nullable', 'string', 'max:50'],
            'whatsapp_phone_number_id' => ['nullable', 'string', 'max:50'],
            'whatsapp_access_token' => ['nullable', 'string'],
            'whatsapp_business_account_id' => ['nullable', 'string', 'max:50'],
            'whatsapp_api_version' => ['nullable', 'string', 'max:20'],
            'mail_mailer' => ['nullable', 'in:log,smtp'],
            'mail_host' => ['nullable', 'string', 'max:191'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:191'],
            'mail_password' => ['nullable', 'string', 'max:191'],
            'mail_encryption' => ['nullable', 'in:tls,ssl,none'],
            'mail_from_address' => ['nullable', 'email', 'max:191'],
            'mail_from_name' => ['nullable', 'string', 'max:191'],
        ]);

        $parameter = Parameter::first();
        if (!$parameter) {
            $parameter = new Parameter();
        }

        $auditFields = [
            'system_name', 'system_logo', 'show_brand_text', 'contact_email',
            'records_per_page', 'session_timeout_minutes', 'maintenance_mode',
            'modal_style', 'error_display_mode', 'sms_provider', 'mail_mailer',
        ];
        $oldAuditValues = $parameter->only($auditFields);

        // Si twilio_auth_token viene vacío, no sobreescribir el existente a menos que se cambie explícitamente
        if (array_key_exists('twilio_auth_token', $validated) && empty($validated['twilio_auth_token'])) {
            unset($validated['twilio_auth_token']);
        }

        // Si whatsapp_access_token viene vacío, no sobreescribir el token existente
        if (array_key_exists('whatsapp_access_token', $validated) && empty($validated['whatsapp_access_token'])) {
            unset($validated['whatsapp_access_token']);
        }

        // Si mail_password viene vacío, no sobreescribir la contraseña existente
        if (array_key_exists('mail_password', $validated) && empty($validated['mail_password'])) {
            unset($validated['mail_password']);
        }

        $parameter->fill($validated);
        if ($request->has('show_brand_text')) {
            $parameter->show_brand_text = (bool)$request->input('show_brand_text');
        } elseif ($parameter->show_brand_text === null) {
            $parameter->show_brand_text = true;
        }
        $parameter->save();

        if (!empty($validated['current_logo_name']) && !empty($validated['system_logo'])) {
            SystemImage::updateOrCreate(
                ['path' => $validated['system_logo']],
                ['name' => trim($validated['current_logo_name'])]
            );
        }

        $newAuditValues = $parameter->only($auditFields);
        $auditLog = AuditService::log(
            'SETTINGS',
            'SYSTEM',
            'Actualización de configuración institucional del sistema',
            $oldAuditValues,
            $newAuditValues
        );

        \Illuminate\Support\Facades\Log::info('PARAMETERS_UPDATED', [
            'actor_id' => auth()->id(),
            'system_name' => $parameter->system_name,
            'ip' => $request->ip(),
        ]);

        $incidenceUrl = $auditLog 
            ? route('audit-logs.index', ['detail' => $auditLog->id])
            : route('audit-logs.index');

        NotificationService::broadcastToAdmins(
            'Parámetros Actualizados',
            "El usuario " . (auth()->user()?->name ?? 'Sistema') . " ha modificado la configuración institucional del sistema.",
            'system',
            $incidenceUrl
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Parámetros del sistema actualizados exitosamente.',
                'parameter' => $parameter,
                'logo_url' => asset($parameter->system_logo ?: 'assets/images/branding/logo-dkript.png'),
                'show_brand_text' => (bool)$parameter->show_brand_text,
                'system_name' => $parameter->system_name,
                'modal_style' => $parameter->modal_style,
                'error_display_mode' => $parameter->error_display_mode,
                'session_timeout_minutes' => $parameter->session_timeout_minutes,
            ]);
        }

        return redirect()->route('parameters.index')->with('success', 'Parámetros del sistema actualizados exitosamente.');
    }

    public function deleteImage(Request $request)
    {
        $request->validate([
            'path' => ['required', 'string'],
            'admin_password' => ['required', 'string'],
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($request->admin_password, $request->user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Contraseña de administrador incorrecta.',
            ], 422);
        }

        $path = $request->input('path');
        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');

        // Blindaje estricto contra Path Traversal: solo se permite eliminar en uploads/branding/
        if (str_contains($normalizedPath, '..') || !str_starts_with($normalizedPath, 'uploads/branding/')) {
            \Illuminate\Support\Facades\Log::warning('PATH_TRAVERSAL_ATTEMPT_BLOCKED', [
                'user_id' => $request->user()->id,
                'user_email' => $request->user()->email,
                'attempted_path' => $path,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado: Ruta de archivo no autorizada o inválida.',
            ], 403);
        }

        $image = SystemImage::where('path', $normalizedPath)
            ->orWhere('path', $path)
            ->orWhere('path', '/' . $normalizedPath)
            ->first();

        if ($image) {
            $image->delete();
        }

        // Eliminar del servidor físico verificando confinamiento estricto en uploads/branding
        $brandingDir = realpath(public_path('uploads/branding'));
        $fullPath = public_path($normalizedPath);
        $realTarget = realpath($fullPath);

        if ($brandingDir && $realTarget && str_starts_with($realTarget, $brandingDir) && is_file($realTarget)) {
            @unlink($realTarget);
        }

        \Illuminate\Support\Facades\Log::info('BRANDING_IMAGE_DELETED', [
            'user_id' => $request->user()->id,
            'user_email' => $request->user()->email,
            'path' => $normalizedPath,
            'ip' => $request->ip(),
        ]);

        $parameter = Parameter::first();
        $newLogo = null;
        if ($parameter && ($parameter->system_logo === $path || $parameter->system_logo === $normalizedPath)) {
            $nextImage = SystemImage::first();
            $parameter->system_logo = $nextImage ? $nextImage->path : 'assets/images/branding/logo-dkript.png';
            $parameter->save();

            $newLogo = [
                'path' => $parameter->system_logo,
                'name' => $nextImage ? $nextImage->name : 'Dkript Logo',
                'url' => asset($parameter->system_logo),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Imagen eliminada exitosamente del sistema y del servidor.',
            'deleted_path' => $path,
            'new_logo' => $newLogo,
            'total_count' => SystemImage::count(),
        ]);
    }

    /**
     * Recorta márgenes transparentes excesivos en imágenes PNG/WEBP
     */
    protected function trimTransparentMargins(string $filePath, string $extension): void
    {
        if (!extension_loaded('gd') || !in_array(strtolower($extension), ['png', 'webp']) || !file_exists($filePath)) {
            return;
        }

        try {
            $srcImg = match (strtolower($extension)) {
                'png' => @imagecreatefrompng($filePath),
                'webp' => @imagecreatefromwebp($filePath),
                default => null,
            };

            if (!$srcImg) return;

            $w = imagesx($srcImg);
            $h = imagesy($srcImg);
            $top = $h; $bottom = 0; $left = $w; $right = 0;
            $hasVisiblePixels = false;

            // Escaneo eficiente con paso de 2 píxeles
            for ($x = 0; $x < $w; $x += 2) {
                for ($y = 0; $y < $h; $y += 2) {
                    $rgba = imagecolorat($srcImg, $x, $y);
                    $alpha = ($rgba & 0x7F000000) >> 24;
                    if ($alpha < 120) { // Pixel visible
                        $hasVisiblePixels = true;
                        if ($x < $left) $left = $x;
                        if ($x > $right) $right = $x;
                        if ($y < $top) $top = $y;
                        if ($y > $bottom) $bottom = $y;
                    }
                }
            }

            if ($hasVisiblePixels && ($left > 12 || $top > 12 || ($w - $right) > 12 || ($h - $bottom) > 12)) {
                $padding = 8;
                $cropX = max(0, $left - $padding);
                $cropY = max(0, $top - $padding);
                $cropW = min($w - $cropX, ($right - $left) + ($padding * 2));
                $cropH = min($h - $cropY, ($bottom - $top) + ($padding * 2));

                $cropped = imagecrop($srcImg, ['x' => $cropX, 'y' => $cropY, 'width' => $cropW, 'height' => $cropH]);
                if ($cropped) {
                    imagealphablending($cropped, false);
                    imagesavealpha($cropped, true);

                    match (strtolower($extension)) {
                        'png' => imagepng($cropped, $filePath, 9),
                        'webp' => imagewebp($cropped, $filePath, 88),
                    };

                    imagedestroy($cropped);
                }
            }

            imagedestroy($srcImg);
        } catch (\Throwable $e) {
            // Silencioso para no interrumpir el flujo
        }
    }

    /**
     * Prueba la conexión del servidor SMTP enviando un correo de diagnóstico.
     */
    public function testSmtp(Request $request)
    {
        $request->validate([
            'test_email' => ['nullable', 'email', 'max:191'],
        ]);

        $recipientEmail = $request->input('test_email') 
            ?: ($request->user()?->email ?: 'admin@dkript.com');

        $result = \App\Services\MailConfigService::testConnection($recipientEmail);

        AuditService::log('SETTINGS', 'SYSTEM', "Prueba de conexión de correo enviada a '{$recipientEmail}'", null, [
            'recipient' => $recipientEmail,
            'mailer' => $result['mailer'] ?? 'unknown',
            'status' => $result['success'] ? 'success' : 'failed',
        ]);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Prueba el envío de mensajes de WhatsApp enviando un mensaje de diagnóstico.
     */
    public function testWhatsApp(Request $request)
    {
        $request->validate([
            'test_phone' => ['required', 'string', 'max:50'],
        ]);

        $recipientPhone = $request->input('test_phone');

        $result = \App\Services\Messaging\MessagingService::testWhatsAppConnection($recipientPhone);

        AuditService::log('SETTINGS', 'SYSTEM', "Prueba de conexión WhatsApp enviada al número '{$recipientPhone}'");

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Limpia la memoria caché del sistema (optimize:clear) desde el panel de control.
     */
    public function clearCache(Request $request)
    {
        try {
            Artisan::call('optimize:clear');
            $output = Artisan::output();

            AuditService::log('SETTINGS', 'SYSTEM', 'Limpieza de caché general del sistema ejecutada desde el panel');

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Caché del sistema depurada exitosamente (vistas, rutas y configuraciones optimizadas).',
                    'output' => trim($output),
                ]);
            }

            return redirect()->back()->with('success', 'Caché del sistema depurada exitosamente.');
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al limpiar la caché: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error al limpiar la caché: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza la configuración textual (título, mensaje, insignia) de una página de error.
     */
    public function updateErrorPage(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'in:403,404,419,429,500,503'],
            'title' => ['nullable', 'string', 'max:191'],
            'message' => ['nullable', 'string', 'max:1000'],
            'badge' => ['nullable', 'string', 'max:100'],
        ]);

        $code = $validated['code'];
        $parameter = Parameter::first() ?: new Parameter();
        $errorPages = $parameter->error_pages ?? [];

        $pageConfig = $errorPages[$code] ?? [];
        if (array_key_exists('title', $validated)) {
            $pageConfig['title'] = $validated['title'];
        }
        if (array_key_exists('message', $validated)) {
            $pageConfig['message'] = $validated['message'];
        }
        if (array_key_exists('badge', $validated)) {
            $pageConfig['badge'] = $validated['badge'];
        }

        $errorPages[$code] = $pageConfig;
        $parameter->error_pages = $errorPages;
        $parameter->save();

        AuditService::log('SETTINGS', 'UPDATE', "Configuración de textos actualizada para la página de error {$code}");

        return response()->json([
            'success' => true,
            'message' => "Configuración de la página {$code} guardada exitosamente.",
            'page' => \App\Services\BrandingService::errorPage($code),
        ]);
    }

    /**
     * Carga y almacena un recurso multimedia (video o imagen) para una página de error.
     */
    public function uploadErrorMedia(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'in:403,404,419,429,500,503'],
            'type' => ['required', 'string', 'in:video,image'],
        ]);

        $code = $request->input('code');
        $type = $request->input('type');

        if ($type === 'video') {
            $request->validate([
                'file' => ['required', 'file', 'mimes:mp4,webm', 'max:51200'], // 50MB
            ], [
                'file.required' => 'Debe seleccionar un archivo de video.',
                'file.file' => 'El archivo proporcionado no es válido.',
                'file.mimes' => 'El video debe estar en formato MP4 o WebM.',
                'file.max' => 'El archivo de video no debe superar los 50 MB.',
            ]);
        } else {
            $request->validate([
                'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'], // 10MB
            ], [
                'file.required' => 'Debe seleccionar un archivo de imagen.',
                'file.file' => 'El archivo proporcionado no es válido.',
                'file.mimes' => 'La imagen debe estar en formato JPG, JPEG, PNG o WebP.',
                'file.max' => 'El archivo de imagen no debe superar los 10 MB.',
            ]);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        // Verificación estricta de seguridad: denegar formatos ejecutables o scripts
        $disallowedExtensions = ['php', 'phtml', 'phar', 'js', 'html', 'htm', 'svg', 'sh', 'exe', 'bat'];
        if (in_array($extension, $disallowedExtensions)) {
            return response()->json([
                'success' => false,
                'message' => 'El formato o extensión del archivo no está permitido por motivos de seguridad.',
            ], 422);
        }

        $destinationPath = public_path('uploads/errors');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $filename = 'error_' . $code . '_' . $type . '_' . time() . '_' . Str::random(8) . '.' . $extension;
        $targetFile = $destinationPath . DIRECTORY_SEPARATOR . $filename;

        if (!@copy($file->getRealPath(), $targetFile)) {
            $file->move($destinationPath, $filename);
        }

        $relativePath = 'uploads/errors/' . $filename;

        $parameter = Parameter::first() ?: new Parameter();
        $errorPages = $parameter->error_pages ?? [];
        $pageConfig = $errorPages[$code] ?? [];

        // Limpieza de archivo anterior administrado (solo si reside en uploads/errors/)
        $oldFile = $pageConfig[$type] ?? null;
        if (!empty($oldFile) && str_starts_with($oldFile, 'uploads/errors/')) {
            $fullOldPath = public_path($oldFile);
            if (file_exists($fullOldPath) && !is_dir($fullOldPath)) {
                @unlink($fullOldPath);
            }
        }

        $pageConfig[$type] = $relativePath;
        $errorPages[$code] = $pageConfig;
        $parameter->error_pages = $errorPages;
        $parameter->save();

        AuditService::log('SETTINGS', 'UPDATE', "Recurso {$type} cargado para la página de error {$code}: {$relativePath}");

        return response()->json([
            'success' => true,
            'message' => ucfirst($type) . " para error {$code} cargado exitosamente.",
            'file_path' => $relativePath,
            'asset_url' => asset($relativePath),
            'page' => \App\Services\BrandingService::errorPage($code),
        ]);
    }

    /**
     * Elimina el recurso multimedia (video o imagen) de una página de error.
     * Si era un archivo administrado en uploads/errors/, se remueve del disco.
     * Los assets empaquetados de la Demo se preservan intactos sin borrado físico.
     */
    public function deleteErrorMedia(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'in:403,404,419,429,500,503'],
            'type' => ['required', 'string', 'in:video,image'],
        ]);

        $code = $validated['code'];
        $type = $validated['type'];

        $parameter = Parameter::first() ?: new Parameter();
        $errorPages = $parameter->error_pages ?? [];
        $pageConfig = $errorPages[$code] ?? [];

        $currentFile = $pageConfig[$type] ?? null;

        // Limpieza física únicamente para uploads del usuario en uploads/errors/
        if (!empty($currentFile) && str_starts_with($currentFile, 'uploads/errors/')) {
            $fullPath = public_path($currentFile);
            if (file_exists($fullPath) && !is_dir($fullPath)) {
                @unlink($fullPath);
            }
        }

        // Marcar explícitamente como null o remover de la configuración
        $pageConfig[$type] = null;
        $errorPages[$code] = $pageConfig;
        $parameter->error_pages = $errorPages;
        $parameter->save();

        AuditService::log('SETTINGS', 'UPDATE', "Recurso {$type} eliminado de la página de error {$code}");

        return response()->json([
            'success' => true,
            'message' => ucfirst($type) . " de la página {$code} eliminado exitosamente.",
            'page' => \App\Services\BrandingService::errorPage($code),
        ]);
    }
}
