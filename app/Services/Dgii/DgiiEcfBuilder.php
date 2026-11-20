<?php

namespace App\Services\Dgii;

use App\Config;
use RuntimeException;

class DgiiEcfBuilder
{
    /**
     * Construye XML ECF tipo 32 (Factura de Consumo Electrónica).
     *
     * @param  array{
     *   encf:string,rnc_emisor:string,razon_emisor:string,direccion_emisor?:string,
     *   telefono_emisor?:string,correo_emisor?:string,fecha_emision:string,
     *   fecha_firma:string,fecha_vencimiento?:?string,tipo_ingresos:string,
     *   tipo_pago:int,forma_pago:int,rnc_comprador?:?string,razon_comprador:string,
     *   items:array<int,array{nombre:string,cantidad:float,precio:float,monto:float}>,
     *   itbis_modo:string,numero_interno?:string
     * }  $data
     */
    public function buildEcf32(array $data): string
    {
        $itbisModo = $data['itbis_modo'] ?? 'exento';
        $items = $data['items'] ?? [];
        if (count($items) === 0) {
            throw new RuntimeException('El e-CF requiere al menos un ítem.');
        }

        $montoTotalItems = 0.0;
        foreach ($items as $item) {
            $montoTotalItems += (float) $item['monto'];
        }
        $montoTotalItems = round($montoTotalItems, 2);

        if ($itbisModo === '18') {
            // Precios de clínica suelen ser con ITBIS incluido → desglose
            $montoGravado = round($montoTotalItems / 1.18, 2);
            $totalItbis = round($montoTotalItems - $montoGravado, 2);
            $montoExento = 0.0;
            $indicadorFacturacion = '1';
            $itbis1 = '18';
        } else {
            $montoGravado = 0.0;
            $totalItbis = 0.0;
            $montoExento = $montoTotalItems;
            $indicadorFacturacion = '4';
            $itbis1 = null;
        }

        $montoTotal = round($montoGravado + $totalItbis + $montoExento, 2);
        $tipoPago = (int) ($data['tipo_pago'] ?? 1);
        $formaPago = (int) ($data['forma_pago'] ?? 1);

        $xml = new \DOMDocument('1.0', 'utf-8');
        $xml->formatOutput = false;
        $xml->preserveWhiteSpace = false;

        $ecf = $xml->createElement('ECF');
        $xml->appendChild($ecf);

        $enc = $xml->createElement('Encabezado');
        $ecf->appendChild($enc);
        $enc->appendChild($xml->createElement('Version', '1.0'));

        $idDoc = $xml->createElement('IdDoc');
        $enc->appendChild($idDoc);
        $idDoc->appendChild($xml->createElement('TipoeCF', '32'));
        $idDoc->appendChild($xml->createElement('eNCF', $data['encf']));
        if (!empty($data['fecha_vencimiento'])) {
            $idDoc->appendChild($xml->createElement('FechaVencimientoSecuencia', $data['fecha_vencimiento']));
        }
        $idDoc->appendChild($xml->createElement('IndicadorMontoGravado', $itbisModo === '18' ? '0' : '0'));
        $idDoc->appendChild($xml->createElement('TipoIngresos', $data['tipo_ingresos'] ?? '01'));
        $idDoc->appendChild($xml->createElement('TipoPago', (string) $tipoPago));

        $tablaFormas = $xml->createElement('TablaFormasPago');
        $idDoc->appendChild($tablaFormas);
        $forma = $xml->createElement('FormaDePago');
        $tablaFormas->appendChild($forma);
        $forma->appendChild($xml->createElement('FormaPago', (string) $formaPago));
        $forma->appendChild($xml->createElement('MontoPago', $this->money($montoTotal)));

        $emisor = $xml->createElement('Emisor');
        $enc->appendChild($emisor);
        $emisor->appendChild($xml->createElement('RNCEmisor', preg_replace('/\D+/', '', $data['rnc_emisor'])));
        $emisor->appendChild($xml->createElement('RazonSocialEmisor', $this->clean($data['razon_emisor'])));
        if (!empty($data['direccion_emisor'])) {
            $emisor->appendChild($xml->createElement('DireccionEmisor', $this->clean($data['direccion_emisor'])));
        }
        if (!empty($data['telefono_emisor'])) {
            $tablaTel = $xml->createElement('TablaTelefonoEmisor');
            $emisor->appendChild($tablaTel);
            $tablaTel->appendChild($xml->createElement('TelefonoEmisor', $this->clean($data['telefono_emisor'])));
        }
        if (!empty($data['correo_emisor'])) {
            $emisor->appendChild($xml->createElement('CorreoEmisor', $this->clean($data['correo_emisor'])));
        }
        if (!empty($data['numero_interno'])) {
            $emisor->appendChild($xml->createElement('NumeroFacturaInterna', $this->clean((string) $data['numero_interno'])));
        }
        $emisor->appendChild($xml->createElement('FechaEmision', $data['fecha_emision']));

        $comprador = $xml->createElement('Comprador');
        $enc->appendChild($comprador);
        $rncComp = preg_replace('/\D+/', '', (string) ($data['rnc_comprador'] ?? ''));
        if ($rncComp !== '') {
            // Cédula 11 dígitos o RNC 9
            if (strlen($rncComp) === 11 || strlen($rncComp) === 9) {
                $comprador->appendChild($xml->createElement('RNCComprador', $rncComp));
            }
        }
        $comprador->appendChild($xml->createElement('RazonSocialComprador', $this->clean($data['razon_comprador'])));

        $totales = $xml->createElement('Totales');
        $enc->appendChild($totales);
        if ($itbisModo === '18') {
            $totales->appendChild($xml->createElement('MontoGravadoTotal', $this->money($montoGravado)));
            $totales->appendChild($xml->createElement('MontoGravadoI1', $this->money($montoGravado)));
            $totales->appendChild($xml->createElement('MontoExento', '0'));
            $totales->appendChild($xml->createElement('ITBIS1', $itbis1));
            $totales->appendChild($xml->createElement('TotalITBIS', $this->money($totalItbis)));
            $totales->appendChild($xml->createElement('TotalITBIS1', $this->money($totalItbis)));
        } else {
            $totales->appendChild($xml->createElement('MontoGravadoTotal', '0'));
            $totales->appendChild($xml->createElement('MontoExento', $this->money($montoExento)));
            $totales->appendChild($xml->createElement('TotalITBIS', '0'));
        }
        $totales->appendChild($xml->createElement('MontoTotal', $this->money($montoTotal)));

        $detalles = $xml->createElement('DetallesItems');
        $ecf->appendChild($detalles);
        $linea = 1;
        $subGravado = 0.0;
        $subExento = 0.0;
        $subItbis = 0.0;
        foreach ($items as $item) {
            $itemNode = $xml->createElement('Item');
            $detalles->appendChild($itemNode);
            $itemNode->appendChild($xml->createElement('NumeroLinea', (string) $linea));
            $itemNode->appendChild($xml->createElement('IndicadorFacturacion', $indicadorFacturacion));
            $itemNode->appendChild($xml->createElement('NombreItem', $this->clean($item['nombre'])));
            $itemNode->appendChild($xml->createElement('IndicadorBienoServicio', '2'));
            $itemNode->appendChild($xml->createElement('CantidadItem', $this->qty($item['cantidad'])));
            $itemNode->appendChild($xml->createElement('UnidadMedida', '43')); // Unidad

            $montoItem = round((float) $item['monto'], 2);
            if ($itbisModo === '18') {
                $base = round($montoItem / 1.18, 2);
                $precioUnit = round($base / max((float) $item['cantidad'], 0.0001), 4);
                $itemNode->appendChild($xml->createElement('PrecioUnitarioItem', $this->money($precioUnit, 4)));
                $itemNode->appendChild($xml->createElement('MontoItem', $this->money($base)));
                $subGravado += $base;
                $subItbis += round($montoItem - $base, 2);
            } else {
                $precioUnit = round($montoItem / max((float) $item['cantidad'], 0.0001), 4);
                $itemNode->appendChild($xml->createElement('PrecioUnitarioItem', $this->money($precioUnit, 4)));
                $itemNode->appendChild($xml->createElement('MontoItem', $this->money($montoItem)));
                $subExento += $montoItem;
            }
            $linea++;
        }

        $paginacion = $xml->createElement('Paginacion');
        $ecf->appendChild($paginacion);
        $pagina = $xml->createElement('Pagina');
        $paginacion->appendChild($pagina);
        $pagina->appendChild($xml->createElement('PaginaNo', '1'));
        $pagina->appendChild($xml->createElement('NoLineaDesde', '1'));
        $pagina->appendChild($xml->createElement('NoLineaHasta', (string) ($linea - 1)));
        if ($itbisModo === '18') {
            $pagina->appendChild($xml->createElement('SubtotalMontoGravadoPagina', $this->money($subGravado)));
            $pagina->appendChild($xml->createElement('SubtotalMontoGravado1Pagina', $this->money($subGravado)));
            $pagina->appendChild($xml->createElement('SubtotalExentoPagina', '0'));
            $pagina->appendChild($xml->createElement('SubtotalItbisPagina', $this->money($subItbis)));
            $pagina->appendChild($xml->createElement('SubtotalItbis1Pagina', $this->money($subItbis)));
        } else {
            $pagina->appendChild($xml->createElement('SubtotalMontoGravadoPagina', '0'));
            $pagina->appendChild($xml->createElement('SubtotalExentoPagina', $this->money($subExento)));
            $pagina->appendChild($xml->createElement('SubtotalItbisPagina', '0'));
        }
        $pagina->appendChild($xml->createElement('MontoSubtotalPagina', $this->money($montoTotal)));

        $ecf->appendChild($xml->createElement('FechaHoraFirma', $data['fecha_firma']));

        return $xml->saveXML();
    }

