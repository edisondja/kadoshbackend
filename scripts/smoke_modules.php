#!/usr/bin/env php
<?php
/**
 * Smoke tests de todos los módulos del backend.
 *
 * Uso:
 *   php scripts/smoke_modules.php
 *   SMOKE_HOST=felix.noeodontoestetic.com SMOKE_BASE=http://127.0.0.1 php scripts/smoke_modules.php
 *
 * Variables:
 *   SMOKE_BASE   Base URL (default http://127.0.0.1)
 *   SMOKE_HOST   Host header multi-tenant (obligatorio en prod)
 *   SMOKE_USER_ID  id JWT (default 1)
 *   SMOKE_TIMEOUT  segundos por request (default 20)
 *   SMOKE_STRICT   1 = fallar también en endpoints optional
 *
 * Exit code: 0 = OK, 1 = fallos.
 */

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
require $root . '/tests/Smoke/ModuleCatalog.php';

use Tests\Smoke\ModuleCatalog;
use Firebase\JWT\JWT;

function smoke_env($key, $default = null)
{
    $v = getenv($key);
    if ($v === false || $v === '') {
        return $default;
    }
    return $v;
}

function load_dotenv($root)
{
    $path = $root . '/.env';
    if (!is_file($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\"'");
        if ($k !== '' && getenv($k) === false) {
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
        }
    }
}

load_dotenv($root);

$base = rtrim(smoke_env('SMOKE_BASE', 'http://127.0.0.1'), '/');
$host = smoke_env('SMOKE_HOST', smoke_env('SMOKE_TENANT_HOST', ''));
$userId = (int) smoke_env('SMOKE_USER_ID', 1);
$timeout = (int) smoke_env('SMOKE_TIMEOUT', 20);
$strict = smoke_env('SMOKE_STRICT', '0') === '1';
$firma = smoke_env('FIRMA_TOKEN', '');

if ($firma === '') {
    fwrite(STDERR, "ERROR: FIRMA_TOKEN no definido (revise .env)\n");
    exit(1);
}

if ($host === '') {
    // Intentar primer tenant activo
    try {
        $app = require $root . '/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $row = Illuminate\Support\Facades\DB::connection('mysql')
            ->table('tenants')
            ->where('activo', 1)
            ->orderBy('id')
            ->first();
        if ($row) {
            $host = !empty($row->dominio) ? $row->dominio : ($row->subdominio . '.localhost');
            // Preferir hosts conocidos en prod
            if (!empty($row->api_url) && preg_match('#https?://([^/]+)#', $row->api_url, $m)) {
                $host = $m[1];
            }
        }
    } catch (Throwable $e) {
        // ignore
    }
}

if ($host === '') {
    fwrite(STDERR, "ERROR: defina SMOKE_HOST (ej. felix.noeodontoestetic.com)\n");
    exit(1);
}

$token = JWT::encode(
    array('id' => $userId, 'iat' => time(), 'exp' => time() + 3600),
    $firma,
    'HS256'
);

$ids = array(
    'paciente' => null,
    'doctor' => null,
    'factura' => null,
    'recibo' => null,
    'procedimiento' => null,
    'presupuesto' => null,
    'usuario' => null,
    'gasto' => null,
    'suplidor' => null,
    'producto' => null,
    'especialidad' => null,
    'odontograma' => null,
    'receta' => null,
);

/**
 * @return array{code:int,body:string,error:?string}
 */
function smoke_request($base, $host, $token, $method, $path, $timeout)
{
    $url = $base . $path;
    $ch = curl_init($url);
    $headers = array(
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
        'Host: ' . $host,
    );
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => false,
    ));
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false) {
        return array('code' => 0, 'body' => '', 'error' => $err ?: 'curl failed');
    }
    return array('code' => $code, 'body' => (string) $body, 'error' => null);
}

function smoke_extract_id($json, $preferredKeys = null)
{
    $keys = $preferredKeys ?: array('id', 'id_paciente', 'id_factura', 'id_recibo', 'id_doctor', 'id_usuario');
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return null;
    }
    // Lista
    if (isset($data[0]) && is_array($data[0])) {
        foreach ($keys as $k) {
            if (isset($data[0][$k]) && is_numeric($data[0][$k])) {
                return (int) $data[0][$k];
            }
        }
        if (isset($data[0]['id'])) {
            return (int) $data[0]['id'];
        }
    }
    // Objeto con data/items
    foreach (array('data', 'items', 'rows', 'pacientes', 'facturas', 'doctores') as $wrap) {
        if (isset($data[$wrap]) && is_array($data[$wrap]) && isset($data[$wrap][0])) {
            $row = $data[$wrap][0];
            if (is_array($row) && isset($row['id'])) {
                return (int) $row['id'];
            }
        }
    }
    if (isset($data['id']) && is_numeric($data['id'])) {
        return (int) $data['id'];
    }
    return null;
}

function smoke_resolve_path($path, $ids)
{
    return preg_replace_callback('/\{([a-z_]+)\}/', function ($m) use ($ids) {
        $key = $m[1];
        if (!isset($ids[$key]) || $ids[$key] === null) {
            return $m[0]; // leave unresolved
        }
        return (string) $ids[$key];
    }, $path);
}

