<?php

namespace App\Services\Dgii;

use App\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DgiiAuthService
{
    public function __construct(
        private DgiiXmlSignerService $signer,
        private DgiiCertificateService $certs
    ) {
    }

    public function ambiente(?Config $config = null): string
    {
        try {
            $config = $config ?: Config::first();
        } catch (\Throwable $e) {
            $config = null;
        }
        $amb = strtolower((string) ($config->dgii_ambiente ?? config('dgii.ambiente', 'testecf')));
        if (!in_array($amb, ['testecf', 'certecf', 'ecf'], true)) {
            $amb = 'testecf';
        }
        return $amb;
    }

    public function base(string $key, ?Config $config = null): string
    {
        $amb = $this->ambiente($config);
        $bases = config('dgii.bases.' . $amb, []);
        if (empty($bases[$key])) {
            throw new RuntimeException("URL DGII no configurada: {$amb}/{$key}");
        }
        return rtrim($bases[$key], '/');
    }

    public function token(?Config $config = null, bool $force = false): string
    {
        try {
            $config = $config ?: Config::first();
        } catch (\Throwable $e) {
            $config = null;
        }
        $amb = $this->ambiente($config);
        $cacheKey = 'dgii_token_' . $amb . '_' . md5($this->certs->certPath());

        if (!$force) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        $semillaUrl = $this->base('auth', $config) . '/api/autenticacion/semilla';
        $semillaResp = Http::timeout(45)
            ->withHeaders(['Accept' => '*/*'])
            ->get($semillaUrl);

        if (!$semillaResp->successful()) {
            throw new RuntimeException('DGII semilla falló (' . $semillaResp->status() . '): ' . $semillaResp->body());
        }

        $semillaXml = $semillaResp->body();
        if (stripos($semillaXml, '<Semilla') === false && stripos($semillaXml, 'valor') === false) {
            throw new RuntimeException('Respuesta de semilla DGII inválida.');
        }

        $firmada = $this->signer->sign($semillaXml, $config);

        $tmp = tempnam(sys_get_temp_dir(), 'dgii_semilla_');
        file_put_contents($tmp, $firmada);

        try {
            $validarUrl = $this->base('auth', $config) . '/api/autenticacion/validarsemilla';
            $tokenResp = Http::timeout(45)
                ->attach('xml', file_get_contents($tmp), 'semilla.xml')
                ->post($validarUrl);
        } finally {
            @unlink($tmp);
        }

        if (!$tokenResp->successful()) {
            throw new RuntimeException('DGII validarsemilla falló (' . $tokenResp->status() . '): ' . $tokenResp->body());
        }

        $json = $tokenResp->json();
        $token = $json['token'] ?? $json['Token'] ?? null;
        if (!$token) {
            throw new RuntimeException('DGII no devolvió token: ' . $tokenResp->body());
        }

        // Token DGII suele durar ~1 hora; cacheamos 50 min
        Cache::put($cacheKey, $token, now()->addMinutes(50));

        return $token;
    }
}
