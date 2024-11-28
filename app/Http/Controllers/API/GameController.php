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
            if ($user->games->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No games found for this user',
                ], 404); 
            }
            return $user->games;
    }
    public function ranking(){
        $players = User::has('games')->get();

    if ($players->isEmpty()) {
        return response()->json([
            'status' => 'error',
            'message' => 'No players found.',
        ], 404);
    }

    $totalSuccessRate = 0;
    $totalPlayers = $players->count();

    foreach ($players as $player) {
       
        $gamesCount = $player->games->count();
        $winsCount = $player->games->where('win', true)->count(); 
        if ($gamesCount > 0) {
           
            $playerSuccessRate = ($winsCount / $gamesCount) * 100;
        } else {
            $playerSuccessRate = 0;
        }
         $totalSuccessRate += $playerSuccessRate;
    }

    $averageSuccessRate = $totalSuccessRate / $totalPlayers;

    return response()->json([
        'status' => 'success',
        'message' => 'Ranking calculated successfully.',
        'average_success_rate' => $averageSuccessRate,
    ]);
    }
    public function worstPlayer(){
        $user = User::all();
        if ($user->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No user found.',
            ], 404);
        }
        $worstPlayer= User::orderBy('success_rate')->first();
        return response()->json([
            'status' => 'success',
            'message' => 'Worst player found successfully.',
            'worst_player' => $worstPlayer->name]);
    }
    public function bestPlayer(){
        $user = User::all();
        if ($user->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No user found.',
            ], 404);
            $bestPlayer= User::orderByDesc('success_rate')->first();
        return response()->json([
            'status' => 'success',
            'message' => 'Worst player found successfully.',
            'worst_player' => $bestPlayer->name]);
    }
}
}
