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
     
         
         $this->playerUser = User::create([
             'name' => 'PlayerUser',
             'email' => 'player@example.com',
             'password' => bcrypt('securePassword'),
         ]);
         $this->playerUser->assignRole('player');
     
        
         $this->adminUser = User::create([
             'name' => 'AdminUser2',
             'email' => 'admin2@example.com',
             'password' => bcrypt('securePassword'),
         ]);
         $this->adminUser->assignRole('admin');}

    public function test_roll_dice_creates_game_and_updates_user_success_rate()
    {
        
        $user = User::factory()->create();

        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,
        ])->postJson("/api/players/{$user->id}/games");

       
        $response->assertStatus(201);

       
        $this->assertDatabaseHas('games', [
            'user_id' => $user->id,
        ]);

       
        $user->refresh();
        $this->assertNotNull($user->success_rate);
    }

    public function test_roll_dice_winning_condition()
    {
        $user = User::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,
        ])->postJson("/api/players/{$user->id}/games");

        $response->assertStatus(201);

        $gameData = $response->json();

        $this->assertEquals($gameData['win'], $gameData['dice_one'] + $gameData['dice_two'] === 7);
    }

    public function test_roll_dice_for_nonexistent_user()
    {
       
        $nonexistentUserId = 999;
        
        $user = User::factory()->create();
      
        $response = $this->withHeaders([
                 'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,
        ])->postJson("/api/players/{$nonexistentUserId}/games");

        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'User not found',
            ]);
    }

    public function test_unauthenticated_user_cannot_roll_dice()
    {
      
        $user = User::factory()->create();

        $response = $this->postJson("/api/players/{$user->id}/games");

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.',
                 ]);
    }

    public function test_user_success_rate_is_calculated_correctly()
    {
       
        $user = User::factory()->create();

        
        Game::factory()->create(['user_id' => $user->id, 'win' => true]);
        Game::factory()->create(['user_id' => $user->id, 'win' => true]);
        Game::factory()->create(['user_id' => $user->id, 'win' => true]);
        

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,
        ])->postJson("/api/players/{$user->id}/games");

        $user->refresh();
        $expectedRate = (3 / 4) * 100; 
        $this->assertEquals($expectedRate, $user->success_rate);
    }
    public function test_delete_games_for_player_successfully()
    {
      
        $user = User::factory()->create();
        Game::factory()->count(5)->create(['user_id' => $user->id]);

        $this->assertDatabaseCount('games', 5);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,
        ])->deleteJson("/api/players/{$user->id}/games");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Tiradas eliminadas',
                 ]);

        $this->assertDatabaseCount('games', 0);

        $user->refresh();
        $this->assertEquals(0, $user->success_rate);
    }

    public function test_delete_games_for_nonexistent_player()
    {
        
        $nonexistentUserId = 999;

        $user = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->createToken('TestToken')->accessToken,
        ])->deleteJson("/api/players/{$nonexistentUserId}/games");

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
    public function test_list_games_success()
    {
        
        $user = User::factory()->create();
        $game = Game::factory()->create([
            'user_id' => $user->id,
        ]);
        $token = $user->createToken('TestToken')->accessToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                 ->getJson("/api/players/{$user->id}/games");

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $game->id,
            'user_id' => $user->id,
        ]);
    }
    public function test_list_games_user_not_found()
    {
        
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->accessToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                 ->getJson("/api/players/9999/games");
       
        $response->assertStatus(404);

        $response->assertJson([
            'status' => false,
            'message' => 'User not found',
        ]);
        
    }
    public function test_user_without_admin_role_cannot_access_players_list()
    {
        
        $user = User::factory()->create();

        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->accessToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('api/players');

       
        $response->assertStatus(403);
    }

    
    public function test_admin_user_can_access_players_list()
    {
        // Crea un usuario con el rol 'admin'
        $admin = User::factory()->create();
        $token = $admin->createToken('TestToken')->accessToken;
        $admin->assignRole('admin');

        // Crea algunos jugadores para que aparezcan en la respuesta
        $player1 = User::factory()->create();
        $player2 = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('api/players');

        $response->assertStatus(200);

        // Verifica que los jugadores estén presentes en la respuesta
        $response->assertJsonFragment(['email' => $player1->email]);
        $response->assertJsonFragment(['email' => $player2->email]);
    }
}
