<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutional_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entity_id')
                ->constrained('actors')
                ->cascadeOnDelete();

            $table->enum('document_type', [
                'carta',
                'oficio',
                'presentacion',
                'solicitud_apoyo',
                'certificado_pdm',
                'carta_intencion',
                'lista_asistencia',
                'acta',
                'informe',
                'base_datos',
                'otro',
            ]);

            $table->string('subject');
            $table->date('document_date');

            $table->enum('status', [
                'pending',
                'in_management',
                'delivered',
                'pending_signature',
                'signed',
                'finalized',
            ])->default('pending');

            $table->date('delivery_date')->nullable();

            $table->boolean('requires_follow_up')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->text('observation')->nullable();

            $table->enum('has_physical_support', ['yes', 'no', 'not_applicable'])->default('no');
            $table->text('physical_location')->nullable();

            $table->string('main_file_path')->nullable();
            $table->json('attachments')->nullable();

            $table->foreignId('manager_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('updated_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('entity_id');
            $table->index('status');
            $table->index('document_date');
            $table->index('requires_follow_up');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutional_documents');
    }
};