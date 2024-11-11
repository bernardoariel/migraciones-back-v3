<?php

namespace App\Http\Controllers;

use App\Models\Notary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    // Método principal del controlador
    public function login(Request $request)
    {
        $currentTime = time();
        $expiryTime = $currentTime + 3600; // suma 3600 segundos al tiempo actual
        $expiryDate = number_format($expiryTime, 0, '', '');// convierte el timestamp en una fecha formateada

        // Validar los datos del formulario
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        // Buscar al usuario en la base de datos
        $user = Notary::where('email', $request->email)->first();
        // Verificar si el usuario existe y si la contraseña es correcta

        if ( $user ) {
            // Crear un nuevo token de acceso
            $userId = $user->id;
            // dd($userId);

            $accessToken = $user->createToken('authToken', ['user_id' => $userId])->accessToken;

            // Crear una respuesta JSON
            $response = response()->json([
                'user' => $user,
                'access_token' => $accessToken->token,
                'expiry_date' => $expiryDate,

            ], 200, [], JSON_PRETTY_PRINT)
            ->withHeaders([
                'expiry_date' => $expiryDate,
            ]);

            return $response;

            } else {

                // Devolver un mensaje de error si el usuario no existe o la contraseña es incorrecta
                return response()->json([
                    'error' => 'The email or password is incorrect',
                ], 401);

        }

}
public function registrarUsuario(Request $request){
    $usuario = User::create($request->all());
    return response($usuario,200);
}

}
