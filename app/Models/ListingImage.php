<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ListingImage extends Model
{
    protected $fillable = ['path', 'position'];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MedicineListing::class, 'medicine_listing_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
