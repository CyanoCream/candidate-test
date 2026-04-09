<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SupplierApiTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_can_create_and_fetch_suppliers(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/suppliers', [
            'name' => 'Supplier A'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('suppliers', ['name' => 'Supplier A']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/suppliers');
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Supplier A']);
    }
}
