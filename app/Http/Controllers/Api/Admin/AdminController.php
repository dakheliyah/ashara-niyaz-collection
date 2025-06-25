<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
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
        $encryptedId = $this->encrypt($request->its_id);
        return response()->json(['encrypted_its_id' => $encryptedId]);
    }

    private function encrypt($value)
    {
        $key = env('ITS_ENCRYPTION_KEY');
        if (empty($key)) {
            throw new \Exception('ITS_ENCRYPTION_KEY is not set in the .env file.');
        }

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv . $encrypted);
    }
}