    public function buildRfceFromSignedEcf32(string $signedEcfXml, string $codigoSeguridad): string
    {
        $src = new \DOMDocument();
        $src->preserveWhiteSpace = false;
        if (!$src->loadXML($signedEcfXml)) {
            throw new RuntimeException('No se pudo parsear el e-CF firmado para RFCE.');
        }

        $xp = new \DOMXPath($src);
        $get = function (string $query) use ($xp) {
            $n = $xp->query($query);
            return ($n && $n->length) ? trim($n->item(0)->textContent) : '';
        };

        $out = new \DOMDocument('1.0', 'utf-8');
        $out->formatOutput = false;
        $rfce = $out->createElement('RFCE');
        $out->appendChild($rfce);
        $enc = $out->createElement('Encabezado');
        $rfce->appendChild($enc);
        $enc->appendChild($out->createElement('Version', '1.0'));

        $idDoc = $out->createElement('IdDoc');
        $enc->appendChild($idDoc);
        $idDoc->appendChild($out->createElement('TipoeCF', $get('//ECF/Encabezado/IdDoc/TipoeCF') ?: '32'));
        $idDoc->appendChild($out->createElement('eNCF', $get('//ECF/Encabezado/IdDoc/eNCF')));
        $idDoc->appendChild($out->createElement('TipoIngresos', $get('//ECF/Encabezado/IdDoc/TipoIngresos')));
        $idDoc->appendChild($out->createElement('TipoPago', $get('//ECF/Encabezado/IdDoc/TipoPago')));

        $formasSrc = $xp->query('//ECF/Encabezado/IdDoc/TablaFormasPago/FormaDePago');
        if ($formasSrc && $formasSrc->length) {
            $tabla = $out->createElement('TablaFormasPago');
            $idDoc->appendChild($tabla);
            foreach ($formasSrc as $f) {
                $forma = $out->createElement('FormaDePago');
                $tabla->appendChild($forma);
                $fp = '';
                $mp = '';
                foreach ($f->childNodes as $c) {
                    if ($c->nodeName === 'FormaPago') {
                        $fp = trim($c->textContent);
                    }
                    if ($c->nodeName === 'MontoPago') {
                        $mp = trim($c->textContent);
                    }
                }
                $forma->appendChild($out->createElement('FormaPago', $fp));
                $forma->appendChild($out->createElement('MontoPago', $mp));
            }
        }

        $emisor = $out->createElement('Emisor');
        $enc->appendChild($emisor);
        $emisor->appendChild($out->createElement('RNCEmisor', $get('//ECF/Encabezado/Emisor/RNCEmisor')));
        $emisor->appendChild($out->createElement('RazonSocialEmisor', $get('//ECF/Encabezado/Emisor/RazonSocialEmisor')));
        $emisor->appendChild($out->createElement('FechaEmision', $get('//ECF/Encabezado/Emisor/FechaEmision')));

        $comprador = $out->createElement('Comprador');
        $enc->appendChild($comprador);
        $rncC = $get('//ECF/Encabezado/Comprador/RNCComprador');
        if ($rncC !== '') {
            $comprador->appendChild($out->createElement('RNCComprador', $rncC));
        }
        $comprador->appendChild($out->createElement('RazonSocialComprador', $get('//ECF/Encabezado/Comprador/RazonSocialComprador')));

        $totales = $out->createElement('Totales');
        $enc->appendChild($totales);
        foreach ([
            'MontoGravadoTotal', 'MontoGravadoI1', 'MontoGravadoI2', 'MontoGravadoI3',
            'MontoExento', 'TotalITBIS', 'TotalITBIS1', 'TotalITBIS2', 'TotalITBIS3',
            'MontoTotal', 'MontoNoFacturable', 'MontoPeriodo',
        ] as $tag) {
            $val = $get('//ECF/Encabezado/Totales/' . $tag);
            if ($val !== '') {
                $totales->appendChild($out->createElement($tag, $val));
            }
        }

        $enc->appendChild($out->createElement('CodigoSeguridadeCF', $codigoSeguridad));

        return $out->saveXML();
    }

    private function money(float $n, int $decimals = 2): string
    {
        return number_format($n, $decimals, '.', '');
    }

    private function qty($n): string
    {
        $f = (float) $n;
        return (floor($f) == $f) ? (string) (int) $f : number_format($f, 2, '.', '');
    }

    private function clean(string $s): string
    {
        $s = trim(preg_replace('/\s+/u', ' ', $s) ?? '');
        // DOMDocument escapa al serializar; no usar htmlspecialchars aquí.
        return preg_replace('/[^\P{C}\t]/u', '', $s) ?? $s;
    }
}
