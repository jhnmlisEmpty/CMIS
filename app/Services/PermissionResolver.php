<?php

namespace App\Services;

use App\Support\PermissionRegistry;
use App\Support\PermissionResolution;

class PermissionResolver
{
    /** @param array<string, string> $requested */
    public function resolve(array $requested): PermissionResolution
    {
        $normalized = [];
        $requestedRootScopes = [];
        foreach (PermissionRegistry::SCOPEABLE as $root) {
            if (isset($requested[$root]) && in_array($requested[$root], PermissionRegistry::SCOPES, true)) {
                $requestedRootScopes[$root] = $requested[$root];
            }
        }

        foreach ($requested as $permission => $scope) {
            if (! in_array($permission, PermissionRegistry::keys(), true)) {
                continue;
            }

            $root = PermissionRegistry::scopeRootFor($permission);
            $normalized[$permission] = $root
                ? ($requestedRootScopes[$root] ?? (in_array($scope, PermissionRegistry::SCOPES, true) ? $scope : PermissionRegistry::SCOPE_ASSOCIATED))
                : PermissionRegistry::SCOPE_ALL;
        }

        $effective = $normalized;
        do {
            $before = $effective;
            foreach ($effective as $permission => $scope) {
                foreach (PermissionRegistry::dependenciesFor($permission) as $dependency) {
                    $requiredScope = $this->requiredDependencyScope($permission, $dependency, $scope);
                    $effective[$dependency] = PermissionRegistry::broaderScope(
                        $effective[$dependency] ?? null,
                        $requiredScope,
                    );
                }
            }
        } while ($effective !== $before);

        $effective = $this->applyCoreScopes($effective);

        $adjustments = [];
        foreach ($effective as $permission => $scope) {
            $from = $normalized[$permission] ?? 'none';
            if ($from === $scope || (PermissionRegistry::scopeRootFor($permission) && ! PermissionRegistry::isScopeable($permission) && $from !== 'none')) {
                continue;
            }

            $adjustments[] = [
                'permission' => $permission,
                'from' => $from,
                'to' => $scope,
                'required_by' => $this->enabledRequirementsFor($permission, $effective),
            ];
        }

        return new PermissionResolution($normalized, $effective, $adjustments);
    }

    private function requiredDependencyScope(string $permission, string $dependency, string $scope): string
    {
        if (! PermissionRegistry::scopeRootFor($dependency)) {
            return PermissionRegistry::SCOPE_ALL;
        }

        if ($permission === 'group_members.add' && $dependency === 'users.view') {
            return PermissionRegistry::SCOPE_ALL;
        }

        return $scope;
    }

    /** @param array<string, string> $permissions */
    private function applyCoreScopes(array $permissions): array
    {
        foreach (PermissionRegistry::SCOPE_FAMILIES as $root => $family) {
            if (! isset($permissions[$root])) {
                continue;
            }

            foreach ($family as $permission) {
                if (isset($permissions[$permission])) {
                    $permissions[$permission] = $permissions[$root];
                }
            }
        }

        return $permissions;
    }

    /**
     * @param  array<string, string>  $effective
     * @return list<string>
     */
    private function enabledRequirementsFor(string $dependency, array $effective): array
    {
        return collect(array_keys($effective))
            ->filter(fn (string $permission): bool => in_array(
                $dependency,
                PermissionRegistry::dependenciesFor($permission),
                true,
            ))
            ->values()
            ->all();
    }
}
