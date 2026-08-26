<?php

namespace App\Http\Controllers;

use App\Models\InstitutionalDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class EvidencesDownloadController extends Controller
{
    public function download(Request $request)
    {
        $token = $request->get('token');

        if (!$token) {
            abort(400);
        }

        $ids = session("evidences_{$token}");

        if (empty($ids)) {
            abort(404);
        }

        session()->forget("evidences_{$token}");

        $documents = InstitutionalDocument::with(['entity.city'])
            ->whereIn('id', $ids)
            ->get();

        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ev_' . uniqid() . '.zip';
        $zip     = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo crear el archivo ZIP.');
        }

        $year = date('Y');

        foreach ($documents as $doc) {
            $city    = self::sanitize($doc->entity?->city?->name  ?? 'Sin_Municipio');
            $entity  = self::sanitize($doc->entity?->name         ?? 'Sin_Entidad');
            $subject = self::sanitize($doc->subject               ?? 'documento');
            $base    = "Evidencias_Articulacion_{$year}/{$city}/{$entity}";

            // Archivo principal
            if ($doc->main_file_path) {
                $fullPath = Storage::disk('public')->path($doc->main_file_path);
                if (file_exists($fullPath)) {
                    $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
                    $zip->addFile($fullPath, "{$base}/{$subject}_{$doc->id}.{$ext}");
                }
            }

            // Anexos
            if (!empty($doc->attachments)) {
                foreach ($doc->attachments as $i => $attachment) {
                    $fullPath = Storage::disk('public')->path($attachment);
                    if (file_exists($fullPath)) {
                        $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
                        $n   = $i + 1;
                        $zip->addFile($fullPath, "{$base}/{$subject}_{$doc->id}_anexo{$n}.{$ext}");
                    }
                }
            }
        }

        $zip->close();

        // ZIP vacío pesa 22 bytes
        if (!file_exists($zipPath) || filesize($zipPath) <= 22) {
            @unlink($zipPath);
            return response('No hay archivos digitales en los registros seleccionados.', 422);
        }

        $filename = 'Evidencias_Articulacion_' . $year . '.zip';

        return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
    }

    public function downloadExcel(Request $request)
    {
        $token = $request->get('token');

        if (!$token) abort(400);

        $data = session("excel_{$token}");
        session()->forget("excel_{$token}");

        if (empty($data)) abort(404);

        $rows     = $data['rows'];
        $headings = $data['headings'];
        $filename = $data['filename'];

        $export = new class($rows, $headings) implements FromArray, WithHeadings {
            public function __construct(private array $rows, private array $headings) {}
            public function array(): array    { return $this->rows; }
            public function headings(): array { return $this->headings; }
        };

        return Excel::download($export, $filename);
    }

    private static function sanitize(?string $value): string
    {
        return Str::slug($value ?? '', '_') ?: 'sin_nombre';
    }
}
