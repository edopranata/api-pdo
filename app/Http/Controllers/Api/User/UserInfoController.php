<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;

class UserInfoController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user()->load('cash'));
    }
}
