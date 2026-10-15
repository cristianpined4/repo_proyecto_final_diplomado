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
            $table->index(['status', 'nombre'], 'idx_productos_status_nombre');
        });

        Schema::table('proveedors', function (Blueprint $table) {
            $table->index(['estado', 'nombre'], 'idx_proveedors_estado_nombre');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->index(['status', 'nombre'], 'idx_clientes_status_nombre');
        });

        Schema::table('producto_sucursal', function (Blueprint $table) {
            $table->index(['producto_id', 'sucursal_id'], 'idx_prod_suc_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('idx_productos_status_nombre');
        });

        Schema::table('proveedors', function (Blueprint $table) {
            $table->dropIndex('idx_proveedors_estado_nombre');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex('idx_clientes_status_nombre');
        });

        Schema::table('producto_sucursal', function (Blueprint $table) {
            $table->dropIndex('idx_prod_suc_ids');
        });
    }
};
