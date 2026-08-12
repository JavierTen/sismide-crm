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
        Schema::table('training_supports', function (Blueprint $table) {
            // Fotos múltiples (JSON) — reemplaza los 3 slots fijos en nuevos registros
            $table->json('photos')->nullable()->after('additional_photo_3_path');

            // Evidencias virtuales
            $table->string('connection_evidence_path')->nullable()->after('photos');
            $table->string('visual_evidence_path')->nullable()->after('connection_evidence_path');
            $table->string('recording_file_path')->nullable()->after('visual_evidence_path');

            // Materiales complementarios
            $table->string('material_path')->nullable()->after('recording_file_path');
            $table->string('additional_documents_path')->nullable()->after('material_path');
        });
    }

    public function down(): void
    {
        Schema::table('training_supports', function (Blueprint $table) {
            $table->dropColumn([
                'photos',
                'connection_evidence_path',
                'visual_evidence_path',
                'recording_file_path',
                'material_path',
                'additional_documents_path',
            ]);
        });
    }
};
