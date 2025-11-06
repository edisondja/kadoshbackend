<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class ReciboMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $asunto;
    public $pdfPath;
    public $nombre_compania;
    public $logo_compania;
    public $direccion_compania;
    public $telefono_compania;

    public function __construct($asunto, $pdfPath, $nombre_compania, $logo_compania, $direccion_compania, $telefono_compania)
    {
        $this->asunto = $asunto;
        $this->pdfPath = $pdfPath;
        $this->nombre_compania = $nombre_compania;
        $this->logo_compania = $logo_compania;
        $this->direccion_compania = $direccion_compania;
        $this->telefono_compania = $telefono_compania;
    
    }

    public function build()
    {
        return $this->subject($this->asunto)
            ->view('recibo')->with([
                'nombre_compania' => $this->nombre_compania,
                'logo_compania' => $this->logo_compania,
                'direccion_compania' => $this->direccion_compania,
                'telefono_compania' => $this->telefono_compania,
            ])
            ->attach($this->pdfPath, [
                'as' => 'recibo.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
