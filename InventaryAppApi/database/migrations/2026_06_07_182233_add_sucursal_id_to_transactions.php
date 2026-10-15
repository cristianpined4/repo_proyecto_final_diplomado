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
        if (!Schema::hasColumn('ventas', 'sucursal_id')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursals')->cascadeOnDelete();
            });
        }
        if (!Schema::hasColumn('compras', 'sucursal_id')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursals')->cascadeOnDelete();
            });
        }
        if (!Schema::hasColumn('kardexes', 'sucursal_id')) {
            Schema::table('kardexes', function (Blueprint $table) {
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursals')->cascadeOnDelete();
            });
        }
        if (!Schema::hasColumn('movimientos_inventarios', 'sucursal_id')) {
            Schema::table('movimientos_inventarios', function (Blueprint $table) {
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursals')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('ventas', 'sucursal_id')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropForeign(['sucursal_id']);
                $table->dropColumn('sucursal_id');
            });
        }
        if (Schema::hasColumn('compras', 'sucursal_id')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->dropForeign(['sucursal_id']);
                $table->dropColumn('sucursal_id');
            });
        }
        if (Schema::hasColumn('kardexes', 'sucursal_id')) {
            Schema::table('kardexes', function (Blueprint $table) {
                $table->dropForeign(['sucursal_id']);
                $table->dropColumn('sucursal_id');
            });
        }
        if (Schema::hasColumn('movimientos_inventarios', 'sucursal_id')) {
            Schema::table('movimientos_inventarios', function (Blueprint $table) {
                $table->dropForeign(['sucursal_id']);
                $table->dropColumn('sucursal_id');
            });
        }
    }
};
