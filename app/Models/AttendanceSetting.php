<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    use Auditable;

    protected $fillable = ['attendance_required', 'reward_points', 'penalty_points', 'audience_mode', 'role_audience', 'small_group_audience'];

    protected function casts(): array
    {
        return ['attendance_required' => 'boolean', 'role_audience' => 'array', 'small_group_audience' => 'array'];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'attendance_required' => false,
            'reward_points' => 10,
            'penalty_points' => 10,
            'audience_mode' => 'selected',
            'role_audience' => [],
            'small_group_audience' => [],
        ]);
    }
}
