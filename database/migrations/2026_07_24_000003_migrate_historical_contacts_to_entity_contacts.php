<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $actors = DB::table('actors')
            ->whereNotNull('contact_name')
            ->where('contact_name', '!=', '')
            ->get();

        foreach ($actors as $actor) {
            $alreadyMigrated = DB::table('entity_contacts')
                ->where('entity_id', $actor->id)
                ->where('is_primary_contact', true)
                ->exists();

            if ($alreadyMigrated) {
                continue;
            }

            DB::table('entity_contacts')->insert([
                'entity_id'          => $actor->id,
                'name'               => $actor->contact_name,
                'role'               => $actor->contact_role ?? null,
                'email'              => $actor->contact_email ?? null,
                'phone'              => $actor->contact_phone ?? null,
                'is_primary_contact' => true,
                'status'             => 'active',
                'manager_id'         => $actor->manager_id,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Elimina solo los registros migrados automáticamente
        // (marcados como contacto principal sin área ni tipo de contacto)
        DB::table('entity_contacts')
            ->where('is_primary_contact', true)
            ->whereNull('area')
            ->whereNull('contact_type')
            ->delete();
    }
};
