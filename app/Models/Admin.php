<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Admin extends Authenticatable
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'its_id',
        'role_id',
        'created_by',
        'status',
    ];
    /**
     * Get the role that the admin belongs to.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
