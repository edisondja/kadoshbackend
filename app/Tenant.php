<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    /**
     * La tabla tenants siempre debe estar en la base de datos maestra (mysql)
     * No debe cambiar según el subdominio
     */
    protected $connection = 'mysql';
    
    protected $fillable = [
        'nombre',
        'subdominio',
        'dominio',
        'database_name',
        'document_root',
        'api_url',
        'vhost_enabled',
        'ultimo_deploy_at',
        'fecha_vencimiento',
        'activo',
        'bloqueado',
        'notas',
        'contacto_nombre',
        'contacto_email',
        'contacto_telefono'
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'activo' => 'boolean',
        'bloqueado' => 'boolean',
        'vhost_enabled' => 'boolean',
        'ultimo_deploy_at' => 'datetime',
    ];

    /**
     * Document root por defecto bajo /var/www/<dominio>/public_html
     */
    public function documentRootResuelto()
    {
        if (!empty($this->document_root)) {
            return rtrim($this->document_root, '/');
        }
        $dominio = $this->dominioResuelto();
        return $dominio ? '/var/www/' . $dominio . '/public_html' : null;
    }

    public function dominioResuelto()
    {
        if (!empty($this->dominio)) {
            return strtolower(trim($this->dominio));
        }
        if (!empty($this->subdominio)) {
            return strtolower(trim($this->subdominio)) . '.odontoed.com';
        }
        return null;
    }

    public function apiUrlResuelta()
    {
        if (!empty($this->api_url)) {
            return rtrim($this->api_url, '/');
        }
        $dominio = $this->dominioResuelto();
        return $dominio ? 'https://' . $dominio : null;
    }

    /**
     * Verificar si el tenant está vencido
     */
    public function estaVencido()
    {
        if (!$this->fecha_vencimiento) {
            return false;
        }
        
        return now()->greaterThan($this->fecha_vencimiento);
    }

    /**
     * Verificar si el tenant puede acceder al sistema
     */
    public function puedeAcceder()
    {
        if (!$this->activo) {
            return false;
        }
        
        if ($this->bloqueado) {
            return false;
        }
        
        if ($this->estaVencido()) {
            return false;
        }
        
        return true;
    }

    /**
     * Días restantes hasta el vencimiento
     */
    public function diasRestantes()
    {
        if (!$this->fecha_vencimiento) {
            return null;
        }
        
        $dias = now()->diffInDays($this->fecha_vencimiento, false);
        return $dias;
    }

    /**
     * Estado del tenant
     */
    public function getEstadoAttribute()
    {
        if ($this->bloqueado) {
            return 'bloqueado';
        }
        
        if (!$this->activo) {
            return 'inactivo';
        }
        
        if ($this->estaVencido()) {
            return 'vencido';
        }
        
        $dias = $this->diasRestantes();
        if ($dias !== null && $dias <= 7) {
            return 'por_vencer';
        }
        
        return 'activo';
    }
}
