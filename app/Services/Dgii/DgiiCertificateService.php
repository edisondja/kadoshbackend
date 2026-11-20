<?php

namespace App\Services\Dgii;

use App\Config;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class DgiiCertificateService
{
    public function certPath(): string
    {
        $configured = config('dgii.cert_path');
        if ($configured && is_file($configured)) {
            return $configured;
        }

        $default = storage_path('app/private/dgii/identity.p12');
        if (is_file($default)) {
            return $default;
        }

        throw new RuntimeException('No se encontró el certificado .p12 de DGII. Súbelo en Configuración → Facturación electrónica.');
    }

    public function certExists(): bool
    {
        try {
            return is_file($this->certPath());
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function certBinary(): string
    {
        $path = $this->certPath();
        $bin = file_get_contents($path);
        if ($bin === false || $bin === '') {
            throw new RuntimeException('No se pudo leer el certificado .p12.');
        }
        return $bin;
    }

    public function password(?Config $config = null): string
    {
        try {
            $config = $config ?: Config::first();
        } catch (\Throwable $e) {
            $config = null;
        }

        if ($config && !empty($config->dgii_cert_password)) {
            try {
                return Crypt::decryptString($config->dgii_cert_password);
            } catch (\Throwable $e) {
                // Puede estar guardado en texto plano por migraciones antiguas
                return (string) $config->dgii_cert_password;
            }
        }

        $envPass = (string) config('dgii.cert_password', '');
        if ($envPass !== '') {
            return $envPass;
        }

        throw new RuntimeException('Falta la contraseña del certificado DGII. Configúrala en Configuración o en DGII_CERT_PASSWORD.');
    }

    public function storePassword(string $password, ?Config $config = null): void
    {
        $config = $config ?: Config::first();
        if (!$config) {
            throw new RuntimeException('No hay configuración de clínica para guardar la contraseña.');
        }
        $config->dgii_cert_password = Crypt::encryptString($password);
        $config->save();
    }

    public function storeUploadedCert(UploadedFile $file, ?Config $config = null): string
    {
        $dir = storage_path('app/private/dgii');
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        $dest = $dir . '/identity.p12';
        if (is_file($dest)) {
            @unlink($dest);
        }
        $file->move($dir, 'identity.p12');
        @chmod($dest, 0600);

        $config = $config ?: Config::first();
        if ($config) {
            $config->dgii_cert_loaded = true;
            $config->save();
        }

        return $dest;
    }

    public function validateReadable(?Config $config = null): array
    {
        $bin = $this->certBinary();
        $password = $this->password($config);
        $certs = [];
        if (!openssl_pkcs12_read($bin, $certs, $password)) {
            throw new RuntimeException('No se pudo abrir el .p12. Verifica la contraseña (y soporte OpenSSL legacy si el certificado es antiguo).');
        }

        $parsed = openssl_x509_parse($certs['cert'] ?? '');
        return [
            'ok' => true,
            'subject' => $parsed['subject'] ?? [],
            'valid_from' => isset($parsed['validFrom_time_t']) ? date('Y-m-d', $parsed['validFrom_time_t']) : null,
            'valid_to' => isset($parsed['validTo_time_t']) ? date('Y-m-d', $parsed['validTo_time_t']) : null,
        ];
    }
}
