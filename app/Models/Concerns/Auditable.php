<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => app(AuditLogger::class)->log('created', $model->getTable(), class_basename($model).' created', $model, [], $model->getAttributes()));
        static::updated(fn (Model $model) => app(AuditLogger::class)->log('updated', $model->getTable(), class_basename($model).' updated', $model, $model->getOriginal(), $model->getChanges()));
        static::deleted(fn (Model $model) => app(AuditLogger::class)->log('deleted', $model->getTable(), class_basename($model).' deleted', $model, $model->getOriginal(), []));
    }
}
