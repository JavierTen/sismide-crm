<?php

namespace App\Filament\Resources\EntrepreneurUserResource\Pages;

use App\Filament\Resources\EntrepreneurUserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreateEntrepreneurUser extends CreateRecord
{
    protected static string $resource = EntrepreneurUserResource::class;

    protected $password;

    // IMPORTANTE: Sobrescribir este método para prevenir la creación de un registro vacío
    protected function handleRecordCreation(array $data): Model
    {
        // Obtener el emprendedor
        $entrepreneur = \App\Models\Entrepreneur::find($data['entrepreneur_id']);

        if (!$entrepreneur) {
            throw new \Exception('Emprendedor no encontrado');
        }

        // Generar contraseña
        $this->password = Str::random(8);

        // Actualizar contraseña del emprendedor
        $entrepreneur->update([
            'password' => Hash::make($this->password)
        ]);

        // Retornar el emprendedor actualizado (no crear uno nuevo)
        return $entrepreneur;
    }

    protected function afterCreate(): void
    {
        try {
            $entrepreneur = $this->record;

            if (empty($entrepreneur->email)) {
                Notification::make()
                    ->title('Advertencia')
                    ->body('El emprendedor no tiene correo electrónico.')
                    ->warning()
                    ->send();
                return;
            }

            // Enviar correo
            $entrepreneur->notify(new \App\Notifications\EntrepreneurCredentialsNotification(
                $entrepreneur->email,
                $this->password,
                $entrepreneur->full_name
            ));

            Notification::make()
                ->title('¡Usuario creado exitosamente!')
                ->body("Las credenciales han sido enviadas a {$entrepreneur->email}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al enviar credenciales')
                ->body('Error: ' . $e->getMessage())
                ->warning()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // AGREGAR ESTE MÉTODO PARA DESACTIVAR LA NOTIFICACIÓN POR DEFECTO
    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }
}
