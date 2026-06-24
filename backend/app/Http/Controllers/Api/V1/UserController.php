<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        $users = User::latest()->get(['id', 'name', 'email', 'role', 'is_active', 'created_at']);

        return response()->json(['data' => $users]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'is_active' => true,
        ]);

        AuditLogger::log('create_user', $user, null, $user->only(['name', 'email', 'role']));

        return response()->json(['data' => $user->only(['id', 'name', 'email', 'role', 'is_active', 'created_at'])], 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json(['data' => $user->only(['id', 'name', 'email', 'role', 'is_active', 'created_at'])]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $old  = $user->only(['name', 'email', 'role', 'is_active']);
        $data = [
            'name'      => $request->name,
            'email'     => $request->email,
            'role'      => $request->role,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        AuditLogger::log('update_user', $user, $old, $user->fresh()->only(['name', 'email', 'role', 'is_active']));

        return response()->json(['data' => $user->only(['id', 'name', 'email', 'role', 'is_active'])]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        AuditLogger::log('delete_user', $user, $user->only(['name', 'email', 'role']));
        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    public function toggleActive(User $user): JsonResponse
    {
        $this->authorize('update', $user);
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';
        AuditLogger::log("user_{$status}", $user);

        return response()->json(['is_active' => $user->is_active, 'message' => "User {$status} successfully."]);
    }
}
