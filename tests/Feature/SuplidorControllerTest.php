<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;

class SuplidorControllerTest extends TestCase
{
    use WithFaker;

    /** @test */
    public function registrar_suplidor()
    {
        $payload = [
            'nombre_suplidor'=> 'Kadosh Suplidor test',
            'descripcion' => 'The Best',
            'rnc_suplidor' => '1654196584',
            'id_usuario' => 1,
        ];

        $response = $this->postJson('/api/registrar_suplidor', $payload);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'ok',
                     'nombre' => $payload['nombre_suplidor'],
                 ]);
    }
}
