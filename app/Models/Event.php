<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }

    protected $fillable = [
        'title',
        'description',
        'event_date',
        'location',
        'event_type',
    ];

    /**
     * Get all attendance records for this event.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
