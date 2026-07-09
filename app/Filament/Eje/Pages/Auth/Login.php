<?php

namespace App\Filament\Eje\Pages\Auth;

use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        if (session('status')) {
            Notification::make()
                ->danger()
                ->title('Acceso denegado')
                ->body(session('status'))
                ->persistent()
                ->send();
        }
    }
}
