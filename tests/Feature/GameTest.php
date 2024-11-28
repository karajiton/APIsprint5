<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Game;
use Illuminate\Support\Facades\Artisan;

class GameTest extends TestCase
{
    use RefreshDatabase;
    protected $playerUser;
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate');
        Artisan::call('db:seed');
        Artisan::call('passport:client', [
             '--name' => 'TestClient',
             '--no-interaction' => true,
             '--personal' => true
         ]);
     
         // Create a user with 'player' role
         $this->playerUser = User::create([
             'name' => 'PlayerUser',
             'email' => 'player@example.com',
             'password' => bcrypt('securePassword'),
         ]);
         $this->playerUser->assignRole('player');
     
         // Create a user with 'admin' role
         $this->adminUser = User::create([
             'name' => 'AdminUser2',
             'email' => 'admin2@example.com',
             'password' => bcrypt('securePassword'),
         ]);
         $this->adminUser->assignRole('admin');}

    public function test_roll_dice_creates_game_and_updates_user_success_rate()
    {
        // Crear un usuario ficticio
        $user = User::factory()->create();

        // Hacer la solicitud para tirar los dados
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,
        ])->postJson("/api/players/{$user->id}/games");

        // Verificar que la respuesta tiene un código 201
        $response->assertStatus(201);

        // Verificar que el juego se haya guardado en la base de datos
        $this->assertDatabaseHas('games', [
            'user_id' => $user->id,
        ]);

        // Verificar que la tasa de éxito del usuario se actualizó
        $user->refresh();
        $this->assertNotNull($user->success_rate);
    }

    public function test_roll_dice_winning_condition()
    {
        // Forzar el resultado de los dados
        $user = User::factory()->create();

        // Hacer la solicitud para tirar los dados
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,
        ])->postJson("/api/players/{$user->id}/games");

        // Verificar que la respuesta tiene un código 201
        $response->assertStatus(201);

        // Obtener los datos del juego desde la respuesta
        $gameData = $response->json();

        // Verificar si la suma de los dados es 7 para marcar como ganancia
        $this->assertEquals($gameData['win'], $gameData['dice_one'] + $gameData['dice_two'] === 7);
    }

    public function test_roll_dice_for_nonexistent_user()
    {
        // ID de usuario inexistente
        $nonexistentUserId = 999;
        
        $user = User::factory()->create();
        // Hacer la solicitud
        $response = $this->withHeaders([
                 'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,
        ])->postJson("/api/players/{$nonexistentUserId}/games");

        // Verificar que responde con 404
        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'User not found',
            ]);
    }

    public function test_unauthenticated_user_cannot_roll_dice()
    {
        // Crear un usuario
        $user = User::factory()->create();

        // Hacer la solicitud sin autenticación
        $response = $this->postJson("/api/players/{$user->id}/games");

        // Verificar que responde con 401
        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.',
                 ]);
    }

    public function test_user_success_rate_is_calculated_correctly()
    {
        // Crear un usuario ficticio
        $user = User::factory()->create();

        
        Game::factory()->create(['user_id' => $user->id, 'win' => true]);
        Game::factory()->create(['user_id' => $user->id, 'win' => true]);
        Game::factory()->create(['user_id' => $user->id, 'win' => true]);
        

        // Hacer la solicitud para tirar los dados
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,
        ])->postJson("/api/players/{$user->id}/games");

        // Verificar que la tasa de éxito se actualizó correctamente
        $user->refresh();
        $expectedRate = (3 / 4) * 100; // 3 ganados de 4 juegos
        $this->assertEquals($expectedRate, $user->success_rate);
    }
    public function test_delete_games_for_player_successfully()
    {
        // Crear un usuario con juegos
        $user = User::factory()->create();
        Game::factory()->count(5)->create(['user_id' => $user->id]);

        // Verificar que los juegos existen antes de la eliminación
        $this->assertDatabaseCount('games', 5);

        // Hacer la solicitud para eliminar los juegos
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,
        ])->deleteJson("/api/players/{$user->id}/games");

        // Verificar que responde con éxito
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Tiradas eliminadas',
                 ]);

        // Verificar que los juegos fueron eliminados
        $this->assertDatabaseCount('games', 0);

        // Verificar que la tasa de éxito del usuario es 0
        $user->refresh();
        $this->assertEquals(0, $user->success_rate);
    }

    public function test_delete_games_for_nonexistent_player()
    {
        // ID de usuario inexistente
        $nonexistentUserId = 999;

        // Crear un usuario autenticado
        $user = User::factory()->create();

        // Hacer la solicitud para un usuario inexistente
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,
        ])->deleteJson("/api/players/{$nonexistentUserId}/games");

        // Verificar que responde con 404
        $response->assertStatus(404)
        ->assertJson([
            'status' => false,
            'message' => 'User not found',
        ]);
    }

    public function test_unauthenticated_user_cannot_delete_games()
    {
        // Crear un usuario con juegos
        $user = User::factory()->create();
        Game::factory()->count(5)->create(['user_id' => $user->id]);

        // Hacer la solicitud sin autenticación
        $response = $this->deleteJson("/api/players/{$user->id}/games");

        // Verificar que responde con 401
        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.',
                 ]);
    }

}
