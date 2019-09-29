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
	Route::get('/api/eliminar_doctor/{id}','ControllerDoctor@drop');
	Route::get('/api/actualizar_doctor/{nombre}/{apellido}/{cedula}/{telefono}/{id}','ControllerDoctor@edit');
	Route::get('api/cargar_doctor/{id_doctor}','ControllerDoctor@cargar_doctor');
	Route::get('/api/crear_doctor/{nombre}/{apellido}/{cedula}/{telefono}','ControllerDoctor@create');
	Route::get('api/buscando_doctor/{nombre}','ControllerDoctor@buscando_doctor');


	///API CITAS



	///API FACTURAS



	///PROCEDIMIENTOS
	Route::get('/api/cargar_procedimientos','ControllerProcedimiento@index');
	Route::get('/api/guardar_procedimiento/{nombre}/{precio}','ControllerProcedimiento@store');
	Route::get('/api/update/{nombre}/{precio}/{id}','ControllerProcedimiento@update');
	Route::get('/api/eliminar/{id}','ControllerProcedimiento@destroy');
	Route::get('/api/buscar_procedimiento/{buscar}','ControllerProcedimiento@buscarProcedimiento');




	///REPORTES