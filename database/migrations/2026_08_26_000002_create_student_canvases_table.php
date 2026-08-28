<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_canvases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();

            // 9 bloques del Canvas de Modelo de Negocio
            $table->text('customer_segments')->nullable();
            $table->text('value_proposition')->nullable();
            $table->text('channels')->nullable();
            $table->text('customer_relationships')->nullable();
            $table->text('revenue_streams')->nullable();
            $table->text('key_resources')->nullable();
            $table->text('key_activities')->nullable();
            $table->text('key_partnerships')->nullable();
            $table->text('cost_structure')->nullable();

            // Materiales (fire pitch requerido para estudiantes)
            $table->string('fire_pitch_video_url')->nullable();
            $table->string('canvas_file_path')->nullable();

            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_canvases');
    }
};
