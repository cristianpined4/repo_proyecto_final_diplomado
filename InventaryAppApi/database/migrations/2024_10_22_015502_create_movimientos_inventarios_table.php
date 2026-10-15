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
        Schema::create('movimientos_inventarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id'); // Producto relacionado
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            $table->enum('tipo', ['entrada', 'salida']); // Tipo de movimiento (entrada o salida)
            $table->integer('cantidad');
            $table->decimal('costo_unitario', 10, 2)->nullable(); // Costo unitario solo para entradas
            $table->text('razon')->nullable(); // Razon del movimiento
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventarios');
    }
};