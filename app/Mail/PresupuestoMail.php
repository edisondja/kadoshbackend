<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PresupuestoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $asunto;
    public $nombre_compania;
    public $logo_compania;
    public $direccion_compania;
    public $telefono_compania;
    public $pdfPath;

    public function __construct($asunto, $nombre_compania, $logo_compania, $direccion_compania, $telefono_compania, $pdfPath)
    {
        $this->asunto = $asunto;
        $this->nombre_compania = $nombre_compania;
        $this->logo_compania = $logo_compania;
        $this->direccion_compania = $direccion_compania;
        $this->telefono_compania = $telefono_compania;
        $this->pdfPath = $pdfPath;
    }

    public function build()
    {
        return $this->subject($this->asunto)
            ->view('presupuesto')
            ->attach($this->pdfPath, [
                'as'   => 'presupuesto.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
