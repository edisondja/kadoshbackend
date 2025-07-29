<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SuplidorControllerTest extends TestCase
{

    /** @test */
    public function registrar_suplidor()
    {
        $payload = [
            'nombre_suplidor' => 'Alexander Dientess',
            'descripcion'     => 'The Best',
            'rnc_suplidor'    => '1654196584',
            'usuario_id'      => 1,
        ];

        $response = $this->postJson('/api/registrar_suplidor', $payload);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'status' => 'ok',
                     'nombre' => $payload['nombre_suplidor'],
                 ]);

    }
    
    public function test_buscar_suplidor()
    {
        $nombre = 'Nitro ult';

        $response = $this->getJson("/api/buscar_suplidor/$nombre");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'status' => 'ok'
                ]);
    }


}
