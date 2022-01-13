<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Descuento;
use App\Factura;

class ControllerDescuento extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function descontar(Request $data)
    {
        $descuentos = new App\Descuento();
        $descuentos->monto-=$data->input('monto');
        $descuentos->id_factura = $data->input('id_factura');
        $descuentos->save();
        

      /*/  $descuento = Descuento::find($data->input('id_factura'));
        $descuento->monto = ($descuento->monto - $data->input('monto'));
        $descuento->save();
        */
    }

    public function consultar_descuentos($id_factura)
    {
        $descuentos = Descuento::where("id_factura",$id_factura)->get();
        return $descuentos;
    }

    public function eliminar_descuento(Request $data){
        
        $des = Descuento::find("id",$data->input('id_descuento'));
        $cantidad = $des->monto; 
        $id_factura = $des->id_factura;
        $des->delete();
        $des->save();

        $factura = App\Factura::find($id_factura);
        $estado_actual = $factura->precio_estatus + $cantidad;
        $factura->precio_estatus = $estado_actual;
        $factura->save();

        return response()->json(['cantidad'=>$cantidad]);
    }

}
