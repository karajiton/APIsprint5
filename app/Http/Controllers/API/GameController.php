<?php

namespace App\Http\Controllers\API;
use App\Models\User;
use App\Models\Game;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GameController extends Controller
{
    
    
    public function rollDice($id){
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);}
        $diceOne = rand(1, 6);
        $diceTwo = rand(1, 6); 
        $win = $diceOne + $diceTwo == 7; 
        $game = Game::create(['user_id' => $id, 'dice_one' => $diceOne, 'dice_two' => $diceTwo, 'win' => $win]);

        
        $totalGames = $user->games()->count();
        $totalWins = $user->games()->where('win', true)->count();
        $user->success_rate = $totalGames ? ($totalWins / $totalGames) * 100 : 0;
        $user->save();

        return response()->json($game, 201);
    }
    public function deleteGames($id){
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);}
        $user->games()->delete();
        $user->success_rate = 0; // Reiniciar porcentaje de éxito
        $user->save();

        return response()->json(['message' => 'Tiradas eliminadas']);
    }
    public function listPlayers(){
        return User::all();
    }
    public function listGames($id){
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);}
        return $user->games;
    }
    public function ranking(){
        return User::orderByDesc('success_rate')->get();
    }
    public function worstPlayer(){
        return User::orderBy('success_rate')->first();
    }
    public function bestPlayer(){
        return User::orderByDesc('success_rate')->first();
    }
}

