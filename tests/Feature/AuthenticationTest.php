<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

class AuthenticationTest extends TestCase
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
         $this->adminUser->assignRole('admin');
     }
    public function test_register_new_user_successfully()
    {
        $this->withoutExceptionHandling();
        $response = $this->postJson('/api/register', [
            'name' => 'TestPlayer',
            'email' => 'testplayer@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'TestPlayer',
            'email' => 'testplayer@example.com',
        ]);

        $user = User::where('email', 'testplayer@example.com')->first();
        
        $this->assertTrue($user->hasRole('player'));
        
    }
    public function test_register_user_validation_error()
    {
        $response = $this->postJson('/api/register', [
            'name'  => 'anonimo',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password', 'email']);
    }
    public function test_register_with_duplicate_name()
    {
        User::factory()->create(['name' => 'DuplicateName', 'email' => 'duplicate@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'DuplicateName',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
    public function test_register_as_anonymous_user()
    {
        $response = $this->postJson('/api/register', [
            'email' => 'anonymous@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'anonimo');

        $this->assertDatabaseHas('users', [
            'email' => 'anonymous@example.com',
            'name' => 'anonimo',
        ]);
    }
    public function test_login_successful()
{
    // Crear un usuario de prueba
    $user = User::factory()->create([
        'email' => 'testuser@example.com',
        'password' => bcrypt('password123'),
    ]);

    // Enviar solicitud de login
    $response = $this->postJson('/api/login', [
        'email' => 'testuser@example.com',
        'password' => 'password123',
    ]);

    // Verificar que la respuesta tiene un código 200 y contiene el token
    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'token',
            'user' => [
                'id',
                'name',
                'email',
                'created_at',
                'updated_at',
            ],
        ]);
    }
    public function test_login_validation_error()
    {
    // Enviar solicitud con datos incompletos
    $response = $this->postJson('api/login', [
        'email' => '',
        'password' => '',
    ]);

    // Verificar que la respuesta tiene un código 422 y errores de validación
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
    }
    public function test_login_invalid_credentials()
    {
    // Crear un usuario de prueba
    $user = User::factory()->create([
        'email' => 'testuser@example.com',
        'password' => bcrypt('password123'),
    ]);

    // Enviar solicitud de login con credenciales incorrectas
    $response = $this->postJson('/api/login', [
        'email' => 'testuser@example.com',
        'password' => 'wrongpassword',
    ]);

    // Verificar que la respuesta tiene un código 401 y el mensaje esperado
    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Invalid credentials',
        ]);
    }
    public function test_update_user_successfully()
    {
        // Crear un usuario de prueba
        $user = User::factory()->create([
            'email' => 'original@example.com',
            'password' => bcrypt('password123'),
        ]);
        $authUser = User::factory()->create();
        $token = $user->createToken('TestToken')->accessToken;
    
        // Enviar solicitud de actualización
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/players/{$user->id}", [
            'name' => 'Updated Name',
            
        ]);
    
        // Verificar que la respuesta es exitosa
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Player updated successfully',
            ]);
    
        // Verificar que los datos se actualizaron en la base de datos
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }
        public function test_update_user_not_found()
        {     $user = User::factory()->create([
            'email' => 'original@example.com',
            'password' => bcrypt('password123'),
        ]);
            $token = $user->createToken('TestToken')->accessToken;
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->putJson('/api/players/99', [
                'name' => 'Nonexistent User',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'User not found',
            ]);
    }
       
    
    

}
