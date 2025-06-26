<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Mumineen extends Authenticatable
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'mumineen';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'its_id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     */
    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'its_id',
        'hof_id',
        'fullname',
        'gender',
        'age',
        'mobile',
        'country',
        'jamaat',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'its_id' => 'integer',
        'hof_id' => 'integer',
        'age' => 'integer',
    ];
}
