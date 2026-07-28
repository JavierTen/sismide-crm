<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ciiu_codes')->insertOrIgnore([
            'code'        => '3212',
            'descripcion' => 'Fabricación de bisutería y artículos conexos',
            'status'      => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('ciiu_codes')->where('code', '3212')->delete();
    }
};
