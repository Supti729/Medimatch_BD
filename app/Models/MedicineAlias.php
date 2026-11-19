<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A brand name for a catalogue medicine, e.g. "Napa" for Paracetamol 500 mg tablet.
 */
class MedicineAlias extends Model
{
    protected $fillable = ['medicine_id', 'brand_name', 'manufacturer'];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}
