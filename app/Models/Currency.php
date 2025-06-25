<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'symbol',
    ];

    /**
     * Get the donations for the currency.
     */
    public function donations()
    {
        return $this->hasMany(\App\Models\Donation::class);
    }
}
