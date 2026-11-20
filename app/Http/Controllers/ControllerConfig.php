<?php

namespace App\Http\Controllers;

use App\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfigController extends Controller
{
    public function index()
    {
        $configs = Config::all();
        return response()->json($configs->map(function($config) {
            return $this->appendStorageUrls($config);
        }));
    }

    public function store(Request $request)
    {
        $data = $request->all();

        $quota = app(\App\Services\StorageQuotaService::class);
        if ($request->hasFile('ruta_logo')) {
            $data['ruta_logo'] = $quota->store($request->file('ruta_logo'), 'configs');
        }

        if ($request->hasFile('ruta_favicon')) {
            $data['ruta_favicon'] = $quota->store($request->file('ruta_favicon'), 'configs');
        }

        $config = Config::create($data);

        return response()->json($this->appendStorageUrls($config), 201);
    }

    public function show($id)
    {
        $config = Config::findOrFail($id);
        return response()->json($this->appendStorageUrls($config));
    }

    public function update(Request $request, $id)
    {
        $config = Config::findOrFail($id);
        $data = $request->all();

        // Manejar campos booleanos
        if (isset($data['usar_google_calendar'])) {
            $data['usar_google_calendar'] = filter_var($data['usar_google_calendar'], FILTER_VALIDATE_BOOLEAN);
        }
        foreach (['dgii_ecf_activo', 'dgii_auto_emitir', 'dgii_cert_loaded'] as $boolField) {
            if (isset($data[$boolField])) {
                $data[$boolField] = filter_var($data[$boolField], FILTER_VALIDATE_BOOLEAN);
            }
        }
        // Contraseña del .p12: cifrar; vacío = no tocar
        if (array_key_exists('dgii_cert_password', $data)) {
            $plain = trim((string) $data['dgii_cert_password']);
            if ($plain === '' || $plain === '********') {
                unset($data['dgii_cert_password']);
            } else {
                $data['dgii_cert_password'] = \Illuminate\Support\Facades\Crypt::encryptString($plain);
            }
        }

        $quota = app(\App\Services\StorageQuotaService::class);
        if ($request->hasFile('ruta_logo')) {
            if ($config->ruta_logo) {
                $quota->forget($config->ruta_logo);
            }
            $data['ruta_logo'] = $quota->store($request->file('ruta_logo'), 'configs');
        } else {
            unset($data['ruta_logo']);
        }

        if ($request->hasFile('ruta_favicon')) {
            if ($config->ruta_favicon) {
                $quota->forget($config->ruta_favicon);
            }
            $data['ruta_favicon'] = $quota->store($request->file('ruta_favicon'), 'configs');
        } else {
            unset($data['ruta_favicon']);
        }

        $config->update($data);

        return response()->json($this->appendStorageUrls($config));
    }

    public function destroy($id)
    {
        $config = Config::findOrFail($id);

        $quota = app(\App\Services\StorageQuotaService::class);
        if ($config->ruta_logo) {
            $quota->forget($config->ruta_logo);
        }

        if ($config->ruta_favicon) {
            $quota->forget($config->ruta_favicon);
        }

        $config->delete();

        return response()->json(['message' => 'Eliminado correctamente']);
    }

    private function appendStorageUrls(Config $config)
    {
        $quota = app(\App\Services\StorageQuotaService::class);
        if ($config->ruta_logo) {
            $config->ruta_logo = $quota->publicUrl($config->ruta_logo);
        }

        if ($config->ruta_favicon) {
            $config->ruta_favicon = $quota->publicUrl($config->ruta_favicon);
        }

        // Nunca exponer la contraseña del certificado
        $config->makeHidden(['dgii_cert_password']);
        $config->dgii_tiene_password = !empty($config->getAttributes()['dgii_cert_password'] ?? null)
            || !empty(config('dgii.cert_password'));
        $config->storage_disk = $quota->mediaDiskName();
        $config->storage_public_base = $quota->usesSpaces()
            ? rtrim((string) config('filesystems.disks.spaces.url'), '/')
            : rtrim(asset('storage'), '/');

        return $config;
    }
}
