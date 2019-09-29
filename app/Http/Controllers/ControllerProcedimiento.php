<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use DB;

class ControllerProcedimiento extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $data = DB::table("procedimientos")->get();
        return $data;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($nombre,$precio)
    {
        $procedimiento = new App\Procedimiento();
        $procedimiento->nombre = $nombre;
        $procedimiento->precio = $precio;
        $procedimiento->save();

    }

 
    public function show($id)
    {
        $procedimiento = App\Procedimiento::find($id);
        return $procedimiento;
    }

    public function update($nombre,$precio,$id)
    {
        $procedimiento = App\Procedimiento::find($id);
        $procedimiento->nombre = $nombre;
        $procedimiento->precio = $precio;
        $procedimiento->save();
            
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $eliminar = App\Procedimiento::find($id);
        $eliminar->delete();
    }
}
