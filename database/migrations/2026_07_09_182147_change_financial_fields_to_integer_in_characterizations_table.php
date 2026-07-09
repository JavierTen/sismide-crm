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
        Schema::table('characterizations', function (Blueprint $table) {
            $table->unsignedBigInteger('monthly_costs')->nullable()->change();
            $table->unsignedBigInteger('monthly_expenses')->nullable()->change();
            $table->unsignedBigInteger('monthly_profit')->nullable()->change();
            $table->unsignedBigInteger('credit_amount')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('characterizations', function (Blueprint $table) {
            $table->decimal('monthly_costs', 15, 2)->nullable()->change();
            $table->decimal('monthly_expenses', 15, 2)->nullable()->change();
            $table->decimal('monthly_profit', 15, 2)->nullable()->change();
            $table->decimal('credit_amount', 15, 2)->nullable()->change();
        });
    }
};
