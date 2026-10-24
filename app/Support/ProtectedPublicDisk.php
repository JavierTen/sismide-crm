<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemAdapter;

/**
 * Disco "public" cuyas URLs no muestran la ruta del archivo.
 *
 * Todo lo que genera enlaces (Storage::url, las vistas previas de Filament,
 * los Excel) pasa por url(), así que basta con cambiarlo aquí para que ningún
 * enlace del sistema exponga /storage/carpeta/archivo.
 */
class ProtectedPublicDisk extends FilesystemAdapter
{
    public function url($path)
    {
        return ProtectedFiles::url($path);
    }
}
