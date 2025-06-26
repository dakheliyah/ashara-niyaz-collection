<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'collector_session_id',
        'donor_its_id',
        'whatsapp_number',
        'donation_type_id',
        'currency_id',
        'amount',
        'date',
        'collected_by',
        'receipt_url',
        'remarks',
    ];

    /**
     * Get the donation type that owns the donation.
     */
    public function donationType()
    {
        return $this->belongsTo(\App\Models\DonationType::class);
    }

    /**
     * Get the currency that owns the donation.
     */
    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class);
    }

    /**
     * Get the collector session that owns the donation.
     */
    public function collectorSession()
    {
        return $this->belongsTo(\App\Models\CollectorSession::class);
    }

    /**
     * Get the donor for the donation.
     */
    public function donor()
    {
        return $this->belongsTo(Mumineen::class, 'donor_its_id', 'its_id');
    }
}
