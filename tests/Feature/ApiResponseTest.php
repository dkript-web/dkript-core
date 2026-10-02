<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Traits\ApiResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    private object $apiDummy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiDummy = new class {
            use ApiResponse;
        };
    }

    public function test_success_response_returns_canonical_envelope(): void
    {
        $response = $this->apiDummy->successResponse(
            data: ['user_id' => 42, 'username' => 'drypt'],
            message: 'Operación completada',
            code: 200,
            meta: ['server_time' => '2026-09-25T01:30:00Z']
        );

        $this->assertEquals(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);

        $this->assertTrue($payload['success']);
        $this->assertEquals('Operación completada', $payload['message']);
        $this->assertEquals(42, $payload['data']['user_id']);
        $this->assertEquals('drypt', $payload['data']['username']);
        $this->assertEquals('2026-09-25T01:30:00Z', $payload['meta']['server_time']);
        $this->assertNull($payload['errors']);
    }

    public function test_error_response_returns_canonical_envelope(): void
    {
        $response = $this->apiDummy->errorResponse(
            message: 'Acceso denegado al recurso',
            code: 403,
            errors: ['reason' => 'Privilegios insuficientes en posición 2 (Editar)']
        );

        $this->assertEquals(403, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);

        $this->assertFalse($payload['success']);
        $this->assertEquals('Acceso denegado al recurso', $payload['message']);
        $this->assertNull($payload['data']);
        $this->assertNull($payload['meta']);
        $this->assertEquals('Privilegios insuficientes en posición 2 (Editar)', $payload['errors']['reason']);
    }

    public function test_paginated_response_returns_canonical_envelope(): void
    {
        $items = collect([
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ]);

        $paginator = new LengthAwarePaginator(
            items: $items,
            total: 10,
            perPage: 2,
            currentPage: 1
        );

        $response = $this->apiDummy->paginatedResponse(
            paginator: $paginator,
            message: 'Listado paginado'
        );

        $this->assertEquals(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);

        $this->assertTrue($payload['success']);
        $this->assertEquals('Listado paginado', $payload['message']);
        $this->assertCount(2, $payload['data']);
        $this->assertEquals(1, $payload['meta']['current_page']);
        $this->assertEquals(5, $payload['meta']['last_page']);
        $this->assertEquals(2, $payload['meta']['per_page']);
        $this->assertEquals(10, $payload['meta']['total']);
    }

    public function test_rbac_permissions_payload_maps_5_positions_correctly(): void
    {
        // Posiciones simuladas: 1=Crear(true), 2=Editar(false), 3=Eliminar(false), 4=Ver(true), 5=Especial(true)
        $positions = [
            1 => true,
            2 => false,
            3 => false,
            4 => true,
            5 => true,
        ];

        $payload = $this->apiDummy->rbacPermissionsPayload($positions);

        $this->assertTrue($payload['create']);
        $this->assertFalse($payload['edit']);
        $this->assertFalse($payload['delete']);
        $this->assertTrue($payload['view']);
        $this->assertTrue($payload['special']);
    }
}
