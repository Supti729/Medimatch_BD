<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    public const AUDIENCES = [
        'all' => 'All active users',
        'donors' => 'Users with active listings',
        'requesters' => 'Users with open requests',
        'partners' => 'Partner organizations',
    ];

    protected $guarded = ['*'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
