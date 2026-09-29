<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El municipio de la feria pasa de texto libre a una referencia a `cities`,
 * igual que en instituciones educativas y capacitaciones del Dashboard.
 *
 * Queda nullable en base de datos porque las ferias ya registradas no tienen
 * un municipio válido que asignar; el formulario sí lo exige.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_fairs', function (Blueprint $table) {
            $table->unsignedBigInteger('city_id')->nullable()->after('name');
            $table->foreign('city_id', 'sf_city_fk')->references('id')->on('cities')->nullOnDelete();
        });

        Schema::table('student_fairs', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }

    public function down(): void
    {
        Schema::table('student_fairs', function (Blueprint $table) {
            $table->string('location')->nullable()->after('name');
        });

        Schema::table('student_fairs', function (Blueprint $table) {
            $table->dropForeign('sf_city_fk');
            $table->dropColumn('city_id');
        });
    }
};
