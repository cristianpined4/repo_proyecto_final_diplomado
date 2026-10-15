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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('correo')->unique()->nullable(); // Correo del cliente (opcional)
            $table->string('dui')->unique()->nullable(); // DUI del cliente (opcional)
            $table->string('telefono')->nullable(); // Teléfono del cliente (opcional)
            $table->text('direccion')->nullable(); // Dirección del cliente (opcional)
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
