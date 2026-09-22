<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fair_participations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_fair_id')->constrained('student_fairs')->cascadeOnDelete();
            $table->foreignId('educational_institution_id')->constrained('educational_institutions')->cascadeOnDelete();
            $table->date('participation_date');

            // ── Articulaciones ─────────────────────────────────────────────────
            $table->boolean('generated_articulations')->default(false);
            $table->json('articulation_actor_types')->nullable();
            $table->string('articulations_count')->nullable(); // 1, 2-5, 6-10, >10

            // ── Encadenamiento productivo ──────────────────────────────────────
            $table->boolean('identified_chain_opportunity')->default(false);
            $table->string('chain_link')->nullable(); // proveedor, cliente, distribuidor, transformador
            $table->json('chain_actor_types')->nullable();

            // ── Ventas ────────────────────────────────────────────────────────
            $table->boolean('had_sales')->default(false);
            $table->string('sales_range')->nullable(); // sin_ventas, lt50k, 50k_200k, 200k_500k, gt500k
            $table->decimal('exact_sales_amount', 14, 2)->nullable();
            $table->string('sales_balance')->nullable(); // positivo, neutro, negativo

            // ── Experiencia ───────────────────────────────────────────────────
            $table->string('organization_rating')->nullable(); // excelente, buena, regular, deficiente
            $table->string('visitor_flow')->nullable();        // alto, medio, bajo
            $table->boolean('generated_contacts')->default(false);

            // ── Evidencias ────────────────────────────────────────────────────
            $table->text('description')->nullable();
            $table->string('attendance_list_path')->nullable();
            $table->json('photos')->nullable();

            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['student_fair_id', 'educational_institution_id'], 'unique_fair_institution');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fair_participations');
    }
};
