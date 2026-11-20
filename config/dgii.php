<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Certificado digital DGII (.p12)
    |--------------------------------------------------------------------------
    */
    'cert_path' => env('DGII_CERT_PATH', storage_path('app/private/dgii/identity.p12')),
    'cert_password' => env('DGII_CERT_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Ambiente: testecf | certecf | ecf
    |--------------------------------------------------------------------------
    */
    'ambiente' => env('DGII_AMBIENTE', 'testecf'),

    /*
    |--------------------------------------------------------------------------
    | Defaults de emisión
    |--------------------------------------------------------------------------
    */
    'tipo_ecf_default' => env('DGII_TIPO_ECF', '32'),
    'tipo_ingresos' => env('DGII_TIPO_INGRESOS', '01'),
    'itbis_modo' => env('DGII_ITBIS_MODO', 'exento'), // exento | 18
    'auto_emitir' => env('DGII_AUTO_EMITIR', true),
    'monto_rfce_max' => 250000.00,

    'bases' => [
        'testecf' => [
            'auth' => 'https://ecf.dgii.gov.do/testecf/autenticacion',
            'recepcion' => 'https://ecf.dgii.gov.do/testecf/recepcion',
            'recepcion_fc' => 'https://fc.dgii.gov.do/testecf/recepcionfc',
            'consulta' => 'https://ecf.dgii.gov.do/testecf/consultaresultado',
            'qr_fc' => 'https://fc.dgii.gov.do/testecf/consultatimbrefc',
            'qr_ecf' => 'https://ecf.dgii.gov.do/testecf/ConsultaTimbre',
        ],
        'certecf' => [
            'auth' => 'https://ecf.dgii.gov.do/certecf/autenticacion',
            'recepcion' => 'https://ecf.dgii.gov.do/certecf/recepcion',
            'recepcion_fc' => 'https://fc.dgii.gov.do/certecf/recepcionfc',
            'consulta' => 'https://ecf.dgii.gov.do/certecf/consultaresultado',
            'qr_fc' => 'https://fc.dgii.gov.do/certecf/consultatimbrefc',
            'qr_ecf' => 'https://ecf.dgii.gov.do/certecf/ConsultaTimbre',
        ],
        'ecf' => [
            'auth' => 'https://ecf.dgii.gov.do/ecf/autenticacion',
            'recepcion' => 'https://ecf.dgii.gov.do/ecf/recepcion',
            'recepcion_fc' => 'https://fc.dgii.gov.do/ecf/recepcionfc',
            'consulta' => 'https://ecf.dgii.gov.do/ecf/consultaresultado',
            'qr_fc' => 'https://fc.dgii.gov.do/ecf/consultatimbrefc',
            'qr_ecf' => 'https://ecf.dgii.gov.do/ecf/ConsultaTimbre',
        ],
    ],
];
