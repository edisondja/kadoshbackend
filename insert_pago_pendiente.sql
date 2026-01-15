-- ============================================
-- INSERT para simular un pago mensual pendiente próximo a vencer
-- ============================================
-- IMPORTANTE: Cambia el usuario_id (1) por tu ID de usuario real
-- Para obtener tu usuario_id, ejecuta:
-- SELECT id, usuario, nombre, apellido FROM usuarios;

-- Ejemplo 1: Pago que vence en 2 días (dentro del período de gracia de 3 días)
-- Este activará la alerta porque quedan 2 días (menos de 3 días de gracia)
INSERT INTO `pagos_mensuales` (
    `usuario_id`, 
    `fecha_pago`, 
    `fecha_vencimiento`, 
    `monto`, 
    `estado`, 
    `dias_gracia`, 
    `comentarios`,
    `created_at`, 
    `updated_at`
) VALUES (
    1,  -- ⚠️ CAMBIA ESTE ID por tu usuario_id real
    DATE_SUB(CURDATE(), INTERVAL 28 DAY),  -- Fecha de pago hace 28 días
    DATE_ADD(CURDATE(), INTERVAL 2 DAY),  -- Vence en 2 días (dentro del período de gracia)
    50.00,  -- Monto del pago
    'pendiente',  -- Estado pendiente
    3,  -- 3 días de gracia
    'Pago mensual de suscripción - Prueba de alerta',
    NOW(),
    NOW()
);

-- Ejemplo 2: Pago que vence mañana (dentro del período de gracia)
INSERT INTO `pagos_mensuales` (
    `usuario_id`, 
    `fecha_pago`, 
    `fecha_vencimiento`, 
    `monto`, 
    `estado`, 
    `dias_gracia`, 
    `comentarios`,
    `created_at`, 
    `updated_at`
) VALUES (
    1,  -- ⚠️ CAMBIA ESTE ID por tu usuario_id real
    DATE_SUB(CURDATE(), INTERVAL 29 DAY),
    DATE_ADD(CURDATE(), INTERVAL 1 DAY),  -- Vence mañana
    50.00,
    'pendiente',
    3,
    'Pago mensual - vence mañana',
    NOW(),
    NOW()
);

-- Ejemplo 3: Pago que vence hoy (dentro del período de gracia)
INSERT INTO `pagos_mensuales` (
    `usuario_id`, 
    `fecha_pago`, 
    `fecha_vencimiento`, 
    `monto`, 
    `estado`, 
    `dias_gracia`, 
    `comentarios`,
    `created_at`, 
    `updated_at`
) VALUES (
    1,  -- ⚠️ CAMBIA ESTE ID por tu usuario_id real
    DATE_SUB(CURDATE(), INTERVAL 30 DAY),
    CURDATE(),  -- Vence hoy
    50.00,
    'pendiente',
    3,
    'Pago mensual - vence hoy - URGENTE',
    NOW(),
    NOW()
);

-- ============================================
-- CONSULTA PARA VERIFICAR TU USUARIO_ID:
-- ============================================
-- SELECT id, usuario, nombre, apellido FROM usuarios;
--
-- Luego reemplaza el valor 1 en los INSERT anteriores por tu usuario_id
