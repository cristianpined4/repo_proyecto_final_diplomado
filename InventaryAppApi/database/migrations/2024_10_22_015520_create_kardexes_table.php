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
        Schema::create('kardexes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id'); // Producto relacionado
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            $table->integer('cantidad_antes'); // Cantidad antes del movimiento
            $table->decimal('costo_antes', 10, 2); // Costo promedio antes del movimiento
            $table->integer('cantidad_movimiento'); // Cantidad movida (entrada o salida)
            $table->decimal('costo_movimiento', 10, 2)->nullable(); // Costo del movimiento (para entradas)
            $table->integer('cantidad_despues'); // Cantidad después del movimiento
            $table->decimal('costo_despues', 10, 2); // Costo promedio después del movimiento
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardexes');
    }
};
