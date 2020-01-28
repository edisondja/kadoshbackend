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
	Route::post('/api/login','LoginController@Login');
	Route::get('/api/actualizar_usuario/{usuario}/{clave}/{nombre}/{apellido}/{id}','LoginContorller@AactualizarUsuario');
	Route::get('/api/eliminar_usuario/{id}','LoginContorller@AactualizarUsuario');
	Route::get('/api/consultar_usuarios','LoginController@CargarUsuarios');

	//API PACIENTE
	Route::get('/api/paciente','Paciente@index');
	Route::get('/api/paciente/{id_paciente}','Paciente@show');
	Route::get('/api/borrar_paciente/{id_paciente}','Paciente@destroy');
	Route::get('/api/guardar_paciente/{nombre}/{apellido}/{telefono}/{id_doctor}/{cedula}/{fecha_nacimiento}/{sexo}','Paciente@guardar');
	Route::get('/api/notificar_cumple','Paciente@notificar_cumple');
	Route::get('/api/actualizar_paciente/{nombre}/{apellido}/{cedula}/{telefono}/{sexo}/{fecha_nacimiento}/{id_doctor}/{id}','Paciente@update');
	Route::get('/api/buscar_paciente/{nombre}','Paciente@buscando_paciente');
	Route::get('/api/consultar_deuda/{id_paciente}','Paciente@deuda_paciente');

	///API DOCTORES
	Route::get('/api/doctores/','ControllerDoctor@index');
	Route::get('/api/eliminar_doctor/{id}','ControllerDoctor@destroy');
	Route::get('/api/actualizar_doctor/{nombre}/{apellido}/{cedula}/{telefono}/{id}','ControllerDoctor@edit');
	Route::get('/api/cargar_doctor/{id_doctor}','ControllerDoctor@cargar_doctor');
	Route::get('/api/crear_doctor/{nombre}/{apellido}/{cedula}/{telefono}','ControllerDoctor@create');
	Route::get('/api/buscando_doctor/{nombre}','ControllerDoctor@buscando_doctor');


	///API CITAS
	Route::get('api/cargar_citas','ControllerCita@index');
	Route::get('api/cargar_citas_de_paciente/{id}','ControllerCita@citas_paciente');
	Route::get('api/guardar_cita/{id_paciente}/{hora}/{dia}/','ControllerCita@create');
	Route::get('api/cargar_cita/{id}','ControllerCita@show');
	Route::get('api/actualizar_cita/{id_cita}/{hora}/{dia}','ControllerCita@update');
	Route::get('api/eliminar_cita/{id_cita}','ControllerCita@destroy');
	Route::get('api/buscar_cita/{fecha}','ControllerCita@BuscarCita');
	Route::get('/api/cargar_citas','ControllerCita@cargar_citas');


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
	Route::get('/api/descontar_precio_factura/{id_factura}/{monto}','ControllerFactura@descontar_estatus');


	///PROCEDIMIENTOS
	Route::get('/api/cargar_procedimientos','ControllerProcedimiento@index');
	Route::get('/api/cargar_procedimiento/{id}','ControllerProcedimiento@show');
	Route::get('/api/guardar_procedimiento/{nombre}/{precio}','ControllerProcedimiento@create');
	Route::get('/api/actualizar_procedimiento/{nombre}/{precio}/{id}','ControllerProcedimiento@update');
	Route::get('/api/eliminar_procedimiento/{id}','ControllerProcedimiento@destroy');
	Route::get('/api/buscar_procedimiento/{buscar}','ControllerProcedimiento@buscarProcedimiento');

	
	//RECIBOS
	Route::get('/api/pagar_recibo/{id_factura}/{monto}/{tipo_de_pago}/{estado_actual}/{codigo}','ControllerRecibo@pagar_recibo');
	Route::get('/api/actualizar_recibo/{id_recibo}','ControllerRecibo@actualizar_recibo');
	Route::get('/api/eliminar_recibo/{id_recibo}/{id_facutara}','ControllerRecibo@eliminar_recibo');
	Route::get('/api/cargar_recibos/{id_factura}','ControllerRecibo@cargar_recibos');
	Route::get('/api/cargar_recibo/{id_recibo}','ControllerRecibo@cargar_recibo');
	Route::get('/api/imprimir_recibo/{id_recibo}/{id_factura}','ControllerRecibo@imprimir_recibo');
	Route::get('/api/ingresos_de_mes/','ControllerRecibo@ingresos_en_meses');
	Route::get('/api/ingresos_de_semana/{fecha}','ControllerRecibo@ingresos_de_semana');


	//CARGAR HISTORIAL_PS

	Route::get('/api/cargar_historial_ps/{id_factura}','ControllerHistorialps@cargar_procedimientos');
	Route::get('/api/eliminar_historial_ps/{id_factura}','ControllerHistorialps@eliminar_procedimiento');
	//Route::get('/api/editar_historial_ps/{id_factura}','Controller');

	//USUARIOS
	Route::get('/api/login/{usuario}/{clave}','ControllerUsuario@login');

	///REPORTES
	Route::get('/api/facturas_reportes/{fecha_inicial}/{fecha_final}','ControllerRecibo@reporte_recibos');