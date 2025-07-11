<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Mail;
use App;
use Carbon\Carbon;
use App\Mail\KadoshNotificacion;


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

    public function pagar_recibo($id_factura,$monto,$tipo_de_pago,$estado_actual,$codigo_tarjeta,$total=0,$procedimientos=[]){

        //capturando el ultimo registro de recibo

       // return $id_factura." ".$monto." ".$tipo_de_pago." ".$estado_actual." ".$codigo_tarjeta;
        $ultimo_recibo = DB::table('recibos')->orderBy('id','desc')->first();

       // return "ULTIMO RECIBO!!!--->".$ultimo_recibo;
        if($ultimo_recibo!=""){

            $numero = ($ultimo_recibo->id + 1);
            $codigo ="B02".str_pad($numero, 7, "0", STR_PAD_LEFT);
    
        }else{

            $numero=1;
            $codigo="B02".str_pad($numero, 7, "0", STR_PAD_LEFT);
        }
 
            $recibo = new App\Recibo();
            $recibo->id_factura = $id_factura;
            $recibo->monto = $monto;
    

            if($codigo_tarjeta=="ts"){
                $recibo->tipo_de_pago =  "Transferencia Bancaria";
                $recibo->concepto_pago = "Transferencia Bancaria";
            }else{

                if($codigo_tarjeta!="ef"){
                    $recibo->tipo_de_pago =  "Pago en tarjeta";
                    $recibo->concepto_pago = "tarjeta";

                }else{
                    $recibo->concepto_pago = "efectivo";
                    $recibo->tipo_de_pago = "Pago en efectivo";
                }
            }
            $recibo->codigo_recibo = $codigo;
            $recibo->total = $total;
            $recibo->procedimientos = $procedimientos;
            $recibo->estado_actual = ($estado_actual - $monto);
            $recibo->fecha_pago = date("Y-m-d H:i:s");
            $recibo->save();
            
            $factura = App\Factura::find($id_factura);
            $factura->precio_estatus =$factura->precio_estatus - $monto;
           
            $factura->save();
            
            return ["ready"=>"payment"];

    }

    public function ingresos_de_meses(){

        
    
        return;
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

    public function ingresos_de_semana($fecha){
       // Carbon::parse('2017-05-01')si desaea obtener la semana de otra fecha en lugar de Carbon::now()
        
       if($fecha=='hoy'){
            $data = App\Recibo::where('created_at', '>', Carbon::now()->startOfWeek())
            ->where('created_at', '<', Carbon::now()->endOfWeek())
            ->get();
        }else{
            $data = App\Recibo::where('created_at', '>', Carbon::parse($fecha)->startOfWeek())
            ->where('created_at', '<', Carbon::parse($fecha)->endOfWeek())
            ->get();
        }

        $semana = [];
        $ingreso_de_dias =[
            'lunes'=>0,
            'martes'=>0,
            'miercoles'=>0,
            'jueves'=>0,
            'viernes'=>0,
            'sabado'=>0
        ];


        foreach ($data as $key) {
           // echo $key['nombre'] . "\n";
    
           $timestamp = strtotime($key['fecha_pago']);
            $mydate = getdate($timestamp);
    
            if ($mydate['weekday'] == 'Monday') {

                $ingreso_de_dias['lunes']+=(int)$key['monto'];
                $semana['Monday'][] = $key;

            } elseif ($mydate['weekday'] == 'Tuesday') {
                
                $ingreso_de_dias['martes']+=(int)$key['monto'];
                $semana['Tuesday'][] = $key;
            
            } elseif ($mydate['weekday'] == 'Wednesday') {

                $ingreso_de_dias['miercoles']+=(int)$key['monto'];
                $semana['Wednesday'][] = $key;
           
            } elseif ($mydate['weekday'] == 'Thursday') {

               $ingreso_de_dias['jueves']+=(int)$key['monto'];
                $semana['Thursday'] = $key;
           
            } elseif ($mydate['weekday'] == 'Friday') {

                $ingreso_de_dias['viernes']+=(int)$key['monto'];
                $semana['Friday'][] = $key;

            } elseif ($mydate['weekday'] == 'Saturday') {

                $ingreso_de_dias['sabado']+=(int)$key['monto'];
                $semana['Saturday'][] = $key;

            }
        }

        return $ingreso_de_dias;
    }

   public function reporte_recibos($fecha_inicial, $fecha_final)
    {
        $tiempo_inicial = '00:00:00';
        $tiempo_final = '23:59:59';

        $desde = $fecha_inicial . ' ' . $tiempo_inicial;
        $hasta = $fecha_final . ' ' . $tiempo_final;

        // Total de monto (no necesita relaciones)
        $total = App\Recibo::whereBetween('fecha_pago', [$desde, $hasta])->sum('monto');

        // Recibos con factura y paciente (a través de factura)
        $recibos = App\Recibo::with('factura.paciente')
            ->whereBetween('fecha_pago', [$desde, $hasta])
            ->get();

        return [
            'monto_total' => (int) $total,
            'recibos' => $recibos
        ];
    }

    public function notificar_reporte(){

        

       

        Mail::send('reporte', ['user' => 'co'], function ($m){
            $m->from('hello@app.com', 'Your Application');

            $m->to('edisondja@gmail.com','Edison')->subject('Your Reminder!');
        });

    }
}