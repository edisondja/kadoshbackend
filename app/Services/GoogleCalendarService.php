<?php

namespace App\Services;

use App\Config;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GoogleCalendarService
{
    protected $client;
    protected $calendarService;
    protected $config;

    public function __construct()
    {
        $this->config = Config::first();
        
        if (!$this->config || !$this->config->usar_google_calendar || !$this->config->api_token_google) {
            return;
        }

        try {
            $this->client = new Google_Client();
            
            // Si api_token_google es un JSON string, decodificarlo
            $tokenData = is_string($this->config->api_token_google) 
                ? json_decode($this->config->api_token_google, true) 
                : $this->config->api_token_google;
            
            if (is_array($tokenData)) {
                $this->client->setAuthConfig($tokenData);
            } else {
                // Si es un token de acceso directo
                $this->client->setAccessToken($this->config->api_token_google);
            }
            
            $this->client->addScope(Google_Service_Calendar::CALENDAR);
            $this->client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
            
            $this->calendarService = new Google_Service_Calendar($this->client);
        } catch (\Exception $e) {
            Log::error('Error al inicializar Google Calendar: ' . $e->getMessage());
        }
    }

    /**
     * Crear un evento en Google Calendar
     */
    public function crearEvento($cita, $paciente, $doctor)
    {
        if (!$this->calendarService || !$this->config) {
            return false;
        }

        try {
            $calendarId = $this->config->google_calendar_id ?: 'primary';
            
            $event = new Google_Service_Calendar_Event();
            $event->setSummary('Cita: ' . $cita->motivo);
            $event->setDescription(
                "Paciente: {$paciente->nombre} {$paciente->apellido}\n" .
                "Doctor: {$doctor->nombre} {$doctor->apellido}\n" .
                "Teléfono: {$paciente->telefono}\n" .
                "Motivo: {$cita->motivo}"
            );

            // Fecha de inicio
            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime(date('c', strtotime($cita->inicio)));
            $start->setTimeZone('America/Santo_Domingo');
            $event->setStart($start);

            // Fecha de fin
            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime(date('c', strtotime($cita->fin)));
            $end->setTimeZone('America/Santo_Domingo');
            $event->setEnd($end);

            // Recordatorio
            if ($this->config->recordatorio_minutos > 0) {
                $reminder = new \Google_Service_Calendar_EventReminder();
                $reminder->setMethod('email');
                $reminder->setMinutes($this->config->recordatorio_minutos);
                
                $reminders = new \Google_Service_Calendar_EventReminders();
                $reminders->setUseDefault(false);
                $reminders->setOverrides([$reminder]);
                $event->setReminders($reminders);
            }

            // Invitar al paciente por correo si tiene email
            if (!empty($paciente->correo)) {
                $attendee = new \Google_Service_Calendar_EventAttendee();
                $attendee->setEmail($paciente->correo);
                $attendee->setDisplayName($paciente->nombre . ' ' . $paciente->apellido);
                $event->setAttendees([$attendee]);
            }

            // Invitar al correo de la clínica
            if (!empty($this->config->email_clinica)) {
                $attendeeClinica = new \Google_Service_Calendar_EventAttendee();
                $attendeeClinica->setEmail($this->config->email_clinica);
                $attendeeClinica->setDisplayName($this->config->nombre_clinica ?: 'Clínica');
                $attendees = $event->getAttendees() ?: [];
                $attendees[] = $attendeeClinica;
                $event->setAttendees($attendees);
            }

            $createdEvent = $this->calendarService->events->insert($calendarId, $event);
            
            // Enviar correo de confirmación al paciente
            if (!empty($paciente->correo)) {
                $this->enviarCorreoConfirmacion($cita, $paciente, $doctor);
            }

            return $createdEvent->getId();
        } catch (\Exception $e) {
            Log::error('Error al crear evento en Google Calendar: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar un evento en Google Calendar
     */
    public function actualizarEvento($eventId, $cita, $paciente, $doctor)
    {
        if (!$this->calendarService || !$this->config || !$eventId) {
            return false;
        }

        try {
            $calendarId = $this->config->google_calendar_id ?: 'primary';
            $event = $this->calendarService->events->get($calendarId, $eventId);

            $event->setSummary('Cita: ' . $cita->motivo);
            $event->setDescription(
                "Paciente: {$paciente->nombre} {$paciente->apellido}\n" .
                "Doctor: {$doctor->nombre} {$doctor->apellido}\n" .
                "Teléfono: {$paciente->telefono}\n" .
                "Motivo: {$cita->motivo}"
            );

            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime(date('c', strtotime($cita->inicio)));
            $start->setTimeZone('America/Santo_Domingo');
            $event->setStart($start);

            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime(date('c', strtotime($cita->fin)));
            $end->setTimeZone('America/Santo_Domingo');
            $event->setEnd($end);

            $updatedEvent = $this->calendarService->events->update($calendarId, $eventId, $event);
            return $updatedEvent->getId();
        } catch (\Exception $e) {
            Log::error('Error al actualizar evento en Google Calendar: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar un evento de Google Calendar
     */
    public function eliminarEvento($eventId)
    {
        if (!$this->calendarService || !$this->config || !$eventId) {
            return false;
        }

        try {
            $calendarId = $this->config->google_calendar_id ?: 'primary';
            $this->calendarService->events->delete($calendarId, $eventId);
            return true;
        } catch (\Exception $e) {
            Log::error('Error al eliminar evento de Google Calendar: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar correo de confirmación al paciente
     */
    protected function enviarCorreoConfirmacion($cita, $paciente, $doctor)
    {
        try {
            $config = Config::first();
            $asunto = "Confirmación de Cita - {$config->nombre_clinica}";
            
            $mensaje = "
                <h2>Confirmación de Cita</h2>
                <p>Estimado/a {$paciente->nombre} {$paciente->apellido},</p>
                <p>Su cita ha sido confirmada:</p>
                <ul>
                    <li><strong>Fecha y Hora:</strong> " . date('d/m/Y H:i', strtotime($cita->inicio)) . "</li>
                    <li><strong>Doctor:</strong> Dr. {$doctor->nombre} {$doctor->apellido}</li>
                    <li><strong>Motivo:</strong> {$cita->motivo}</li>
                </ul>
                <p>Por favor, llegue 10 minutos antes de su cita.</p>
                <p>Si necesita cancelar o reprogramar, por favor contáctenos.</p>
                <br>
                <p>Saludos,<br>{$config->nombre_clinica}</p>
            ";

            Mail::send([], [], function ($message) use ($paciente, $asunto, $mensaje, $config) {
                $message->to($paciente->correo, $paciente->nombre . ' ' . $paciente->apellido)
                         ->subject($asunto)
                         ->from($config->email_clinica ?: $config->email, $config->nombre_clinica)
                         ->html($mensaje);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Error al enviar correo de confirmación: ' . $e->getMessage());
            return false;
        }
    }
}
