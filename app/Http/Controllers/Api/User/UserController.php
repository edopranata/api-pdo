<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request): UserCollection
    {
        $query = User::query()
            ->whereNotIn('name', ['Administrator'])
            ->when($request->get('name'), function ($query, $search) {
                return $query->where('name', 'LIKE', "%$search%");
            })
            ->when($request->get('username'), function ($query, $search) {
                return $query->where('username', 'LIKE', "%$search%");
            })
            ->when($request->get('email'), function ($query, $search) {
                return $query->where('email', 'LIKE', "%$search%");
            })
            ->when($request->get('role'), function ($query, $search) {
                if ($search === 'All') {
                    return $query;
                } else {
                    return $query->whereHas('roles', function ($queryRole) use ($search) {
                        return $queryRole->where('name', $search);
                    });
                }
            })
            ->when($request->get('sortBy'), function ($query, $sort) {
                $sortBy = collect(json_decode($sort));
                return $query->orderBy($sortBy['key'], $sortBy['order']);
            });

        $data = $request->get('limit', 0) > 0 ? $query->paginate($request->get('limit', 10)) : $query->get();

        return new UserCollection($data);
    }

    public function destroy(User $user, Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            if ($user->id === '1') {
                throw new \Exception('Invalid user', '301');
            }
            $users = $request->user_id;
            if (is_array($users)) {
                User::query()
                    ->whereIn('id', $request->user_id)->delete();
            } else {
                $user->delete();
            }

            DB::commit();
            return response()->json(['status' => true], 201);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'error' => [
                    'code' => $exception->getCode(),
                    'massage' => $exception->getMessage()
                ]
            ], 301);
        }
    }

    public function update(Request $request, User $user)
    {
        DB::beginTransaction();
        try {
            // Batalkan apabila role user adalah Administrator
            if ($user->hasRole(['Administrator'])) {
                abort(401, 'Unauthenticated');
            }
            // Jika request ada parameter password
            if ($request->has('password')) {
                // Update password
                $validator = Validator::make($request->only(['password', 'password_confirmation']), [
                    'password' => 'required|confirmed|min:6|max:20'
                ]);
                if ($validator->fails()) {
                    return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
                }
                $user->update([
                    'password' => Hash::make($request->password)
                ]);

                DB::commit();
                return response()->json(['status' => true, 'message' => "Password has been change"], 201);

            } else {

                $validator = Validator::make($request->only(['name']), [
                    'name' => 'required|min:3|max:30',
                    'role' => 'require|exists,roles,name'
                ]);

                if ($validator->fails()) {
                    return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
                }

                $user->update($request->only('name'));

                // Jika role di tukar
                if ($request->has('role')) {
                    $user->syncRoles($request->role);
                }

                DB::commit();
                return response()->json(['status' => true, 'message' => "Data has been update"], 201);

            }
        } catch (\Exception $exception) {
            DB::rollBack();
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }

    }

    public function store(Request $request)
    {

        DB::beginTransaction();
        try {
            $validator = Validator::make($request->only([
                'name',
                'username',
                'email',
                'password',
                'password_confirmation',
                'role'
            ]), [
                'name' => 'required|string|min:3|max:30',
                'username' => 'required|string|min:6|max:20|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|confirmed|min:6|max:20',
                'role' => 'required|exists:roles,name|not_in:Administrator'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
            }

            $user = User::query()
                ->create([
                    'name' => $request->name,
                    'username' => $request->username,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);

            $user->assignRole($request->role);

            DB::commit();

            return new UserResource($user->load('roles'));

        } catch (\Exception $exception) {
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }
    }
}
