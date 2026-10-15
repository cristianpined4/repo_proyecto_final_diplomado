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
        // 1. Alterar tabla users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable()->after('password')->constrained('sucursals')->nullOnDelete();
            $table->string('email')->nullable()->after('username');
            $table->string('phone')->nullable()->after('email');
            $table->boolean('status')->default(true)->after('phone');
        });

        // 2. Alterar tabla sucursals
        Schema::table('sucursals', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('correo');
            $table->string('ciudad', 100)->nullable()->after('logo');
            $table->string('estado', 100)->nullable()->after('ciudad');
            $table->string('pais', 100)->nullable()->after('estado');
        });

        // 3. Alterar tabla detalles_compras
        Schema::table('detalles_compras', function (Blueprint $table) {
            $table->integer('cantidad_restante')->default(0)->after('cantidad');
        });

        // Inicializar cantidad_restante = cantidad para registros existentes
        DB::table('detalles_compras')->update([
            'cantidad_restante' => DB::raw('cantidad')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['sucursal_id']);
            $table->dropColumn(['sucursal_id', 'email', 'phone', 'status']);
        });

        Schema::table('sucursals', function (Blueprint $table) {
            $table->dropColumn(['logo', 'ciudad', 'estado', 'pais']);
        });

        Schema::table('detalles_compras', function (Blueprint $table) {
            $table->dropColumn(['cantidad_restante']);
        });
    }
};
