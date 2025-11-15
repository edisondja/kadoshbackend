<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


	//API LOGIN
	//Route::get('/api/login','LoginController@Login');
	//Route::get('/api/actualizar_usuario/{usuario}/{clave}/{nombre}/{apellido}/{id}','LoginContorller@AactualizarUsuario');
	//Route::get('/api/eliminar_usuario/{id}','LoginContorller@AactualizarUsuario');
	//Route::get('/api/consultar_usuarios','LoginController@CargarUsuarios');

	//API PACIENTES READY
	
Route::middleware(['tenant'])->group(function () {

	Route::post('/api/guardar_paciente','Paciente@guardar');
	Route::get('/api/paciente','Paciente@index');	
	Route::get('/api/paciente/{id_paciente}','Paciente@show');
	Route::get('/api/borrar_paciente/{id_paciente}','Paciente@destroy');
	Route::post('/api/actualizar_foto_paciente','Paciente@actualizar_foto_paciente');
	Route::get('/api/notificar_cumple','Paciente@notificar_cumple');
	Route::post('/api/actualizar_paciente','Paciente@update');
	Route::get('/api/buscar_paciente/{nombre}','Paciente@buscando_paciente');
	Route::get('/api/consultar_deuda/{id_paciente}','Paciente@deuda_paciente');
	Route::get('/api/cargar_generos_pacientes','Paciente@cargar_generos');
	Route::get('/api/cantidad_de_pacientes','Paciente@cantidad_de_pacientes');

	///API DOCTORES
	Route::get('/api/doctores/','ControllerDoctor@index');
	Route::get('/api/eliminar_doctor/{id}','ControllerDoctor@destroy');
	Route::get('/api/actualizar_doctor/{nombre}/{apellido}/{cedula}/{telefono}/{id}','ControllerDoctor@edit');
	Route::get('/api/cargar_doctor/{id_doctor}','ControllerDoctor@cargar_doctor');
	Route::get('/api/crear_doctor/{nombre}/{apellido}/{cedula}/{telefono}','ControllerDoctor@create');
	Route::get('/api/buscando_doctor/{nombre}','ControllerDoctor@buscando_doctor');

	/// API  
	Route::get('/api/citas', 'ControllerCita@index');
	Route::get('api/citas_doctor/{doctor_id}', 'ControllerCita@citas_doctor');
	Route::get('/api/cargar_citas_de_paciente/{paciente_id}', 'ControllerCita@citas_paciente');
	Route::get('/api/citas/{id}', 'ControllerCita@show');
	Route::post('/api/guardar_cita', 'ControllerCita@store');
	Route::put('/api/actualizar_cita/{id}', 'ControllerCita@update');
	Route::delete('/api/eliminar_cita/{id}', 'ControllerCita@destroy');

	
	///API FACTURAS
	Route::post('/api/crear_factura','ControllerFactura@create');
	Route::get('/api/buscar_facutura/{id_factura}','ControllerFactura@buscar_factura');
	Route::get('/api/eliminar_factura/{id_factura}','ControllerFactura@eliminar_factura');
	Route::get('/api/editar_factura/{id_factura}','ControllerFactura@edit');
	Route::post('/api/editando_factura/','ControllerFactura@update');
	Route::get('/api/cargar_procedimientos_de_factura/{id}','ControllerFactura@cargar_procedimientos_factura');
	Route::get('/api/cargar_facturas','ControllerFactura@cargar_facturas');
	Route::get('/api/cargar_factura/{id_factura}','ControllerFactura@cargar_una_factura');
	Route::get('/api/cargar_facturas_paciente/{id_paciente}','ControllerFactura@Facturas_de_paciente');
	Route::get('/api/descontar_precio_factura/{id_factura}/{monto}/{comentario}','ControllerFactura@descontar_estatus');
	///API TEMP

	///PROCEDIMIENTOS
	Route::get('/api/cargar_procedimientos','ControllerProcedimiento@index');
	Route::get('/api/cargar_procedimiento/{id}','ControllerProcedimiento@show');
	Route::post('/api/guardar_procedimiento','ControllerProcedimiento@create');
	Route::post('/api/actualizar_procedimiento','ControllerProcedimiento@update');
	Route::get('/api/eliminar_procedimiento/{id}','ControllerProcedimiento@destroy');
	Route::get('/api/buscar_procedimiento/{buscar}','ControllerProcedimiento@buscarProcedimiento');

	
	//RECIBOS
	Route::post('/api/pagar_recibo', 'ControllerRecibo@pagar_recibo');
	Route::get('/api/actualizar_recibo/{id_recibo}','ControllerRecibo@actualizar_recibo');
	Route::get('/api/eliminar_recibo/{id_recibo}/{id_facutara}','ControllerRecibo@eliminar_recibo');
	Route::get('/api/cargar_recibos/{id_factura}','ControllerRecibo@cargar_recibos');
	Route::get('/api/cargar_recibo/{id_recibo}','ControllerRecibo@cargar_recibo');
	Route::get('/api/imprimir_recibo/{id_recibo}/{id_factura}','ControllerRecibo@imprimir_recibo');
	Route::get('/api/ingresos_de_mes/','ControllerRecibo@ingresos_en_meses');
	Route::get('/api/ingresos_de_semana/{fecha}','ControllerRecibo@ingresos_de_semana');
	Route::post('/api/enviar_recibo','ControllerRecibo@enviarRecibo');
	Route::post('/api/subir_recibo_temp','ControllerRecibo@subir_factura_temp');



	//CARGAR HISTORIAL_PS

	Route::get('/api/cargar_historial_ps/{id_factura}','ControllerHistorialps@cargar_procedimientos');
	Route::get('/api/eliminar_historial_ps/{id_factura}','ControllerHistorialps@eliminar_procedimiento');
	Route::get('/api/eliminar_procedimiento/lista/{id_procedimiento}/{id_factura}/{total}','ControllerProcedimiento@eliminar_procedimiento_lista');
	Route::get('/api/agregar_procedimiento_lista/{id_factura}/{id_procedimiento}/{total}/{cantidad}','ControllerProcedimiento@agregar_procedimiento_a_lista');
	Route::get('/api/procedimientos_realizados','ControllerFinanciero@procedimientos_realizados');
	//Route::get('/api/editar_historial_ps/{id_factura}','Controller');

	//USUARIOS
	Route::get('/api/login/{usuario}/{clave}','ControllerUsuario@login');

	///REPORTES
	Route::get('/api/facturas_reportes/{fecha_inicial}/{fecha_final}','ControllerRecibo@reporte_recibos');
	Route::get('/api/notificar_ingresos','ControllerRecibo@notificar_reporte');

	//Descuentos Factura

	 Route::post('/api/guardar_descuento','ControllerDescuento@descontar');
	 Route::get('/api/consultar_descuentos/{id_factura}','ControllerDescuento@consultar_descuentos');
	 Route::post('/api/eliminar_descuento/','ControllerDescuento@eliminar_descuento');
	 Route::get('/test',function(){
		 return "ready";
	 });

	 //Administracion 
	 /*
	Route::get('/api/cargar_suplidores','ControllerFinanciero@suplidores');
	Route::post('/api/eliminar_suplidor','ControllerFinanciero@eliminar_suplidor');
	Route::post('/api/actualizar_suplidor','ControllerFinanciero@actualizar_suplidor');
	Route::get('/api/buscar_suplidores','ControllerFinanciero@buscar_suplidor');
	 */

	//Gastos
	Route::post('/api/registrar_gastos','ControllerFinanciero@registrar_gastos');
	Route::get('/api/cargar_gasto/{id}','ControllerFinanciero@cargar_gasto');
	Route::get('/api/cargar_gastos','ControllerFinanciero@cargar_gastos');
	Route::post('/api/actualizar_gasto','ControllerFinanciero@actualizar_gasto');
	Route::get('/api/eliminar_gasto/{id}','ControllerFinanciero@eliminar_gasto');
	Route::get('/api/buscar_gasto/{id}','ControllerFinanciero@buscar_gasto');
	Route::get('/api/fecha_gastos/{fecha_i}/{fecha_f}','ControllerFinanciero@buscar_por_fecha');
	Route::get('/api/cargar_gastos_fecha/{fecha_i}/{fecha_f}','ControllerFinanciero@cargar_gastos_fecha');


	//Suplidores
	Route::get('/api/buscar_suplidor/{nombre}','ControllerFinanciero@buscar_suplidor');
	Route::get('/api/cargar_suplidores','ControllerFinanciero@suplidores');
	Route::post('/api/actualizar_suplidor','ControllerFinanciero@actualizar_suplidor');
	Route::post('/api/registrar_suplidor','ControllerFinanciero@registrar_suplidor');
	Route::get('/api/eliminar_suplidor/{id}','ControllerFinanciero@eliminar_suplidor');


	//Nominas
	Route::get('/api/cargar_nomina/{fecha_i}/{fecha_f}','ControllerFinanciero@cargar_nomina');

    //Usuario

	Route::post('/api/agregar_usuario','ControllerUsuario@agregar_usuario');
	Route::post('/api/eliminar_usuario','ControllerUsuario@eliminar_usuario');
    Route::post('/api/actualizar_usuario','ControllerUsuario@actualizar_usuario');
	Route::get('/api/buscar_usuario/{usuario}','ControllerUsuario@buscar_usuario');
	Route::get('/api/cantidad_de_usuario','ControllerUsuario@cantidad_usuario');
	Route::get('/api/cargar_usuarios','ControllerUsuario@cargar_usuarios');
	Route::get('/api/cargar_usuario/{id_usuario}','ControllerUsuario@cargar_usuario');


    //Agregar Notas a pacientes

	Route::post('/api/crear_nota','ControllerNotas@agregar_nota');
	Route::get('/api/cargar_notas/{id_nota}','ControllerNotas@cargar_notas');
	Route::post('/api/actualizar_nota','ControllerNotas@actualizar_nota');
	Route::post('/api/eliminar_nota','ControllerNotas@eliminar_nota');
	Route::get('/api/capturar_notas','ControllerNotas@capturar_notas');
	Route::get('/api/ver_nota','ControllerNotas@ver_notas');

    //Agregar documentos
	Route::post('/api/subir_radiografia','ControllerRadiografia@subir_documento');
	Route::post('/api/eliminar_radiografia','ControllerRadiografia@eliminar_radiografia');
	Route::get('/api/cargar_documentos/{id}','ControllerRadiografia@cargar_documentos');
	#Route::get('/api/buscar_documentos','ControllerRadiografia@buscar_documentos');

	//Presupuestos

	Route::post('/api/crear_presupuesto','ControllerPresupuesto@create');
	Route::get('/api/cargar_presupuestos/{paciente_id}','ControllerPresupuesto@cargar_presupuestos');
	Route::get('/api/cargar_presupuesto/{id_presupuesto}','ControllerPresupuesto@cargar_presupuesto');
	Route::post('/api/eliminar_presupuesto','ControllerPresupuesto@eliminar_prespuesto');
	Route::post('/api/actualizar_presupuesto','ControllerPresupuesto@actualizar_presupuesto');
	Route::get('/api/buscar_presupuesto/{buscar}','ControllerPresupuesto@buscar_presupuesto');
	Route::get('/api/buscar_presupuesto/{buscar}','ControllerPresupuesto@buscar_presupuesto');
	Route::post('/api/enviar_presupuesto','ControllerPresupuesto@enviarPresupuesto');

	//Configuracion

	Route::get('api/configs', [ConfigController::class, 'index']);              // Obtener todos
	Route::get('api/configs/{id}', [ConfigController::class, 'show']);          // Obtener uno por ID
	Route::post('api/configs', [ConfigController::class, 'store']);             // Crear nuevo
	Route::post('api/configs/{id}', [ConfigController::class, 'update']);       // Actualizar (POST o PUT)
	Route::delete('api/configs/{id}', [ConfigController::class, 'destroy']);    // Eliminar
	 

	 //Ficha Medica
	 Route::post('/api/guardar_ficha_medica','ControllerFichaMedica@store');
	 Route::get('/api/cargar_ficha_medica/{id_paciente}','ControllerFichaMedica@show');
	 Route::post('/api/actualizar_ficha_medica/{id}','ControllerFichaMedica@update');
	 Route::get('/api/eliminar_ficha_medica/{id}','ControllerFichaMedica@destroy');
	 Route::get('/api/buscar_fichas/{nombre}','ControllerFichaMedica@buscar_fichas');


	 Route::get('/api/probar_correo','ControllerRecibo@enviarReciboTest');


});