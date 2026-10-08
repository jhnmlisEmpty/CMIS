<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use Auditable;

    protected $fillable = [
        'user_id',
        'event_id',
        'check_in_time',
        'source',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
    ];

    /**
     * Get the user associated with this attendance record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the event associated with this attendance record.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
