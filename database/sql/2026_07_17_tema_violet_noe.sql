-- Tema violeta por defecto para NOE ODONTO ESTETIC
-- Ejecutar en la BD del tenant de NOE (felix / noeodontoestetic)

UPDATE `configs`
SET `tema_apariencia` = 'violet'
WHERE `tema_apariencia` IS NULL
   OR TRIM(`tema_apariencia`) = ''
   OR `tema_apariencia` = 'classic'
   OR LOWER(COALESCE(`nombre_clinica`, '')) LIKE '%noe%'
   OR LOWER(COALESCE(`nombre`, '')) LIKE '%noe%';

SELECT id, nombre, nombre_clinica, tema_apariencia FROM `configs`;
