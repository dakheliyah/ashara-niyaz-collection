<?php

namespace App\Http;

use App\Models\Admin;
use App\Models\Mumineen;

class AuthenticatedUser
{
    public $id;
    public $its_id;
    public $role;
    public $fullname;
    public $original;

    public function __construct($user)
    {
        $this->original = $user;

        if ($user instanceof Admin) {
            $this->id = $user->id;
            $this->its_id = $user->its_id;
            $this->role = $user->role->name; // 'admin' or 'collector'
            
            // Eager load mumineen relation to get fullname
            $mumin = Mumineen::where('its_id', $user->its_id)->first();
            $this->fullname = $mumin ? $mumin->fullname : 'N/A';

        } elseif ($user instanceof Mumineen) {
            $this->id = $user->id;
            $this->its_id = $user->its_id;
            $this->role = 'donor';
            $this->fullname = $user->fullname;
        }
    }

    public function __get($key)
    {
        return $this->original->$key;
    }
}
