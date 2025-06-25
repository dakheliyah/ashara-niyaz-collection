<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonationType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the donations for the donation type.
     */
    public function donations()
    {
        return $this->hasMany(\App\Models\Donation::class);
    }
}
