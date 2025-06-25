<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Admin;
use App\Models\Event;
use App\Models\Donation;

class CollectorSession extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'its_id',
        'event_id',
        'session_date',
        'started_at',
        'ended_at',
        'status',
        'reconciliation_status',
        'reconciled_by',
        'reconciled_at',
    ];

    /**
     * Get the collector (admin) associated with this session.
     */
    public function collector()
    {
        return $this->belongsTo(Admin::class, 'its_id', 'its_id');
    }

    /**
     * Get the event associated with this session.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the donations recorded during this session.
     */
    public function donations()
    {
        return $this->hasMany(Donation::class, 'session_id');
    }
}
