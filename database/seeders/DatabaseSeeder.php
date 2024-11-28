<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Crear roles con el guard 'api'
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $player = Role::create(['name' => 'player', 'guard_name' => 'api']);

        // Crear permisos con el guard 'api'
        Permission::create(['name' => 'manage games', 'guard_name' => 'api'])->syncRoles([$admin]);
        Permission::create(['name' => 'play games', 'guard_name' => 'api'])->syncRoles([$admin,$player]);
         // Crear el usuario administrador
         $admind = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );
        
       $admind->assignRole($admin);
       //dd(Role::where('name', 'admin')->first());
        // Crear varios usuarios jugadores
        $playersData = [
            ['email' => 'player1@example.com', 'name' => 'Player One'],
            ['email' => 'player2@example.com', 'name' => 'Player Two'],
            ['email' => 'player3@example.com', 'name' => 'Player Three'],
            ['email' => 'player4@example.com', 'name' => 'Player Four'],
            ['email' => 'player5@example.com', 'name' => 'Player Five'],
        ];

        foreach ($playersData as $playerData) {
            $playerUser = User::firstOrCreate(
                ['email' => $playerData['email']],
                [
                    'name' => $playerData['name'],
                    'password' => Hash::make('password'), // Contraseña por defecto
                ]
            );
            $playerUser->assignRole($player);
        }
    }
}
