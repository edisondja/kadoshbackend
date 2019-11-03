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
	Route::get('/api/paciente/{id}','Paciente@show');
	Route::get('/api/borrar_paciente/{id}','Paciente@destroy');
	Route::get('/api/guardar_paciente/{nombre}/{apellido}/{telefono}/{id_doctor}/{cedula}/{fecha_nacimiento}','Paciente@guardar');

	Route::get('api/actualizar_paciente/{nombre}/{apellido}/{telefono}/{id_doctor}/{id_paciente}/{cedula}/{fecha_nacimiento}/{id}','Paciente@update');

	Route::get('api/buscar_paciente/{nombre}','Paciente@buscando_paciente');

	///API DOCTORES
	Route::get('/api/doctores/','ControllerDoctor@index');
	Route::get('/api/eliminar_doctor/{id}','ControllerDoctor@destroy');
	Route::get('/api/actualizar_doctor/{nombre}/{apellido}/{cedula}/{telefono}/{id}','ControllerDoctor@edit');
	Route::get('api/cargar_doctor/{id_doctor}','ControllerDoctor@cargar_doctor');
	Route::get('/api/crear_doctor/{nombre}/{apellido}/{cedula}/{telefono}','ControllerDoctor@create');
	Route::get('api/buscando_doctor/{nombre}','ControllerDoctor@buscando_doctor');


	///API CITAS
	Route::get('api/cargar_citas','ControllerCita@index');
	Route::get('api/guardar_cita/{hora}/{dia}/{id_paciente}','ControllerCita@create');
	Route::get('api/actualizar_cita','ControllerCita@update');
	Route::get('api/eliminar_cita/{id_cita}','ControllerCita@destroy');
	Route::get('api/buscar_cita/{fecha}','ControllerCita@BuscarCita');


	///API FACTURAS
	Route::post('/api/crear_factura','ControllerFactura@create');
	Route::get('/api/buscar_facutura/{id_factura}','ControllerFactura@buscar_factura');
	Route::get('/api/eliminar_factura/{id_factura','ControllerFactura@eliminar_factura');
	Route::get('/api/editar_factura/{id_factura}','ControllerFactura@edit');
	Route::post('/api/editando_factura/','ControllerFactura@update');

	///PROCEDIMIENTOS
	Route::get('/api/cargar_procedimientos','ControllerProcedimiento@index');
	Route::get('/api/cargar_procedimiento/{id}','ControllerProcedimiento@show');
	Route::get('/api/guardar_procedimiento/{nombre}/{precio}','ControllerProcedimiento@create');
	Route::get('/api/actualizar_procedimiento/{nombre}/{precio}/{id}','ControllerProcedimiento@update');
	Route::get('/api/eliminar_procedimiento/{id}','ControllerProcedimiento@destroy');
	Route::get('/api/buscar_procedimiento/{buscar}','ControllerProcedimiento@buscarProcedimiento');

	///REPORTES