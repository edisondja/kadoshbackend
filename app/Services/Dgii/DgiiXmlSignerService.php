<?php

namespace App\Services\Dgii;

use App\Config;
use PlatinumPlace\DgiiXmlSigner\SignManager;
use RuntimeException;

class DgiiXmlSignerService
{
    public function __construct(private DgiiCertificateService $certs)
    {
    }

    public function sign(string $xml, ?Config $config = null): string
    {
        $manager = new SignManager();
        $bin = $this->certs->certBinary();
        $password = $this->certs->password($config);
        try {
            return $manager->sign($bin, $password, $xml);
        } catch (\Throwable $e) {
            // Algunos .p12 dejan la clave PEM sin cifrar tras openssl_pkcs12_read
            try {
                return $this->signWithEmptyPemPass($bin, $password, $xml);
            } catch (\Throwable $e2) {
                throw new RuntimeException('Error al firmar XML DGII: ' . $e->getMessage(), 0, $e);
            }
        }
    }

    private function signWithEmptyPemPass(string $certStore, string $p12Password, string $xml): string
    {
        $certs = [];
        if (!openssl_pkcs12_read($certStore, $certs, $p12Password)) {
            throw new RuntimeException('No fue posible leer el certificado .p12');
        }
        $pem = ($certs['cert'] ?? '') . ($certs['pkey'] ?? '');
        $store = new \Selective\XmlDSig\PrivateKeyStore();
        $store->loadFromPem($pem, '');
        $store->addCertificatesFromX509Pem($pem);
        $algorithm = new \Selective\XmlDSig\Algorithm(\Selective\XmlDSig\Algorithm::METHOD_SHA256);
        $crypto = new \Selective\XmlDSig\CryptoSigner($store, $algorithm);
        $xmlSigner = new \PlatinumPlace\DgiiXmlSigner\XmlSigner($crypto);
        $xmlSigner->setReferenceUri('');
        return $xmlSigner->signXml($xml);
    }

    public function codigoSeguridad(string $signedXml): string
    {
        if (!preg_match('/<SignatureValue[^>]*>(.*?)<\/SignatureValue>/s', $signedXml, $m)) {
            throw new RuntimeException('SignatureValue no encontrado en el XML firmado.');
        }
        $value = preg_replace('/\s+/', '', $m[1]);
        return substr($value, 0, 6);
    }
}
