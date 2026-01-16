<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App;
use App\Factura;
use App\Descuento;
use App\Helpers\AuditoriaHelper;

class ControllerFactura extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function descontar_estatus($id_factura,$cantidad,$comentario){
        
        $factura = App\Factura::find($id_factura);
        $estado_actual = $factura->precio_estatus - $cantidad;
        $factura->precio_estatus = $estado_actual;
        $factura->save();

        $descuentos = new App\Descuento();
        $descuentos->monto = $cantidad;
        $descuentos->id_factura = $id_factura;
        $descuentos->comentario  = $comentario;
        $descuentos->save();

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $data)
    {
        try {
            $total = $data->input('total');
            $id_doctor = $data->input('id_doctor');
            $id_paciente = $data->input('id_paciente');

            // Validar que los campos requeridos estén presentes
            if (!$id_doctor || !$id_paciente || !$total) {
                return response()->json([
                    'error' => 'Error de validación',
                    'message' => 'Faltan campos requeridos: id_doctor, id_paciente o total'
                ], 422);
            }

            // Validar que el doctor existe
            $doctor = DB::table('doctors')->where('id', $id_doctor)->first();
            if (!$doctor) {
                return response()->json([
                    'error' => 'Error de validación',
                    'message' => 'El doctor especificado no existe'
                ], 422);
            }

            // Validar que el paciente existe
            $paciente = DB::table('pacientes')->where('id', $id_paciente)->first();
            if (!$paciente) {
                return response()->json([
                    'error' => 'Error de validación',
                    'message' => 'El paciente especificado no existe (ID: ' . $id_paciente . ')'
                ], 422);
            }

            // Crear la factura
            $id_factura = DB::table("facturas")->insertGetId([
                'id_doctor' => $id_doctor,
                'id_paciente' => $id_paciente,
                'precio_estatus' => $total,
                'tipo_de_pago' => $data->input('tipo_de_pago') ?? null,
                'tipo_factura' => $data->input('tipo_factura') ?? 'servicio'
            ]);

            // Registrar en auditoría
            $usuarioId = $data->input('usuario_id') ?? $data->header('usuario_id') ?? null;
            if ($usuarioId) {
                AuditoriaHelper::registrar(
                    $usuarioId,
                    'Facturas',
                    'Crear Factura',
                    "Factura #{$id_factura} creada para paciente ID: {$id_paciente}, Doctor ID: {$id_doctor}, Total: RD$ " . number_format($total, 2)
                );
            }
       

            // Procesar procedimientos si existen
            $procedimientos = $data->input('procedimientos');
            if ($procedimientos && is_array($procedimientos) && count($procedimientos) > 0) {
                $procedimientosx = $procedimientos[0];
                $cantidad = count($procedimientosx);
                $array_new = [];
        
                for($i=0; $i<$cantidad; $i++){
                    // Validar que el procedimiento existe
                    $procedimiento = DB::table('procedimientos')->where('id', $procedimientosx[$i]['id_procedimiento'])->first();
                    if (!$procedimiento) {
                        // Si el procedimiento no existe, continuar con el siguiente
                        \Log::warning('Procedimiento no encontrado: ' . $procedimientosx[$i]['id_procedimiento']);
                        continue;
                    }

                    $array_new[] = [
                        'id_factura' => $id_factura,
                        'total' => $procedimientosx[$i]['total'] ?? 0,
                        'cantidad' => $procedimientosx[$i]['cantidad'] ?? 1,
                        'id_procedimiento' => $procedimientosx[$i]['id_procedimiento']
                    ];
                }

                if (count($array_new) > 0) {
                    DB::table('historial_ps')->insert($array_new);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Factura guardada con éxito',
                'id_factura' => $id_factura
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error al crear factura: ' . $e->getMessage());
            \Log::error('SQL: ' . $e->getSql());
            \Log::error('Bindings: ' . json_encode($e->getBindings()));
            
            // Verificar si es un error de foreign key
            if ($e->getCode() == 23000) {
                return response()->json([
                    'error' => 'Error de integridad',
                    'message' => 'No se puede crear la factura. Verifique que el paciente y el doctor existan en el sistema.'
                ], 422);
            }
            
            return response()->json([
                'error' => 'Error al guardar factura',
                'message' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Error al crear factura: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Error al guardar factura',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function cargar_una_factura($id_factura){
        
        $factura = DB::table("facturas")->join("doctors","facturas.id_doctor","=","doctors.id")->where("facturas.id","=",$id_factura)->get();
        return $factura;

        

     }


    public function ConsultarProcedimientos($id){

        $persona = App\Procedimiento::find($id);
    
        return $persona;
    }

    public function eliminar_factura(Request $request, $id_factura){
        try {
            // Validar clave secreta
            $claveSecreta = $request->input('clave_secreta');
            $config = App\Config::first();
            
            if (!$config || !$config->clave_secreta) {
                return response()->json([
                    'error' => 'Error de configuración',
                    'message' => 'No se ha configurado una clave secreta. Por favor configúrela primero.'
                ], 400);
            }

            if ($claveSecreta !== $config->clave_secreta) {
                return response()->json([
                    'error' => 'Clave secreta incorrecta',
                    'message' => 'La clave secreta proporcionada no es correcta. No se puede eliminar la factura.'
                ], 403);
            }

            // Registrar en auditoría
            $usuarioId = $request->input('usuario_id') ?? $request->header('usuario_id') ?? null;
            $factura = App\Factura::find($id_factura);
            
            if ($factura && $usuarioId) {
                AuditoriaHelper::registrar(
                    $usuarioId,
                    'Facturas',
                    'Eliminar Factura',
                    "Factura #{$id_factura} eliminada. Total: RD$ " . number_format($factura->precio_estatus, 2)
                );
            }

            $factura->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Factura eliminada correctamente'
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar factura: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al eliminar factura',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function cargar_facturas(){

            $data = DB::table('facturas')->get();
            return $data;
    }

    public function cargar_procedimientos_factura($id_factura){

        $data = DB::table('historial_ps')->join('procedimientos','historial_ps.id_procedimiento','=','procedimientos.id')->select('historial_ps.*','procedimientos.*','historial_ps.id as id_historial')->where('historial_ps.id_factura','=',$id_factura)->get();
        return $data;
    }

    public function cargar_recibos($id_factura){

           $data =  DB::table('recibos')->where('id_factura','=',$id_factura)->get();
           return $data;
    }

    public function EliminarProcedimiento($id){

        $procedimiento = App\Procedimiento::find($id);
        $procedimiento->delete();
        $procedimiento->save();
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

     public function Facturas_de_paciente($id_paciente){

        $data = DB::table("facturas")->where("id_paciente",$id_paciente)->orderBy('id','desc')->get();
        return $data;

     }


     public function create_presupuesto(Request $data)
     {
 
        $total =  $data->input('total');
        //$cantidad = $data->input('cantidad');
        $id_doctor = $data->input('id_doctor');
        $id_paciente = $data->input('id_paciente');
 
        //return $total." ".$id_doctor." ".$id_paciente;
        $id_factura=DB::table("presupuestos")->insertGetId([
                 'id_doctor'=>$id_doctor,
                 'id_paciente'=>$id_paciente,
                 'precio_estatus'=>$total
 
        ]);
        
 
       // return $id_factura;
        $id_factura= array("id_factura"=>$id_factura);
 
         //return $id_doctor;
        $procedimientos = $data->input('procedimientos');
        $procedimientosx = $procedimientos[0];
        $cantidad = count($procedimientosx);
        $array_new = [];
   
         for($i=0;$i<$cantidad;$i++){
         
 
                 $array_new[] = [
                         'id_factura'=>$id_factura['id_factura'],
                         'total'=>$procedimientosx[$i]['total'],
                         'cantidad'=>$procedimientosx[$i]['cantidad'],
                         'id_procedimiento'=>$procedimientosx[$i]['id_procedimiento']
                 ];
         }
 
         DB::table('historial_ps')->insert($array_new);
         return  $array_new;
 
         return "factura guardada con exito";
 
 
     
     }





}
