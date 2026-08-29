<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppUpdateController extends Controller
{
    /**
     * Path al archivo de configuración de versión.
     */
    private string $versionFilePath = 'app-version.json';

    /**
     * Obtiene la información de la última versión de la app móvil.
     */
    public function check(Request $request): JsonResponse
    {
        $info = $this->getVersionInfo($request);

        return response()->json([
            'success' => true,
            'data' => $info,
        ]);
    }

    /**
     * Descarga el APK solicitado.
     */
    public function download(Request $request, ?string $file = null)
    {
        $info = $this->getVersionInfo($request);
        $fileName = $file ?? ($info['apk_filename'] ?? 'app-release.apk');

        // Limpiar nombre de archivo por seguridad
        $fileName = basename($fileName);
        if (!str_ends_with(strtolower($fileName), '.apk')) {
            $fileName .= '.apk';
        }

        // Buscar en posibles ubicaciones
        $possiblePaths = [
            storage_path('app/public/apks/' . $fileName),
            storage_path('app/apks/' . $fileName),
            public_path('downloads/' . $fileName),
            public_path('apks/' . $fileName),
        ];

        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                return response()->download($path, $fileName, [
                    'Content-Type' => 'application/vnd.android.package-archive',
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => "El archivo APK '{$fileName}' no se encuentra en el servidor. Colócalo en 'public/downloads/' o 'storage/app/public/apks/'.",
        ], 404);
    }

    /**
     * Carga o genera la información de versión actual.
     */
    private function getVersionInfo(Request $request): array
    {
        $default = [
            'version_name' => '1.0.0',
            'version_code' => 7,
            'release_notes' => "Versión actual del sistema de empaque Plasencia.\n• Escaneo rápido de actividades\n• Soporte de sincronización offline\n• Selector de tema claro y oscuro",
            'apk_filename' => 'app-arm64-v8a-release.apk',
            'force_update' => false,
            'published_at' => '2026-08-29 08:00:00',
            'download_url' => null,
            'split_apks' => [
                'arm64-v8a' => null,
                'armeabi-v7a' => null,
                'universal' => null,
            ],
        ];

        if (Storage::disk('local')->exists($this->versionFilePath)) {
            try {
                $saved = json_decode(Storage::disk('local')->get($this->versionFilePath), true) ?: [];
                $default = array_merge($default, $saved);
            } catch (\Throwable $e) {
                // Usar valores por defecto si hay error al parsear
            }
        }

        // Resolver URLs dinámicas basadas en el host actual de la petición
        $baseUrl = $request->getSchemeAndHttpHost();

        if (empty($default['download_url'])) {
            $default['download_url'] = $baseUrl . '/api/app/download/' . ($default['apk_filename'] ?? 'app-release.apk');
        }

        $default['split_apks'] = [
            'arm64-v8a' => $baseUrl . '/api/app/download/app-arm64-v8a-release.apk',
            'armeabi-v7a' => $baseUrl . '/api/app/download/app-armeabi-v7a-release.apk',
            'universal' => $baseUrl . '/api/app/download/app-release.apk',
        ];

        return $default;
    }
}
