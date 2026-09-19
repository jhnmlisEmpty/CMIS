<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAudienceRule extends Model
{
    public const TYPE_ALL_ACTIVE = 'all_active';
    public const TYPE_ROLE = 'role';
    public const TYPE_SMALL_GROUP = 'small_group';
    public const TYPE_USER = 'user';

    protected $fillable = ['event_id', 'audience_type', 'audience_key'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
