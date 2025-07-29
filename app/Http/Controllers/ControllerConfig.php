<?php

namespace App\Http\Controllers;

use App\Models\Config;
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

        if ($request->hasFile('ruta_logo')) {
            $data['ruta_logo'] = $request->file('ruta_logo')->store('configs', 'public');
        }

        if ($request->hasFile('ruta_favicon')) {
            $data['ruta_favicon'] = $request->file('ruta_favicon')->store('configs', 'public');
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

        if ($request->hasFile('ruta_logo')) {
            if ($config->ruta_logo) {
                Storage::disk('public')->delete($config->ruta_logo);
            }
            $data['ruta_logo'] = $request->file('ruta_logo')->store('configs', 'public');
        }

        if ($request->hasFile('ruta_favicon')) {
            if ($config->ruta_favicon) {
                Storage::disk('public')->delete($config->ruta_favicon);
            }
            $data['ruta_favicon'] = $request->file('ruta_favicon')->store('configs', 'public');
        }

        $config->update($data);

        return response()->json($this->appendStorageUrls($config));
    }

    public function destroy($id)
    {
        $config = Config::findOrFail($id);

        if ($config->ruta_logo) {
            Storage::disk('public')->delete($config->ruta_logo);
        }

        if ($config->ruta_favicon) {
            Storage::disk('public')->delete($config->ruta_favicon);
        }

        $config->delete();

        return response()->json(['message' => 'Eliminado correctamente']);
    }

    private function appendStorageUrls(Config $config)
    {
        if ($config->ruta_logo) {
            $config->ruta_logo = asset('storage/' . $config->ruta_logo);
        }

        if ($config->ruta_favicon) {
            $config->ruta_favicon = asset('storage/' . $config->ruta_favicon);
        }

        return $config;
    }
}
