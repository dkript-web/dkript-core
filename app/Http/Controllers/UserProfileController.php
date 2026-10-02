<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserProfileController extends Controller
{
    /**
     * Actualiza los datos de identidad y perfil del usuario autenticado.
     * Requiere verificación obligatoria de la contraseña actual.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'current_password' => ['required', 'string'],
            'new_password' => ['nullable', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ], [
            'name.required' => 'El nombre de usuario es obligatorio.',
            'first_name.required' => 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico es inválido.',
            'email.unique' => 'Este correo electrónico ya está registrado por otro usuario.',
            'current_password.required' => 'Debes ingresar tu contraseña actual para autorizar los cambios.',
            'new_password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.mixed' => 'La nueva contraseña debe incluir al menos una letra mayúscula y una minúscula.',
            'password.letters' => 'La nueva contraseña debe contener letras.',
            'password.numbers' => 'La nueva contraseña debe incluir al menos un número.',
            'password.symbols' => 'La nueva contraseña debe incluir al menos un carácter especial.',
        ]);

        // Verificación estricta de la contraseña actual
        if (!Hash::check($validated['current_password'], $user->password)) {
            Log::warning('USER_PROFILE_UPDATE_PASSWORD_MISMATCH', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'La contraseña actual ingresada es incorrecta. No se aplicó ningún cambio.',
                'errors' => [
                    'current_password' => ['La contraseña actual es incorrecta.'],
                ],
            ], 422);
        }

        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'first_name' => $user->profile?->first_name,
            'last_name' => $user->profile?->last_name,
            'phone' => $user->profile?->phone,
        ];

        // Actualizar datos del usuario
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        $passwordChanged = false;
        if (!empty($validated['new_password'])) {
            $user->password = Hash::make($validated['new_password']);
            $passwordChanged = true;
        }

        $user->save();

        // Actualizar o crear perfil de usuario
        if ($user->profile) {
            $user->profile->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'] ?? null,
            ]);
        } else {
            Profile::create([
                'user_id' => $user->id,
                'role_id' => $user->role_id ?? 1,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'] ?? null,
            ]);
        }

        $newValues = [
            'name' => $user->name,
            'email' => $user->email,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? null,
            'password_changed' => $passwordChanged,
        ];

        Log::info('USER_PROFILE_UPDATED', [
            'actor_id' => $user->id,
            'email' => $user->email,
            'password_changed' => $passwordChanged,
            'ip' => $request->ip(),
        ]);

        AuditService::log(
            'UPDATE',
            'USERS',
            "Actualización de datos de perfil personal por el usuario '{$user->name}'" . ($passwordChanged ? ' (incluye cambio de contraseña)' : ''),
            $oldValues,
            $newValues
        );

        $freshProfile = $user->profile()->first();

        return response()->json([
            'success' => true,
            'message' => 'Tus datos de perfil han sido actualizados exitosamente.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'full_name' => $freshProfile?->full_name ?: $user->name,
                'first_name' => $freshProfile?->first_name ?: '',
                'last_name' => $freshProfile?->last_name ?: '',
                'phone' => $freshProfile?->phone ?: '',
                'avatar_url' => $freshProfile?->avatar_url,
            ],
        ]);
    }

    /**
     * Sube y actualiza de forma autónoma e inmediata la foto de perfil del usuario.
     * No requiere contraseña actual.
     * Reemplaza la foto anterior sin guardar historial y la almacena en public/uploads/users/user_{id}_{slug}/avatar/
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
        ], [
            'avatar.required' => 'Debes seleccionar una imagen para tu foto de perfil.',
            'avatar.image' => 'El archivo seleccionado debe ser una imagen válida.',
            'avatar.mimes' => 'Formatos de imagen permitidos: JPEG, PNG, JPG, WEBP o GIF.',
            'avatar.max' => 'La imagen no debe superar los 5 MB de tamaño.',
        ]);

        $user = $request->user();
        $profile = $user->profile ?? Profile::create([
            'user_id' => $user->id,
            'role_id' => $user->role_id ?? 1,
            'first_name' => $user->name,
            'last_name' => '',
        ]);

        // Estructura de directorio por usuario: public/uploads/users/user_{id}_{slug}/avatar
        $userSlug = Str::slug($user->name) ?: 'user';
        $relativeUserFolder = "uploads/users/user_{$user->id}_{$userSlug}";
        $relativeAvatarFolder = "{$relativeUserFolder}/avatar";
        $fullAvatarPath = public_path($relativeAvatarFolder);

        // Crear directorio seguro si no existe
        if (!File::isDirectory($fullAvatarPath)) {
            File::makeDirectory($fullAvatarPath, 0755, true, true);
            // Colocar index.html defensivo para evitar exploración de directorios
            if (!File::exists(public_path("{$relativeUserFolder}/index.html"))) {
                File::put(public_path("{$relativeUserFolder}/index.html"), '');
            }
        }

        // Política de reemplazo: eliminar avatar anterior si existe
        if ($profile->avatar && File::exists(public_path($profile->avatar))) {
            File::delete(public_path($profile->avatar));
        }

        // Limpiar cualquier archivo previo en la carpeta de avatar del usuario (garantía de no almacenar historial)
        if (File::isDirectory($fullAvatarPath)) {
            File::cleanDirectory($fullAvatarPath);
        }

        // Guardar nuevo archivo con marca de tiempo para evitar problemas de caché en navegador
        $file = $request->file('avatar');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'png');
        $filename = 'avatar_' . time() . '.' . $extension;
        $targetFile = $fullAvatarPath . DIRECTORY_SEPARATOR . $filename;

        if (!@copy($file->getRealPath(), $targetFile)) {
            $file->move($fullAvatarPath, $filename);
        }

        $savedRelativePath = "{$relativeAvatarFolder}/{$filename}";
        $profile->update(['avatar' => $savedRelativePath]);

        // Registrar en bitácora de auditoría
        AuditService::log('UPDATE', 'USERS', "Actualización de foto de perfil del usuario '{$user->name}' (ID: {$user->id})", [
            'user_id' => $user->id,
            'avatar_path' => $savedRelativePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Foto de perfil actualizada exitosamente.',
            'data' => [
                'avatar_url' => asset($savedRelativePath),
                'avatar_path' => $savedRelativePath,
            ],
        ]);
    }
}
