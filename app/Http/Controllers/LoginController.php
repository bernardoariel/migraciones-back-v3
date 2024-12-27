<?php
namespace App\Http\Controllers;

use App\Models\Notary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // Validar los datos del formulario
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Buscar al usuario en la base de datos
        $user = Notary::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            // Generar un token de acceso
            $accessToken = $user->createToken('authToken')->plainTextToken;

            return response()->json([
                'user' => $user,
                'access_token' => $accessToken,
                'expiry_date' => now()->addHour()->toDateTimeString(),
            ], 200);
        } else {
            return response()->json([
                'error' => 'The email or password is incorrect',
            ], 401);
        }
    }
    public function forgotPassword(Request $request)
{
    // Validar el campo "identifier" (puede ser email o matrícula)
    $validator = Validator::make($request->all(), [
        'identifier' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 422);
    }

    // Buscar al usuario por email o matrícula
    $identifier = $request->identifier;
    $user = Notary::where('email', $identifier)
        ->orWhere('matricula', $identifier)
        ->first();

    if (!$user) {
        return response()->json([
            'error' => 'No se encontró ningún usuario con ese email o matrícula.',
        ], 404);
    }

    // Generar una nueva contraseña aleatoria
    $newPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);

    // Actualizar la contraseña en la base de datos (encriptada con hash)
    $user->password = Hash::make($newPassword);
    $user->save();

    // Generar el contenido del correo como cadena HTML
    $htmlContent = "
        <h1>Hola {$user->nombre} {$user->apellido}</h1>
        <p>Hemos generado una nueva contraseña para tu cuenta:</p>
        <p><strong>Contraseña: {$newPassword}</strong></p>
        <p>Por favor, inicia sesión con esta nueva contraseña y cámbiala lo antes posible.</p>
        <br>
        <p>Atentamente,</p>
        <p>El equipo de Escribanía</p>
    ";

    // Enviar el correo electrónico con la nueva contraseña
    try {
        Mail::html($htmlContent, function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Recuperación de Contraseña');
        });

        return response()->json([
            'message' => 'Se envió un correo con la nueva contraseña.',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'No se pudo enviar el correo. Intente nuevamente más tarde.',
        ], 500);
    }
}

    public function me(Request $request)
    {
        $user = $request->user(); 
        return response()->json(['user' => $user], 200);
    }
    public function updatePassword(Request $request)
    {
        // Validar los datos del formulario
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:notaries,email',
            'password' => 'required|min:6',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
    
        // Buscar al usuario por su email
        $user = Notary::where('email', $request->email)->first();
    
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }
    
        // Hashear la contraseña y actualizarla
        $user->password = Hash::make($request->password);
        $user->save();
    
        return response()->json(['message' => 'Contraseña actualizada correctamente'], 200);
    }
    
}
