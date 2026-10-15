<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Nombre del producto
            $table->string('sku')->unique(); // Código único del producto
            $table->integer('stock')->default(0); // Cantidad en inventario
            $table->decimal('costo_promedio', 10, 2)->default(0);
            $table->decimal('precio_venta', 10, 2)->default(0); // Precio de venta
            $table->unsignedBigInteger('categoria_id')->nullable(); // Categoría del producto
            $table->boolean('status')->default(1); // Estado del producto
            $table->enum('tipo', ['perecedero', 'no perecedero'])->default('no perecedero'); // Tipo de producto
            $table->date('fecha_vencimiento')->nullable(); // Fecha de vencimiento del producto
            $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('cascade'); // Categoría del producto
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
