<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class AuthenticationController extends Controller
{
    /** register new account */
    public function register(Request $request)
    {
       
        $request->validate([
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|min:8',
        ]);
        
        $name = $request->name ?? 'anonimo';
        
        if ($name !== 'anonimo') {
            $request->validate([
                'name' => 'string|unique:users,name',
            ]);
        }
        
        // Crear el usuario
        $user = new User();
        $user->name     = $name; // Usa el nombre predeterminado o el proporcionado
        $user->email    = $request->email;
        $user->password = Hash::make($request->password);
              $user->assignRole('player');
        $user->save();
        
        $data = [];
        $data['response_code']  = '200';
        $data['status']         = 'success';
        $data['message']        = 'success Register';
        return response()->json($user);
    }

    /**
     * Login Req
     */
    public function login (Request $request){
        $request->validate([
            'email' => 'required|email|string',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();
        if(!empty($user)){

            if(Hash::check($request->password, $user->password)){

              $token = $user->createToken('myToken')->accessToken;
                return response()->json([
                    'status' => true,
                    'message' => 'Login succesful',
                    'token' => $token,
                    'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    ],
                    
                    ],200);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials', 
                    'data' => [],
                ], 401);
            }
        }
    }
    public function updateUser(Request $request, $id)
    {

        $userToUpdate = User::find($id);
        if (!$userToUpdate) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);}
        
        $authUser = $request->user();
        
       if ($authUser->id !== $userToUpdate->id) {
            return response()->json([
                'message' => "You cannot modify another user's name."
            ], 403);
        } 
        
        // Validar los datos recibidos
         $request->validate([
            'name' => 'nullable|string|max:255',
            
        ]);

        $newName = empty($request->name) ? 'anónimo' : $request->name;

        if($newName !== 'anónimo') {
            $existingUser = User::where('name', $newName)->first();
            if ($existingUser && $existingUser->id !== $userToUpdate->id) {
                return response()->json([
                    'message' => 'The name is already in use. Please choose another one.'
                ], 400);
            }
            $request->validate([
                'name' => 'unique:users,name',
            ]);
        }

        $userToUpdate->name = $newName;
        $userToUpdate->save();

        return response()->json([
            'status' => true,
            'message' => 'Player updated successfully']);
    
    }
}
