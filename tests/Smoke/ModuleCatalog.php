<?php

namespace Tests\Smoke;

/**
 * Catálogo de endpoints smoke por módulo.
 * Solo lecturas / comprobaciones seguras (no mutan datos de producción).
 *
 * Cada caso:
 *  - module: nombre del módulo
 *  - method: GET|POST|...
 *  - path: ruta (puede usar {placeholders})
 *  - accept: códigos HTTP aceptados (además de 200)
 *  - needs: placeholders requeridos (paciente, factura, recibo, doctor, usuario, presupuesto, nota, documento, gasto, suplidor, producto, especialidad, odontograma, receta)
 *  - optional: si true, 404/422 no fallan el deploy
 */
class ModuleCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function cases()
    {
        return array_merge(
            self::core(),
            self::pacientes(),
            self::doctores(),
            self::citas(),
            self::facturas(),
            self::recibos(),
            self::procedimientos(),
            self::documentos(),
            self::presupuestos(),
            self::config(),
            self::usuarios(),
            self::sesiones(),
            self::notas(),
            self::ficha(),
            self::odontogramas(),
            self::recetas(),
            self::financiero(),
            self::nomina(),
            self::puntoVenta(),
            self::mobile(),
            self::dgii(),
            self::misc()
        );
    }

    private static function core()
    {
        return [
            ['module' => 'health', 'method' => 'GET', 'path' => '/test', 'accept' => [200]],
            ['module' => 'health', 'method' => 'GET', 'path' => '/api/mobile/ping', 'accept' => [200]],
            ['module' => 'almacenamiento', 'method' => 'GET', 'path' => '/api/almacenamiento', 'accept' => [200]],
            ['module' => 'landing', 'method' => 'GET', 'path' => '/api/landing/captcha', 'accept' => [200], 'optional' => true],
        ];
    }

    private static function pacientes()
    {
        return [
            ['module' => 'pacientes', 'method' => 'GET', 'path' => '/api/paciente', 'accept' => [200], 'capture' => 'paciente'],
            ['module' => 'pacientes', 'method' => 'GET', 'path' => '/api/cantidad_de_pacientes', 'accept' => [200]],
            ['module' => 'pacientes', 'method' => 'GET', 'path' => '/api/cargar_generos_pacientes', 'accept' => [200]],
            ['module' => 'pacientes', 'method' => 'GET', 'path' => '/api/paciente/{paciente}', 'accept' => [200], 'needs' => ['paciente'], 'optional' => true],
            ['module' => 'pacientes', 'method' => 'GET', 'path' => '/api/consultar_deuda/{paciente}', 'accept' => [200], 'needs' => ['paciente'], 'optional' => true],
            ['module' => 'pacientes', 'method' => 'GET', 'path' => '/api/deudas_por_fecha', 'accept' => [200]],
            ['module' => 'pacientes', 'method' => 'GET', 'path' => '/api/buscar_paciente/a', 'accept' => [200]],
        ];
    }

    private static function doctores()
    {
        return [
            ['module' => 'doctores', 'method' => 'GET', 'path' => '/api/doctores', 'accept' => [200], 'capture' => 'doctor'],
            ['module' => 'doctores', 'method' => 'GET', 'path' => '/api/doctores_todos/', 'accept' => [200]],
            ['module' => 'doctores', 'method' => 'GET', 'path' => '/api/cargar_doctor/{doctor}', 'accept' => [200], 'needs' => ['doctor'], 'optional' => true],
            ['module' => 'doctores', 'method' => 'GET', 'path' => '/api/buscando_doctor/a', 'accept' => [200]],
            ['module' => 'especialidades', 'method' => 'GET', 'path' => '/api/listar_especialidades', 'accept' => [200], 'capture' => 'especialidad'],
            ['module' => 'especialidades', 'method' => 'GET', 'path' => '/api/listar_todas_especialidades', 'accept' => [200]],
        ];
    }

    private static function citas()
    {
        return [
            ['module' => 'citas', 'method' => 'GET', 'path' => '/api/cargar_citas', 'accept' => [200]],
            ['module' => 'citas', 'method' => 'GET', 'path' => '/api/citas', 'accept' => [200]],
            ['module' => 'citas', 'method' => 'GET', 'path' => '/api/citas_doctor/{doctor}', 'accept' => [200], 'needs' => ['doctor'], 'optional' => true],
            ['module' => 'citas', 'method' => 'GET', 'path' => '/api/cargar_citas_de_paciente/{paciente}', 'accept' => [200], 'needs' => ['paciente'], 'optional' => true],
        ];
    }

    private static function facturas()
    {
        return [
            ['module' => 'facturas', 'method' => 'GET', 'path' => '/api/cargar_facturas', 'accept' => [200], 'capture' => 'factura'],
            ['module' => 'facturas', 'method' => 'GET', 'path' => '/api/cargar_factura/{factura}', 'accept' => [200], 'needs' => ['factura'], 'optional' => true],
            ['module' => 'facturas', 'method' => 'GET', 'path' => '/api/cargar_facturas_paciente/{paciente}', 'accept' => [200], 'needs' => ['paciente'], 'optional' => true],
            ['module' => 'facturas', 'method' => 'GET', 'path' => '/api/cargar_procedimientos_de_factura/{factura}', 'accept' => [200], 'needs' => ['factura'], 'optional' => true],
            ['module' => 'historial_ps', 'method' => 'GET', 'path' => '/api/cargar_historial_ps/{factura}', 'accept' => [200], 'needs' => ['factura'], 'optional' => true],
        ];
    }

    private static function recibos()
    {
        return [
            ['module' => 'recibos', 'method' => 'GET', 'path' => '/api/cargar_recibos/{factura}', 'accept' => [200], 'needs' => ['factura'], 'capture' => 'recibo', 'optional' => true],
            ['module' => 'recibos', 'method' => 'GET', 'path' => '/api/cargar_recibo/{recibo}', 'accept' => [200], 'needs' => ['recibo'], 'optional' => true],
            ['module' => 'recibos', 'method' => 'GET', 'path' => '/api/imprimir_recibo/{recibo}/{factura}', 'accept' => [200], 'needs' => ['recibo', 'factura'], 'optional' => true],
            ['module' => 'recibos', 'method' => 'GET', 'path' => '/api/ingresos_de_mes', 'accept' => [200, 301, 302]],
            ['module' => 'recibos', 'method' => 'GET', 'path' => '/api/ingresos_de_semana/' . date('Y-m-d'), 'accept' => [200]],
        ];
    }

    private static function procedimientos()
    {
        return [
            ['module' => 'procedimientos', 'method' => 'GET', 'path' => '/api/cargar_procedimientos', 'accept' => [200], 'capture' => 'procedimiento'],
            ['module' => 'procedimientos', 'method' => 'GET', 'path' => '/api/cargar_procedimiento/{procedimiento}', 'accept' => [200], 'needs' => ['procedimiento'], 'optional' => true],
            ['module' => 'procedimientos', 'method' => 'GET', 'path' => '/api/buscar_procedimiento/a', 'accept' => [200]],
            ['module' => 'financiero', 'method' => 'GET', 'path' => '/api/procedimientos_realizados', 'accept' => [200]],
        ];
    }

    private static function documentos()
    {
        return [
            ['module' => 'documentos', 'method' => 'GET', 'path' => '/api/cargar_documentos/{paciente}', 'accept' => [200], 'needs' => ['paciente'], 'optional' => true],
        ];
    }

    private static function presupuestos()
    {
        return [
            ['module' => 'presupuestos', 'method' => 'GET', 'path' => '/api/listar_todos_presupuestos', 'accept' => [200], 'capture' => 'presupuesto'],
            ['module' => 'presupuestos', 'method' => 'GET', 'path' => '/api/listar_presupuestos_consulta', 'accept' => [200]],
            ['module' => 'presupuestos', 'method' => 'GET', 'path' => '/api/cargar_presupuestos/{paciente}', 'accept' => [200], 'needs' => ['paciente'], 'optional' => true],
            ['module' => 'presupuestos', 'method' => 'GET', 'path' => '/api/cargar_presupuesto/{presupuesto}', 'accept' => [200], 'needs' => ['presupuesto'], 'optional' => true],
            ['module' => 'presupuestos', 'method' => 'GET', 'path' => '/api/buscar_presupuesto/a', 'accept' => [200]],
        ];
    }

    private static function config()
    {
        return [
            ['module' => 'config', 'method' => 'GET', 'path' => '/api/configs', 'accept' => [200]],
            ['module' => 'config', 'method' => 'GET', 'path' => '/api/configs/1', 'accept' => [200, 404]],
        ];
    }

    private static function usuarios()
    {
        return [
            ['module' => 'usuarios', 'method' => 'GET', 'path' => '/api/cargar_usuarios', 'accept' => [200], 'capture' => 'usuario'],
            ['module' => 'usuarios', 'method' => 'GET', 'path' => '/api/cantidad_de_usuario', 'accept' => [200]],
            ['module' => 'usuarios', 'method' => 'GET', 'path' => '/api/listar_roles', 'accept' => [200]],
            ['module' => 'usuarios', 'method' => 'GET', 'path' => '/api/cargar_usuario/{usuario}', 'accept' => [200], 'needs' => ['usuario'], 'optional' => true],
            ['module' => 'usuarios', 'method' => 'GET', 'path' => '/api/buscar_usuario/a', 'accept' => [200]],
        ];
    }

    private static function sesiones()
    {
        return [
            ['module' => 'sesiones', 'method' => 'GET', 'path' => '/api/sesiones/verificar', 'accept' => [200]],
            ['module' => 'sesiones', 'method' => 'GET', 'path' => '/api/sesiones', 'accept' => [200]],
        ];
    }

    private static function notas()
    {
        return [
            ['module' => 'notas', 'method' => 'GET', 'path' => '/api/cargar_notas/{paciente}', 'accept' => [200], 'needs' => ['paciente'], 'optional' => true],
            ['module' => 'notas', 'method' => 'GET', 'path' => '/api/capturar_notas', 'accept' => [200]],
        ];
    }

    private static function ficha()
    {
        return [
            ['module' => 'ficha_medica', 'method' => 'GET', 'path' => '/api/cargar_ficha_medica/{paciente}', 'accept' => [200, 404], 'needs' => ['paciente'], 'optional' => true],
            ['module' => 'ficha_medica', 'method' => 'GET', 'path' => '/api/buscar_fichas/a', 'accept' => [200]],
        ];
    }

    private static function odontogramas()
    {
        return [
            ['module' => 'odontogramas', 'method' => 'GET', 'path' => '/api/listar_odontogramas', 'accept' => [200], 'capture' => 'odontograma'],
            ['module' => 'odontogramas', 'method' => 'GET', 'path' => '/api/listar_odontogramas_paciente/{paciente}', 'accept' => [200], 'needs' => ['paciente'], 'optional' => true],
            ['module' => 'odontogramas', 'method' => 'GET', 'path' => '/api/ver_odontograma/{odontograma}', 'accept' => [200], 'needs' => ['odontograma'], 'optional' => true],
        ];
    }

    private static function recetas()
    {
        return [
            ['module' => 'recetas', 'method' => 'GET', 'path' => '/api/listar_recetas_paciente/{paciente}', 'accept' => [200], 'needs' => ['paciente'], 'capture' => 'receta', 'optional' => true],
            ['module' => 'recetas', 'method' => 'GET', 'path' => '/api/obtener_receta/{receta}', 'accept' => [200], 'needs' => ['receta'], 'optional' => true],
        ];
    }

    private static function financiero()
    {
        $hoy = date('Y-m-d');
        $hace30 = date('Y-m-d', strtotime('-30 days'));

        return [
            ['module' => 'gastos', 'method' => 'GET', 'path' => '/api/cargar_gastos', 'accept' => [200], 'capture' => 'gasto'],
            ['module' => 'gastos', 'method' => 'GET', 'path' => '/api/cargar_gasto/{gasto}', 'accept' => [200], 'needs' => ['gasto'], 'optional' => true],
            ['module' => 'gastos', 'method' => 'GET', 'path' => "/api/cargar_gastos_fecha/{$hace30}/{$hoy}", 'accept' => [200]],
            ['module' => 'suplidores', 'method' => 'GET', 'path' => '/api/cargar_suplidores', 'accept' => [200], 'capture' => 'suplidor'],
            ['module' => 'suplidores', 'method' => 'GET', 'path' => '/api/buscar_suplidor/a', 'accept' => [200]],
            ['module' => 'asientos', 'method' => 'GET', 'path' => '/api/asientos_contables', 'accept' => [200], 'optional' => true],
            ['module' => 'descuentos', 'method' => 'GET', 'path' => '/api/consultar_descuentos/{factura}', 'accept' => [200], 'needs' => ['factura'], 'optional' => true],
            ['module' => 'reportes', 'method' => 'GET', 'path' => "/api/facturas_reportes/{$hace30}/{$hoy}", 'accept' => [200]],
            ['module' => 'pagos_mensuales', 'method' => 'GET', 'path' => '/api/listar_pagos', 'accept' => [200]],
            ['module' => 'pagos_mensuales', 'method' => 'GET', 'path' => '/api/alertas_pagos', 'accept' => [200]],
            ['module' => 'auditoria', 'method' => 'GET', 'path' => '/api/logs_todos', 'accept' => [200]],
            ['module' => 'ganancias', 'method' => 'GET', 'path' => '/api/listar_recibos_ganancias', 'accept' => [200]],
            ['module' => 'ganancias', 'method' => 'GET', 'path' => '/api/listar_doctores_ganancias_resumen', 'accept' => [200]],
        ];
    }

    private static function nomina()
    {
        $hoy = date('Y-m-d');
        $hace30 = date('Y-m-d', strtotime('-30 days'));

        return [
            ['module' => 'nomina', 'method' => 'GET', 'path' => "/api/cargar_nomina/{$hace30}/{$hoy}", 'accept' => [200]],
            ['module' => 'nomina', 'method' => 'GET', 'path' => "/api/calcular_nomina_doctores/{$hace30}/{$hoy}", 'accept' => [200]],
            ['module' => 'nomina', 'method' => 'GET', 'path' => "/api/calcular_nomina_empleados/{$hace30}/{$hoy}", 'accept' => [200]],
            ['module' => 'nomina', 'method' => 'GET', 'path' => '/api/listar_pagos_nomina', 'accept' => [200]],
            ['module' => 'nomina', 'method' => 'GET', 'path' => '/api/listar_empleados_nomina', 'accept' => [200]],
            ['module' => 'nomina', 'method' => 'GET', 'path' => '/api/usuarios_disponibles_empleado', 'accept' => [200]],
            ['module' => 'salarios', 'method' => 'GET', 'path' => '/api/listar_salarios_doctores', 'accept' => [200]],
            ['module' => 'salarios', 'method' => 'GET', 'path' => '/api/doctores_con_salarios', 'accept' => [200]],
        ];
    }

    private static function puntoVenta()
    {
        return [
            ['module' => 'punto_venta', 'method' => 'GET', 'path' => '/api/listar_productos', 'accept' => [200], 'capture' => 'producto'],
            ['module' => 'punto_venta', 'method' => 'GET', 'path' => '/api/productos_stock_bajo', 'accept' => [200]],
        ];
    }

    private static function mobile()
    {
        return [
            ['module' => 'mobile', 'method' => 'GET', 'path' => '/api/mobile/dashboard', 'accept' => [200]],
            ['module' => 'mobile', 'method' => 'GET', 'path' => '/api/mobile/clinica', 'accept' => [200]],
        ];
    }

    private static function dgii()
    {
        return [
            ['module' => 'dgii', 'method' => 'GET', 'path' => '/api/dgii/estado', 'accept' => [200]],
            ['module' => 'dgii', 'method' => 'GET', 'path' => '/api/dgii/factura/{factura}', 'accept' => [200, 404], 'needs' => ['factura'], 'optional' => true],
        ];
    }

    private static function misc()
    {
        return [
            // Ruta pública: 404 esperado si no hay PDF; 200/302 si existe
            ['module' => 'recibos_publicos', 'method' => 'GET', 'path' => '/r/smoke-not-found.pdf', 'accept' => [404, 200], 'optional' => true],
        ];
    }
}
