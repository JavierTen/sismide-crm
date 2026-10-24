<?php

namespace App\Http\Controllers;

use App\Support\ProtectedFiles;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entrega los archivos subidos (disco público) solo a quien tiene sesión
 * iniciada, en cualquier panel: usuarios del sistema o emprendedores.
 *
 * Los enlaces llevan la ruta cifrada (ver ProtectedFiles). Sin sesión se envía
 * al inicio de sesión y, al entrar, se vuelve al archivo.
 */
class ProtectedFileController extends Controller
{
    private const GUARDS = ['web', 'entrepreneur'];

    /** /archivos/{token}: el enlace que genera el sistema. */
    public function show(string $token): Response
    {
        return $this->serve(ProtectedFiles::path($token));
    }

    /**
     * /storage/{ruta}: enlaces que ya estaban en Excel descargados antes del
     * cambio. Siguen funcionando, también solo con sesión iniciada.
     */
    public function legacy(string $path): Response
    {
        return $this->serve(str_contains($path, '..') ? null : $path);
    }

    private function serve(?string $path): Response
    {
        if (! collect(self::GUARDS)->contains(fn (string $guard) => auth()->guard($guard)->check())) {
            return redirect()->guest('/dashboard/login');
        }

        $disk = Storage::disk('public');

        abort_if($path === null || ! $disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
