<?php

use Illuminate\Http\Request;
use App\Http\Controllers\API\AuthenticationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\GameController;
use Illuminate\Support\Facades\Auth;

Route::post("register", [AuthenticationController::class, "register"]);
Route::post("login", [AuthenticationController::class, "login"]);
Route::middleware('auth:api')->group(function () {
Route::put("/players/{id}", [AuthenticationController::class, 'updateUser']);
Route::post('/players/{id}/games', [Gamecontroller::class, 'rollDice']);
Route::delete('/players/{id}/games', [Gamecontroller::class, 'deleteGames']);
Route::get('/players/{id}/games', [Gamecontroller::class, 'listGames']);

});

//Route::middleware('auth:api', 'role:admin')->group(function (){
Route::get('/players', [Gamecontroller::class, 'listPlayers']);
    Route::get('/players/ranking', [Gamecontroller::class, 'ranking']);
    Route::get('/players/ranking/loser', [Gamecontroller::class, 'worstPlayer']);
    Route::get('/players/ranking/winner', [Gamecontroller::class, 'bestPlayer']);
//});