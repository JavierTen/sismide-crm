<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CustomAvatarProvider implements AvatarProvider
{
    public function get(Model | Authenticatable $record): string
    {
        $name = 'Usuario';

        // Intentar obtener el nombre
        if (method_exists($record, 'getFilamentName')) {
            try {
                $name = $record->getFilamentName();
            } catch (\Exception $e) {
                Log::error('Error getting filament name', [
                    'error' => $e->getMessage(),
                    'user_id' => $record->id ?? null
                ]);
            }
        }

        // Fallbacks adicionales
        if (empty($name) || $name === 'Usuario') {
            $name = $record->full_name
                ?? $record->name
                ?? $record->email
                ?? 'Usuario #' . ($record->id ?? '0');
        }

        // Asegurar que sea string válido
        $name = trim((string) $name);
        if (empty($name)) {
            $name = 'Usuario';
        }

        // Generar URL del avatar con UI Avatars
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=7F9CF5&background=EBF4FF';
    }
}
