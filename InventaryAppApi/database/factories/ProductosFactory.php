<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Productos>
 */
class ProductosFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'nombre' => $this->faker->word(),
      'sku' => $this->faker->unique()->bothify('SKU-####'),
      'codigo_interno' => $this->faker->unique()->numerify('INT-#####'),
      // 'descripcion' => $this->faker->sentence(), // Columna eliminada en migración
      'stock' => $this->faker->numberBetween(10, 100),
      'precio_venta' => $this->faker->randomFloat(2, 5, 100),
      'costo_promedio' => $this->faker->randomFloat(2, 3, 80),
      'aplica_iva' => true,
      'categoria_id' => 1, // assume seeded
    ];
  }
}