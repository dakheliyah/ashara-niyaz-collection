<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Role;
use App\Services\ItsTokenCipher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * Fetch all roles.
     */
    public function getRoles()
    {
        return response()->json(Role::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'its_id' => 'required|unique:admins,its_id|digits:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        $admin = Admin::create([
            'its_id' => $request->input('its_id'),
            'role_id' => $request->input('role_id'),
            'created_by' => $request->attributes->get('its_id'),
        ]);

        return response()->json($admin->load('role'), 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Admin $admin)
    {
        $request->validate([
            'its_id' => ['required', 'digits:8', Rule::unique('admins')->ignore($admin->id)],
            'role_id' => 'required|exists:roles,id',
        ]);

        $admin->update($request->only('its_id', 'role_id'));

        return response()->json($admin->load('role'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin)
    {
        $admin->delete();

        return response()->json(null, 204);
    }

    public function encryptItsId(Request $request)
    {
        $request->validate(['its_id' => 'required|digits:8']);

        return response()->json([
            'encrypted_its_id' => ItsTokenCipher::encrypt($request->its_id),
        ]);
    }
}
