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
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->decimal('total_compra', 10, 2); // Total de la compra
            $table->unsignedBigInteger('user_id')->nullable(); // Usuario que realizó la compra
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->enum('status', ['pendiente', 'pagado', 'cancelada']); // Estado de la compra
            $table->string('nombre_proveedor');
            $table->string('numero_factura')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
