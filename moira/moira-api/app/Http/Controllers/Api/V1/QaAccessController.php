<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class QaAccessController extends Controller
{
    /** Hash bcrypt dummy (no corresponde a ninguna cuenta real): fuerza a que Hash::check()
     *  corra siempre, aunque el username no exista, para no filtrar por tiempo de respuesta
     *  qué usernames son válidos. */
    private const DUMMY_HASH = '$2y$12$eImiTXuWVxfM37uY4JANjQZeGX5G/Rk5rGnwEZ5MXWQIkKEnzY7CG';

    /**
     * Valida credenciales de un usuario del panel admin, para el gate de QA de moira-web.
     * No emite token ni sesión: solo confirma que el usuario y contraseña pertenecen
     * a una cuenta con acceso al panel.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::findByLogin($request->username);
        $passwordMatches = Hash::check($request->password, $user->password ?? self::DUMMY_HASH);

        if (! $user || ! $passwordMatches || ! $user->isEditor()) {
            return response()->json([
                'message' => 'Usuario o contraseña incorrectos.',
            ], 422);
        }

        return response()->json(['ok' => true]);
    }
}
