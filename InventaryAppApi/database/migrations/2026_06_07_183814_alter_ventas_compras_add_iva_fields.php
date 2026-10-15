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
        Schema::table('compras', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->after('user_id');
            $table->decimal('monto_iva', 10, 2)->default(0)->after('subtotal');
            $table->decimal('porcentaje_iva_aplicado', 5, 2)->default(0)->after('monto_iva');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('ventas', 'subtotal')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropColumn(['subtotal', 'monto_iva', 'porcentaje_iva_aplicado']);
            });
        }

        if (Schema::hasColumn('compras', 'subtotal')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->dropColumn(['subtotal', 'monto_iva', 'porcentaje_iva_aplicado']);
            });
        }
    }
};