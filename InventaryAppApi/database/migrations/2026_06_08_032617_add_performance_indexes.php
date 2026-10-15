<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índices de rendimiento para columnas frecuentemente buscadas/filtradas.
     */
    public function up(): void
    {
        // Productos: búsqueda por SKU, nombre, status
        Schema::table('productos', function (Blueprint $table) {
            $table->index('sku', 'idx_productos_sku');
            $table->index('nombre', 'idx_productos_nombre');
            $table->index('status', 'idx_productos_status');
            $table->index('categoria_id', 'idx_productos_categoria');
        });

        // Clientes: búsqueda por DUI, NIT, NRC
        Schema::table('clientes', function (Blueprint $table) {
            $table->index('dui', 'idx_clientes_dui');
            $table->index('nombre', 'idx_clientes_nombre');
        });

        // Ventas: filtrado por status, fecha
        Schema::table('ventas', function (Blueprint $table) {
            $table->index('status', 'idx_ventas_status');
            $table->index('created_at', 'idx_ventas_created_at');
            $table->index(['status', 'created_at'], 'idx_ventas_status_date');
        });

        // Compras: filtrado por status, fecha
        Schema::table('compras', function (Blueprint $table) {
            $table->index('status', 'idx_compras_status');
            $table->index('created_at', 'idx_compras_created_at');
        });

        // Kardex: búsqueda por producto y fecha
        Schema::table('kardexes', function (Blueprint $table) {
            $table->index('producto_id', 'idx_kardex_producto');
            $table->index('created_at', 'idx_kardex_created_at');
        });

        // Movimientos: búsqueda por producto
        Schema::table('movimientos_inventarios', function (Blueprint $table) {
            $table->index('producto_id', 'idx_movimientos_producto');
        });

        // Audit logs: consulta por usuario y acción
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('user_id', 'idx_audit_user');
            $table->index('created_at', 'idx_audit_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex('idx_productos_sku');
            $table->dropIndex('idx_productos_nombre');
            $table->dropIndex('idx_productos_status');
            $table->dropIndex('idx_productos_categoria');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex('idx_clientes_dui');
            $table->dropIndex('idx_clientes_nombre');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex('idx_ventas_status');
            $table->dropIndex('idx_ventas_created_at');
            $table->dropIndex('idx_ventas_status_date');
        });

        Schema::table('compras', function (Blueprint $table) {
            $table->dropIndex('idx_compras_status');
            $table->dropIndex('idx_compras_created_at');
        });

        Schema::table('kardexes', function (Blueprint $table) {
            $table->dropIndex('idx_kardex_producto');
            $table->dropIndex('idx_kardex_created_at');
        });

        Schema::table('movimientos_inventarios', function (Blueprint $table) {
            $table->dropIndex('idx_movimientos_producto');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_user');
            $table->dropIndex('idx_audit_created_at');
        });
    }
};
