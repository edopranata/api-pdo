<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserLoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->only([
            'username',
            'password',
        ]), [
            'username' => ['required', 'exists:users', 'max:255'],
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        }

        try {
            $user = User::query()->where('username', $request->username)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return response()->json([
                    'errors' => [
                        'password' => ['The provided credentials are incorrect.']
                    ],
                ], 422);
            }

            $token = $user->createToken($user->username)->plainTextToken;

            return response()->json([
                'token' => $token,
                'type' => 'Bearer',
            ], 201);

        } catch (\Exception $exception) {
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }
    }
}
