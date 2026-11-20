<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Ejecuta el smoke de módulos vía scripts/smoke_modules.php.
 * En CI/local: definir SMOKE_HOST (y opcionalmente SMOKE_BASE).
 * En deploy: scripts/deploy_after_pull.sh lo invoca directamente.
 */
class ModulesSmokeTest extends TestCase
{
    public function test_all_backend_modules_smoke()
    {
        $root = dirname(dirname(__DIR__));
        $script = $root . '/scripts/smoke_modules.php';
        $this->assertTrue(is_file($script), 'Falta scripts/smoke_modules.php');

        if (!getenv('SMOKE_HOST') && !getenv('SMOKE_TENANT_HOST')) {
            $this->markTestSkipped('Defina SMOKE_HOST para correr el smoke de módulos (ej. felix.noeodontoestetic.com).');
        }

        $php = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php';
        $cmd = escapeshellarg($php) . ' ' . escapeshellarg($script) . ' 2>&1';
        $output = array();
        $code = 0;
        exec($cmd, $output, $code);
        $text = implode("\n", $output);

        if ($code !== 0) {
            $this->fail("Smoke modules falló (exit {$code}):\n" . $text);
        }

        $this->assertTrue(strpos($text, 'TOTAL ok=') !== false, $text);
    }
}
