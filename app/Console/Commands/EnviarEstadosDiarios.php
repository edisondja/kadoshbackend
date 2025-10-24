<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Recibo;
use Illuminate\Support\Facades\Mail;

class EnviarEstadosDiarios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'estados:enviar-diarios';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia el estado de ingreso todos los dias';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
   // En app/Console/Commands/TuComando.php (o donde esté definido tu comando)

    public function handle()
    {
        // 1. **Comentar o eliminar la lógica de verificación de estados/condiciones**
        /*
        $estados = Estado::whereDate('created_at', today())->get();

        if ($estados->isEmpty()) {
            $this->info('No hay estados para enviar hoy.');
            return Command::SUCCESS;
        }
        */

        // 2. Definir los destinatarios de prueba
        $destinatarios = ['edisondja2@gmail.com', 'edisondja@gmail.com'];

        // 3. **Asegúrate de que 'EstadoDiarioMail' exista y esté configurado**
        // (Asegúrate de haber ejecutado 'php artisan make:mail EstadoDiarioMail')
        foreach ($destinatarios as $email) {
            // La clase Mail usa la configuración de tu archivo .env para saber cómo enviar
            Mail::to($email)->send(new EstadoDiarioMail('prueba'));
        }

        $this->info('Estados diarios enviados correctamente para prueba.');
        return Command::SUCCESS;
    }
}
