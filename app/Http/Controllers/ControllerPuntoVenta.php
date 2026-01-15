<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Producto;
use App\Factura;
use App\Recibo;
use App\Historial_p;
use DB;
use Carbon\Carbon;

class ControllerPuntoVenta extends Controller
{
    /**
     * Listar todos los productos activos
     */
    public function listarProductos()
    {
        try {
            $productos = Producto::where('activo', true)
                ->orderBy('nombre')
                ->get();
            
            return response()->json($productos);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar productos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear o actualizar un producto
     */
    public function guardarProducto(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:255',
                'codigo' => 'nullable|string|max:50',
                'precio' => 'required|numeric|min:0',
                'cantidad' => 'required|integer|min:0',
                'categoria' => 'nullable|string|max:100',
                'descripcion' => 'nullable|string',
                'stock_minimo' => 'nullable|integer|min:0',
                'usuario_id' => 'required|integer|exists:usuarios,id'
            ]);

            if ($request->id) {
                $producto = Producto::findOrFail($request->id);
                $producto->update($request->all());
                $mensaje = 'Producto actualizado correctamente';
            } else {
                $producto = Producto::create($request->all());
                $mensaje = 'Producto creado correctamente';
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'producto' => $producto
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al guardar producto',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Realizar una venta (crear factura de tipo venta)
     */
    public function realizarVenta(Request $request)
    {
        try {
            $request->validate([
                'productos' => 'required|array|min:1',
                'productos.*.id' => 'required|integer|exists:productos,id',
                'productos.*.cantidad' => 'required|integer|min:1',
                'id_doctor' => 'nullable|integer|exists:doctors,id',
                'id_paciente' => 'nullable|integer|exists:pacientes,id',
                'tipo_pago' => 'nullable|string',
                'monto_total' => 'required|numeric|min:0'
            ]);

            DB::beginTransaction();

            // Crear factura de tipo "venta"
            $factura = Factura::create([
                'id_doctor' => $request->id_doctor ?? 1, // Doctor por defecto o el que se envíe
                'id_paciente' => $request->id_paciente ?? 1, // Paciente genérico o el que se envíe
                'precio_estatus' => $request->monto_total,
                'tipo_de_pago' => $request->tipo_pago ?? 'efectivo',
                'tipo_factura' => 'venta'
            ]);

            // Registrar productos vendidos en historial_ps
            foreach ($request->productos as $item) {
                $producto = Producto::findOrFail($item['id']);
                
                // Verificar stock
                if ($producto->cantidad < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para el producto: {$producto->nombre}");
                }

                // Actualizar stock
                $producto->cantidad -= $item['cantidad'];
                $producto->save();

                // Registrar en tabla de ventas de productos
                $total = $producto->precio * $item['cantidad'];
                
                DB::table('ventas_productos')->insert([
                    'id_factura' => $factura->id,
                    'id_producto' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precio,
                    'total' => $total,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }

            // Crear recibo para la venta
            $recibo = Recibo::create([
                'id_factura' => $factura->id,
                'monto' => $request->monto_total,
                'total' => $request->monto_total,
                'tipo_de_pago' => $request->tipo_pago ?? 'efectivo',
                'estado_actual' => $request->monto_total,
                'concepto_pago' => 'Venta de productos',
                'codigo_recibo' => 'VENT-' . str_pad($factura->id, 6, '0', STR_PAD_LEFT),
                'fecha_pago' => Carbon::now(),
                'procedimientos' => json_encode($request->productos)
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta realizada correctamente',
                'factura_id' => $factura->id,
                'recibo_id' => $recibo->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al realizar la venta',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener productos con stock bajo
     */
    public function productosStockBajo()
    {
        try {
            $productos = Producto::where('activo', true)
                ->whereRaw('cantidad <= stock_minimo')
                ->orderBy('cantidad', 'asc')
                ->get();
            
            return response()->json($productos);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener productos con stock bajo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar o desactivar producto
     */
    public function eliminarProducto($id)
    {
        try {
            $producto = Producto::findOrFail($id);
            $producto->activo = false;
            $producto->save();

            return response()->json([
                'success' => true,
                'message' => 'Producto desactivado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar producto',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
