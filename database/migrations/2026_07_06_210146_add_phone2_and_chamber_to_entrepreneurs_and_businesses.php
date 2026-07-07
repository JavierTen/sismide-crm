<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entrepreneurs', function (Blueprint $table) {
            $table->string('phone_2', 50)->nullable()->after('phone');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('has_chamber_of_commerce')->default(false)->after('creation_date');
        });
    }

    public function down(): void
    {
        Schema::table('entrepreneurs', function (Blueprint $table) {
            $table->dropColumn('phone_2');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('has_chamber_of_commerce');
        });
    }
};
