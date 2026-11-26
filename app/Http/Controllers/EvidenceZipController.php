<?php

namespace App\Http\Controllers;

use App\Support\EvidenceZips;
use App\Support\ExportLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * "Descargar Evidencias" (Caracterización, Planes de negocio y Canvas).
 *
 * El ZIP se arma después de responder, así el usuario puede cambiar de página
 * o cerrar el navegador sin cancelarlo, y la sesión no queda abierta minutos.
 * El aviso de cualquier página del panel pregunta por "status" y retoma la
 * descarga donde iba (ver evidence-zip-toast.blade.php).
 */
class EvidenceZipController extends Controller
{
    /** Empieza a armar el ZIP con los registros que escogió la pantalla. */
    public function prepare(Request $request): JsonResponse
    {
        abort_unless(EvidenceZips::canDownload(), 403);

        $token   = (string) $request->input('token');
        $userId  = auth()->id();
        $payload = Cache::pull(EvidenceZips::cacheKey('request', $token));
        abort_if(empty($payload['ids']) || ($payload['user'] ?? null) !== $userId || ! EvidenceZips::exists($payload['type'] ?? null), 404);

        EvidenceZips::saveJob($token, ['user' => $userId, 'type' => $payload['type'], 'label' => EvidenceZips::title($payload['type']), 'status' => 'working', 'percent' => 0]);
        EvidenceZips::trackJob($userId, $token);

        dispatch(function () use ($token, $payload) {
            ignore_user_abort(true);
            set_time_limit(600);
            static::removeOldZips();

            // El avance se guarda solo cuando cambia, para no escribir en caché a cada archivo.
            $last = -1;

            try {
                $zip = EvidenceZips::zip($payload['type'], $payload['ids'], function (int $percent) use ($token, &$last) {
                    if ($percent !== $last) {
                        EvidenceZips::saveJob($token, ['percent' => $last = $percent]);
                    }
                });

                EvidenceZips::saveJob($token, $zip
                    ? ['status' => 'ready', 'percent' => 100, 'path' => $zip['path'], 'name' => $zip['name'], 'files' => $zip['files'], 'records' => $zip['records']]
                    : ['status' => 'empty']);
            } catch (\Throwable $e) {
                report($e);
                EvidenceZips::saveJob($token, ['status' => 'failed', 'message' => 'No se pudo armar el ZIP. Intenta de nuevo; si se repite, avisa al administrador.']);
            }
        })->afterResponse();

        return response()->json(['status' => 'working']);
    }

    /** Descargas del usuario: las que se están armando, las listas y las que terminaron sin archivo. */
    public function status(): JsonResponse
    {
        abort_unless(EvidenceZips::canDownload(), 403);

        return response()->json(collect(EvidenceZips::userJobs(auth()->id()))->map(fn (array $job) => [
            'token'   => $job['token'],
            'label'   => $job['label'] ?? 'Evidencias',
            'status'  => $job['status'] ?? 'working',
            'percent' => $job['percent'] ?? 0,
            'files'   => $job['files'] ?? null,
            'records' => $job['records'] ?? null,
            'message' => $job['message'] ?? null,
            'url'     => ($job['status'] ?? null) === 'ready' ? route('evidences.file', ['token' => $job['token']]) : null,
        ])->values());
    }

    /** Entrega el ZIP armado (una sola vez) y deja constancia. */
    public function file(Request $request)
    {
        abort_unless(EvidenceZips::canDownload(), 403);

        $token = (string) $request->query('token');
        $job   = EvidenceZips::job($token);
        abort_if(! $job || ($job['user'] ?? null) !== auth()->id() || ($job['status'] ?? null) !== 'ready' || ! is_file($job['path'] ?? ''), 404);

        EvidenceZips::forgetJob(auth()->id(), $token);

        // Ley 1581 de 2012: queda constancia de quién descargó qué.
        ExportLogger::log(EvidenceZips::logLabel($job['type']), [
            'file'    => $job['name'],
            'records' => $job['records'],
            'files'   => $job['files'],
        ]);

        return response()->download($job['path'], $job['name'])->deleteFileAfterSend(true);
    }

    /** El usuario cerró el aviso de una descarga terminada: se olvida y se borra su ZIP. */
    public function dismiss(Request $request): JsonResponse
    {
        abort_unless(EvidenceZips::canDownload(), 403);

        $token = (string) $request->input('token');
        $job   = EvidenceZips::job($token);

        if ($job && ($job['user'] ?? null) === auth()->id() && ($job['status'] ?? null) !== 'working') {
            if (! empty($job['path'])) {
                @unlink($job['path']);
            }
            EvidenceZips::forgetJob(auth()->id(), $token);
        }

        return response()->json(['status' => 'ok']);
    }

    /** ZIP que se armaron y nunca se descargaron. */
    private static function removeOldZips(): void
    {
        foreach (glob(storage_path('app/temp/evidencias_*.zip')) ?: [] as $file) {
            if (filemtime($file) < now()->subDay()->getTimestamp()) {
                @unlink($file);
            }
        }
    }
}
