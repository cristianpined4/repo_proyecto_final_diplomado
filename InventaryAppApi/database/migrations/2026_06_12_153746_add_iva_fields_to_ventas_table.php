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
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->after('total_venta');
            $table->decimal('monto_iva', 10, 2)->default(0)->after('subtotal');
            $table->decimal('porcentaje_iva_aplicado', 5, 2)->default(0)->after('monto_iva');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'monto_iva', 'porcentaje_iva_aplicado']);
        });
    }
};
