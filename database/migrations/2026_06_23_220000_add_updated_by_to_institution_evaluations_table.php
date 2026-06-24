<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_evaluations', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('manager_id')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('institution_evaluations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
        });
    }
};
