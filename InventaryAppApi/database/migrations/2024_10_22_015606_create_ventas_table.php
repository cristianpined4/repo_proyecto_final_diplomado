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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id'); // Cliente relacionado
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->decimal('total_venta', 10, 2); // Total de la venta
            $table->unsignedBigInteger('user_id')->nullable(); // Usuario que realizó la venta
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->enum('status', ['pendiente', 'pagada', 'anulada', 'devolucion']); // Estado de la venta
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
