<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EntrepreneurCredentialsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $email;
    public $password;
    public $entrepreneurName;

    public function __construct(string $email, string $password, string $entrepreneurName)
    {
        $this->email = $email;
        $this->password = $password;
        $this->entrepreneurName = $entrepreneurName;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = route('filament.emprendedor.auth.login');

        return (new MailMessage)
            ->subject('Credenciales de Acceso - Ruta D')
            ->view('emails.entrepreneur-credentials', [
                'entrepreneurName' => $this->entrepreneurName,
                'email' => $this->email,
                'password' => $this->password,
                'loginUrl' => $loginUrl,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'email' => $this->email,
            'entrepreneur_name' => $this->entrepreneurName,
        ];
    }
}
