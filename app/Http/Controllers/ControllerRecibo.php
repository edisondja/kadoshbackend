<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App;

class ControllerRecibo extends Controller
{
  
    public function cargar_recibos($id_factura){
    
            $recibos =  DB::table('recibos')->where('id_factura',"=",$id_factura)->orderBy('id','desc')->get();
            return $recibos;
    }

    public function actualizar_recibo($id_recibo,$monto,$tipo_de_pago,$estado_actual){

        
        $recibo = App\Recibo::find($id_recibo);
        $valor = $recibo->monto;
        $recibo->monto = $monto;
        $recibo->tipo_de_pago = $tipo_de_pago;
        $recibo->save();


        //alterando el estado en el estatus de la factura por el nuevo estado
        $factura = App\Factura::find($id_recibo);
        $factura = $factura->precio_estatus = ($factura->precio_estatus - $valor) + $monto;
        $factura->save();


    }

    public function eliminar_recibo($id_recibo,$id_factura){

        $recibo = DB::table('recibos')->where('id',$id_recibo)->get();   
        $restablecer = $recibo[0]->monto;

        $factura = App\Factura::find($id_factura);
        $factura->precio_estatus = $factura->precio_estatus + $restablecer;
        $factura->save();

        $recibo = App\Recibo::find($id_recibo)->delete();

    }

    public function pagar_recibo($id_factura,$monto,$tipo_de_pago,$estado_actual){

        //capturando el ultimo registro de recibo
        $ultimo_recibo = DB::table('recibos')->orderBy('id','desc')->first();
        $numero = $ultimo_recibo->id + 1;
        $codigo ="B02".str_pad($numero, 7, "0", STR_PAD_LEFT);

            $recibo = new App\Recibo();
            $recibo->id_factura = $id_factura;
            $recibo->monto = $monto;
            $recibo->concepto_pago = "normal";
            $recibo->tipo_de_pago =  $tipo_de_pago;
            $recibo->codigo_recibo = $codigo;
            $recibo->estado_actual = ($estado_actual - $monto);
            $recibo->fecha_pago = date("Y-m-d H:i:s");
            $recibo->save();
            
            $factura = App\Factura::find($id_factura);
            $factura->precio_estatus =$factura->precio_estatus - $monto;
            $factura->save();

            return ["ready"=>"payment"];

    }

    public function imprimir_recibo($id_recibo,$id_factura){

        $recibo = DB::table("recibos")->join("facturas","recibos.id_factura","=","facturas.id")->join("doctors","facturas.id_doctor","=","doctors.id")->where("recibos.id","=",$id_recibo)->join("pacientes","facturas.id_paciente","=","pacientes.id")->select("recibos.*","facturas.*","doctors.*","pacientes.nombre as paciente","pacientes.apellido as apellido_paciente")->get();       
        $procedimientos = DB::table("historial_ps")->join("procedimientos","historial_ps.id_procedimiento","=","procedimientos.id")->where("historial_ps.id_factura","=",$id_factura)->get();
        $recibo_interfaz =[
            'recibo'=>$recibo[0],
            'procedimientos'=>$procedimientos
        ];

        return $recibo_interfaz;
    }

    public function reporte_recibos($fecha_inicial,$fecha_final){

        $tiempo_inicial = '00:00:00';
        $tiempo_final = '23:59:59';
        
        $total = DB::table('recibos')
        ->where('fecha_pago','>=',$fecha_inicial." ".$tiempo_inicial)
        ->where('fecha_pago','<=',$fecha_final." ".$tiempo_final)->sum('monto');
        
        $recibos = DB::table('recibos')
        ->where('fecha_pago','>=',$fecha_inicial." ".$tiempo_inicial)
        ->where('fecha_pago','<=',$fecha_final." ".$tiempo_final)->get();

        return [
                'monto_total'=>$total,
                'recibos'=>$recibos 
               ];


    }

}