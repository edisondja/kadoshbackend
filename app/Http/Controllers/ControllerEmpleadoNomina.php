<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Empleado;
use App\Usuario;
use App\Services\NominaEmpleadoService;
use DB;

class ControllerEmpleadoNomina extends Controller
{
    protected $nominaService;

    public function __construct(NominaEmpleadoService $nominaService)
    {
        $this->nominaService = $nominaService;
    }

    public function listarEmpleados()
    {
        try {
            $empleados = Empleado::with('usuario')
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();

            return response()->json($empleados);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar empleados',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function listarUsuariosDisponibles()
    {
        try {
            $idsUsados = Empleado::where('activo', true)->pluck('usuario_id')->filter()->values();
            $usuarios = Usuario::whereNotIn('id', $idsUsados)
                ->orderBy('nombre')
                ->get(['id', 'usuario', 'nombre', 'apellido', 'roll']);

            return response()->json($usuarios);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar usuarios',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function guardarEmpleado(Request $request)
    {
        try {
            $request->validate([
                'usuario_id' => 'required|integer|exists:usuarios,id',
                'salario' => 'required|numeric|min:0',
                'aplica_afp' => 'nullable|boolean',
                'aplica_sfs' => 'nullable|boolean',
                'aplica_isr' => 'nullable|boolean',
                'porcentaje_afp' => 'nullable|numeric|min:0|max:100',
                'porcentaje_sfs' => 'nullable|numeric|min:0|max:100',
                'porcentaje_isr' => 'nullable|numeric|min:0|max:100',
                'otros_descuentos' => 'nullable|numeric|min:0',
                'comentarios_nomina' => 'nullable|string',
                'telefono' => 'nullable|string|max:50',
                'movil' => 'nullable|string|max:50',
                'direccion' => 'nullable|string|max:255',
            ]);

            DB::beginTransaction();

            $usuario = Usuario::findOrFail($request->usuario_id);

            if ($request->id) {
                $empleado = Empleado::findOrFail($request->id);
            } else {
                $existente = Empleado::where('usuario_id', $request->usuario_id)->where('activo', true)->first();
                if ($existente) {
                    return response()->json([
                        'error' => 'Este usuario ya está registrado como empleado'
                    ], 422);
                }
                $empleado = new Empleado();
            }

            $empleado->usuario_id = $request->usuario_id;
            $empleado->nombre = $usuario->nombre ?: $usuario->usuario;
            $empleado->apellido = $usuario->apellido ?: '';
            $empleado->telefono = $request->input('telefono', '');
            $empleado->movil = $request->input('movil', '');
            $empleado->direccion = $request->input('direccion', '');
            $empleado->salario = $request->salario;
            $empleado->activo = true;
            $empleado->aplica_afp = filter_var($request->input('aplica_afp', false), FILTER_VALIDATE_BOOLEAN);
            $empleado->aplica_sfs = filter_var($request->input('aplica_sfs', false), FILTER_VALIDATE_BOOLEAN);
            $empleado->aplica_isr = filter_var($request->input('aplica_isr', false), FILTER_VALIDATE_BOOLEAN);
            $empleado->porcentaje_afp = $request->input('porcentaje_afp', 2.87);
            $empleado->porcentaje_sfs = $request->input('porcentaje_sfs', 3.04);
            $empleado->porcentaje_isr = $request->input('porcentaje_isr');
            $empleado->otros_descuentos = $request->input('otros_descuentos', 0);
            $empleado->comentarios_nomina = $request->input('comentarios_nomina');
            $empleado->save();

            $deducciones = $this->nominaService->calcularDeducciones($empleado->salario, $empleado->toArray());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Empleado guardado correctamente',
                'empleado' => $empleado->load('usuario'),
                'vista_previa' => $deducciones
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al guardar empleado',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function desactivarEmpleado($id)
    {
        try {
            $empleado = Empleado::findOrFail($id);
            $empleado->activo = false;
            $empleado->save();

            return response()->json([
                'success' => true,
                'message' => 'Empleado desactivado'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al desactivar empleado',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function calcularDeduccionesPreview(Request $request)
    {
        try {
            $request->validate([
                'salario' => 'required|numeric|min:0',
            ]);

            $config = $request->only([
                'aplica_afp', 'aplica_sfs', 'aplica_isr',
                'porcentaje_afp', 'porcentaje_sfs', 'porcentaje_isr', 'otros_descuentos'
            ]);

            $resultado = $this->nominaService->calcularDeducciones($request->salario, $config);

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al calcular deducciones',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
