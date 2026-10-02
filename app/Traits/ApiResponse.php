<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Trait ApiResponse
 * 
 * Proporciona un contrato canónico unificado de respuestas JSON estructuradas (API Response Envelope)
 * compatible con parsers móviles nativos en iOS (Swift Codable) y Android (KotlinX Serialization).
 * 
 * Estructura estándar:
 * {
 *   "success": bool,
 *   "message": string|null,
 *   "data": mixed,
 *   "meta": array|null,
 *   "errors": mixed
 * }
 */
trait ApiResponse
{
    /**
     * Emite una respuesta de éxito con payload y metadatos opcionales.
     */
    public function successResponse(
        mixed $data = null,
        ?string $message = null,
        int $code = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'meta'    => empty($meta) ? null : $meta,
            'errors'  => null,
        ];

        return response()->json($response, $code);
    }

    /**
     * Emite una respuesta de error con descripción y lista detallada opcional.
     */
    public function errorResponse(
        string $message,
        int $code = 400,
        mixed $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'data'    => null,
            'meta'    => null,
            'errors'  => $errors,
        ];

        return response()->json($response, $code);
    }

    /**
     * Emite una respuesta paginada estandarizada para catálogos y listas infinitas.
     */
    public function paginatedResponse(
        LengthAwarePaginator $paginator,
        ?string $resourceClass = null,
        ?string $message = null
    ): JsonResponse {
        $items = $resourceClass 
            ? $resourceClass::collection($paginator->items())
            : $paginator->items();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $items,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'errors'  => null,
        ], 200);
    }

    /**
     * Transforma una máscara de 5 posiciones del RBAC en un objeto semántico para apps nativas.
     * Posición 1 = create, 2 = edit, 3 = delete, 4 = view, 5 = special.
     */
    public function rbacPermissionsPayload(array $positions): array
    {
        return [
            'create'  => (bool) ($positions[1] ?? false),
            'edit'    => (bool) ($positions[2] ?? false),
            'delete'  => (bool) ($positions[3] ?? false),
            'view'    => (bool) ($positions[4] ?? false),
            'special' => (bool) ($positions[5] ?? false),
        ];
    }
}
