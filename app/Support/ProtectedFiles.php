<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Enlaces a los archivos subidos que no revelan dónde están guardados.
 *
 * En lugar de /storage/carpeta/archivo.pdf el enlace lleva la ruta cifrada
 * (/archivos/eyJpdiI6...). Solo el servidor puede descifrarla, así que el
 * enlace no deja ver ni adivinar la carpeta ni el nombre de otros archivos.
 */
class ProtectedFiles
{
    public static function url(string $path): string
    {
        $token = rtrim(strtr(base64_encode(Crypt::encryptString(ltrim($path, '/'))), '+/', '-_'), '=');

        return route('files.show', ['token' => $token]);
    }

    /** Ruta del archivo en el disco, o null si el enlace no es válido. */
    public static function path(string $token): ?string
    {
        try {
            $path = Crypt::decryptString((string) base64_decode(strtr($token, '-_', '+/'), true));
        } catch (Throwable) {
            return null;
        }

        return $path !== '' && ! str_contains($path, '..') ? $path : null;
    }
}
