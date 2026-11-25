<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/institutional-documents/download-evidences', [\App\Http\Controllers\EvidencesDownloadController::class, 'download'])
    ->name('institutional-documents.download-evidences')
    ->middleware('auth');

Route::get('/institutional-documents/download-excel', [\App\Http\Controllers\EvidencesDownloadController::class, 'downloadExcel'])
    ->name('institutional-documents.download-excel')
    ->middleware('auth');

// "Descargar Evidencias" de Caracterización, Planes de negocio y Canvas.
Route::get('/evidences/download', [\App\Http\Controllers\EvidenceZipController::class, 'download'])
    ->name('evidences.download')
    ->middleware('auth');

// Archivos subidos: solo con sesión iniciada y sin mostrar dónde están.
Route::get('/archivos/{token}', [\App\Http\Controllers\ProtectedFileController::class, 'show'])
    ->name('files.show');

// Enlaces anteriores al cambio (Excel ya descargados).
Route::get('/storage/{path}', [\App\Http\Controllers\ProtectedFileController::class, 'legacy'])
    ->where('path', '.*')
    ->name('storage.protected');

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard'); // Va al dashboard si ya está logueado
    }

    return redirect('/dashboard/login'); // Va al login si no está logueado
});
