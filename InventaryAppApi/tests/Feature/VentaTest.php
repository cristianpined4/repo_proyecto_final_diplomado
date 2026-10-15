<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ventas;
use App\Models\Clientes;
use App\Models\Productos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class VentaTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    // Seed basic data
    $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
  }

  /** @test */
  public function it_can_register_a_venta_with_service()
  {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $cliente = Clientes::factory()->create();
    $producto = Productos::factory()->create(['stock' => 100]);

    // Seed sucursal stock
    DB::table('producto_sucursal')->insert([
        'producto_id' => $producto->id,
        'sucursal_id' => 1,
        'stock_actual' => 100,
        'stock_minimo' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user);

    $data = [
      'cliente_id' => $cliente->id,
      'productos' => [
        [
          'producto_id' => $producto->id,
          'cantidad' => 2,
          'precio_venta' => 50.00
        ]
      ]
    ];

    $response = $this->postJson('/api/ventas', $data);

    $response->assertStatus(200)
      ->assertJson([
        'status' => true
      ]);

    $this->assertDatabaseHas('ventas', [
      'cliente_id' => $cliente->id,
      'total_venta' => 100.00 // assuming no IVA for simplicity
    ]);

    $this->assertDatabaseHas('detalles_ventas', [
      'cantidad' => 2
    ]);

    // Check stock reduced
    $producto->refresh();
    $this->assertEquals(98, $producto->stock);
  }

  /** @test */
  public function it_respects_venta_policy_for_create()
  {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson('/api/ventas', []);

    // Depending on permissions, but for test we expect 403 if no permission
    $response->assertStatus(403); // or 201 if seeded with permissions
  }
}