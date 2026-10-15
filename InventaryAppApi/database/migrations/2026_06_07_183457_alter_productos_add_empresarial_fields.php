<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->json('imagenes')->nullable();
            $table->foreignId('marca_id')->nullable()->constrained('marcas')->nullOnDelete();
            $table->foreignId('modelo_id')->nullable()->constrained('modelos')->nullOnDelete();
            $table->foreignId('subcategoria_id')->nullable()->constrained('subcategorias')->nullOnDelete();
            $table->foreignId('unidad_id')->nullable()->constrained('unidads')->nullOnDelete();
            $table->string('codigo_interno')->nullable();
            $table->string('codigo_barras')->nullable()->unique();
            $table->boolean('aplica_iva')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['marca_id']);
            $table->dropForeign(['modelo_id']);
            $table->dropForeign(['subcategoria_id']);
            $table->dropForeign(['unidad_id']);
            $table->dropColumn([
                'imagenes', 'marca_id', 'modelo_id', 'subcategoria_id', 'unidad_id', 
                'codigo_interno', 'codigo_barras', 'aplica_iva'
            ]);
        });
    }
};
