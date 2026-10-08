<?php

namespace App\Support;

final readonly class PermissionResolution
{
    /**
     * @param  array<string, string>  $requested
     * @param  array<string, string>  $effective
     * @param  list<array{permission: string, from: string, to: string, required_by: list<string>}>  $adjustments
     */
    public function __construct(
        public array $requested,
        public array $effective,
        public array $adjustments,
    ) {}

    public function hasAdjustments(): bool
    {
        return $this->adjustments !== [];
    }
}
