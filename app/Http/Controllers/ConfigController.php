<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Config;

class ConfigController extends Controller
{
    public function index()
    {
        $configs = Config::all();
        return response()->json($configs);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $config = Config::create($data);
        return response()->json($config, 201);
    }

    public function show($id)
    {
        $config = Config::findOrFail($id);
        return response()->json($config);
    }

    public function update(Request $request, $id)
    {
        $config = Config::findOrFail($id);
        $config->update($request->all());
        return response()->json($config);
    }

    public function destroy($id)
    {
        $config = Config::findOrFail($id);
        $config->delete();
        return response()->json(null, 204);
    }
}
