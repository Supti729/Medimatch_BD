<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A medicine identity in the verified catalogue: generic name + strength + dosage form.
 */
class Medicine extends Model
{
    protected $fillable = ['generic_name', 'strength', 'dosage_form', 'requires_prescription', 'eligibility', 'restriction_reason'];

    protected function casts(): array
    {
        return ['requires_prescription' => 'boolean'];
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(MedicineAlias::class)->orderBy('brand_name');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(MedicineListing::class);
    }

    public function isListable(): bool
    {
        return $this->eligibility === 'allowed';
    }

    /** "Paracetamol 500 mg · Tablet" */
    public function label(): string
    {
        return "{$this->generic_name} {$this->strength} · {$this->dosage_form}";
    }
}
