<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $email = $request->email;
        $password = $request->password;

        if (empty($email) || empty($password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Os campos obrigatórios devem ser preenchidos.'
            ]);
        }

        try {
            $client = new Client();

            return $client->post(config('service.passport.login_endpoint'), [
                'form_params' => [
                    'client_secret' => config('service.passport.client_secret'),
                    'client_id' => config('service.passport.client_id'),
                    'grant_type' => config('service.passport.grant_type'),
                    'username' => $email,
                    'password' => $password
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' =>  'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function register(Request $request)
    {
        $name = $request->name;
        $email = $request->email;
        $password = $request->password;

        if (empty($name) || empty($email) || empty($password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Os campos obrigatórios devem ser preenchidos.'
            ], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Você deve informar um e-mail válido.'
            ], 400);
        }

        if (strlen($password) < 6) {
            return response()->json([
                'status' => 'error',
                'message' => 'A senha deve ter no mínimo 6 caracteres.'
            ], 400);
        }

        if (User::where('email', '=', $email)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Já existe um usuário com esse e-mail.'
            ], 400);
        }

        try {
            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->password = app('hash')->make($password);

            if ($user->save()) {
                return $this->login($request);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        if (!auth()->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Não foram encontrados usuários autenticados.'
            ], 400);
        }

        try {
            auth()->user()->tokens()->each(function($token) {
                $token->delete();
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Usuário deslogado com sucesso.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