$cases = ModuleCatalog::cases();
$passed = 0;
$failed = 0;
$skipped = 0;
$results = array();
$byModule = array();

echo "Kadosh smoke modules\n";
echo "base={$base} host={$host} user={$userId}\n";
echo str_repeat('-', 72) . "\n";

foreach ($cases as $case) {
    $module = $case['module'];
    $method = isset($case['method']) ? $case['method'] : 'GET';
    $pathTpl = $case['path'];
    $accept = isset($case['accept']) ? $case['accept'] : array(200);
    $optional = !empty($case['optional']);
    $needs = isset($case['needs']) ? $case['needs'] : array();
    $capture = isset($case['capture']) ? $case['capture'] : null;

    if (!isset($byModule[$module])) {
        $byModule[$module] = array('ok' => 0, 'fail' => 0, 'skip' => 0);
    }

    $missing = array();
    foreach ($needs as $need) {
        if (!isset($ids[$need]) || $ids[$need] === null) {
            $missing[] = $need;
        }
    }
    if ($missing) {
        $skipped++;
        $byModule[$module]['skip']++;
        $results[] = array('module' => $module, 'path' => $pathTpl, 'status' => 'SKIP', 'reason' => 'missing ' . implode(',', $missing));
        echo sprintf("SKIP  %-16s %s (faltan: %s)\n", $module, $pathTpl, implode(',', $missing));
        continue;
    }

    $path = smoke_resolve_path($pathTpl, $ids);
    if (strpos($path, '{') !== false) {
        $skipped++;
        $byModule[$module]['skip']++;
        echo sprintf("SKIP  %-16s %s (path sin resolver)\n", $module, $pathTpl);
        continue;
    }

    $res = smoke_request($base, $host, $token, $method, $path, $timeout);
    $code = $res['code'];
    $ok = in_array($code, $accept, true);

    // 500 siempre es fallo duro (salvo optional + no strict)
    $hardFail = ($code >= 500 || $code === 0);
    if ($hardFail && $optional && !$strict) {
        $ok = false; // still count as fail soft? treat as WARN skip
        $skipped++;
        $byModule[$module]['skip']++;
        $snippet = substr(preg_replace('/\s+/', ' ', $res['body']), 0, 120);
        echo sprintf("WARN  %-16s %s -> %s %s\n", $module, $path, $code ?: 'ERR', $snippet);
        $results[] = array('module' => $module, 'path' => $path, 'status' => 'WARN', 'code' => $code, 'body' => $snippet);
        continue;
    }

    if ($ok) {
        $passed++;
        $byModule[$module]['ok']++;
        if ($capture && isset($ids[$capture]) && $ids[$capture] === null) {
            $extracted = smoke_extract_id($res['body']);
            if ($extracted) {
                $ids[$capture] = $extracted;
            }
        }
        echo sprintf("OK    %-16s %s -> %d\n", $module, $path, $code);
        $results[] = array('module' => $module, 'path' => $path, 'status' => 'OK', 'code' => $code);
    } else {
        if ($optional && !$strict && ($code === 404 || $code === 422 || $code === 403)) {
            $skipped++;
            $byModule[$module]['skip']++;
            echo sprintf("SKIP  %-16s %s -> %d (optional)\n", $module, $path, $code);
            $results[] = array('module' => $module, 'path' => $path, 'status' => 'SKIP', 'code' => $code);
            continue;
        }
        $failed++;
        $byModule[$module]['fail']++;
        $snippet = substr(preg_replace('/\s+/', ' ', $res['error'] ?: $res['body']), 0, 160);
        echo sprintf("FAIL  %-16s %s -> %d %s\n", $module, $path, $code, $snippet);
        $results[] = array('module' => $module, 'path' => $path, 'status' => 'FAIL', 'code' => $code, 'body' => $snippet);
    }
}

echo str_repeat('-', 72) . "\n";
echo "IDs: " . json_encode(array_filter($ids)) . "\n";
echo "Resumen por módulo:\n";
ksort($byModule);
foreach ($byModule as $mod => $st) {
    echo sprintf("  %-18s ok=%d fail=%d skip=%d\n", $mod, $st['ok'], $st['fail'], $st['skip']);
}
echo str_repeat('-', 72) . "\n";
echo sprintf("TOTAL ok=%d fail=%d skip=%d\n", $passed, $failed, $skipped);

$reportDir = $root . '/storage/logs';
if (!is_dir($reportDir)) {
    @mkdir($reportDir, 0775, true);
}
$reportFile = $reportDir . '/smoke_modules_' . date('Ymd_His') . '.json';
@file_put_contents($reportFile, json_encode(array(
    'base' => $base,
    'host' => $host,
    'passed' => $passed,
    'failed' => $failed,
    'skipped' => $skipped,
    'ids' => $ids,
    'modules' => $byModule,
    'results' => $results,
    'at' => date('c'),
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Reporte: {$reportFile}\n";

exit($failed > 0 ? 1 : 0);
