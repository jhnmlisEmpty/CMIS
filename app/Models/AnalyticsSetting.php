<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsSetting extends Model
{
    protected $fillable = [
        'low_score_threshold', 'low_attendance_threshold', 'rolling_days', 'minimum_required_events',
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'low_score_threshold' => 60,
            'low_attendance_threshold' => 60,
            'rolling_days' => 90,
            'minimum_required_events' => 3,
        ]);
    }
}
