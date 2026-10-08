<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    private const SENSITIVE_KEYS = ['password', 'remember_token', 'token', 'password_confirmation'];

    public function log(string $action, string $module, string $description, ?Model $auditable = null, array $old = [], array $new = [], ?int $actorId = null): AuditLog
    {
        return AuditLog::query()->create([
            'actor_id' => $actorId ?? auth()->id(),
            'action' => $action,
            'module' => $module,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'old_values' => $this->redact($old) ?: null,
            'new_values' => $this->redact($new) ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    private function redact(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array((string) $key, self::SENSITIVE_KEYS, true)) {
                $values[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $values[$key] = $this->redact($value);
            }
        }

        return $values;
    }
}
