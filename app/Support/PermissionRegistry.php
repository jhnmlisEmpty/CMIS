<?php

namespace App\Support;

final class PermissionRegistry
{
    public const GROUPS = [
        'Dashboard' => [
            'dashboard.view' => 'View dashboard',
        ],
        'Analytics' => [
            'analytics.view' => 'View attendance and growth analytics',
            'analytics.manage_settings' => 'Manage analytics thresholds',
        ],
        'Members' => [
            'users.view' => 'View members',
            'users.create' => 'Create members',
            'users.update' => 'Edit members',
            'users.delete' => 'Delete members',
            'users.export' => 'Export member list',
            'users.map' => 'View member locations',
            'users.assign_roles' => 'Assign user roles',
        ],
        'Events' => [
            'events.view' => 'View events',
            'events.create' => 'Create events',
            'events.update' => 'Edit events',
            'events.delete' => 'Delete events',
        ],
        'Attendance' => [
            'attendance.view' => 'View attendance',
            'attendance.record' => 'Record attendance',
        ],
        'Small groups' => [
            'small_groups.view' => 'View small groups',
            'small_groups.create' => 'Create small groups',
            'small_groups.update' => 'Edit small groups',
            'small_groups.delete' => 'Delete small groups',
        ],
        'Group membership' => [
            'group_members.view' => 'View group members',
            'group_members.add' => 'Add members',
            'group_members.remove' => 'Remove members',
            'group_members.update_status' => 'Change membership status',
        ],
        'Lessons' => [
            'lessons.view' => 'View lessons',
            'lessons.create' => 'Create lessons',
            'lessons.update' => 'Edit lessons',
            'lessons.delete' => 'Delete lessons',
            'lessons.publish' => 'Publish or unpublish lessons',
        ],
        'Lesson progress' => [
            'lesson_progress.view' => 'View lesson progress',
            'lesson_progress.update' => 'Update lesson progress',
        ],
    ];

    public const DEPENDENCIES = [
        'users.create' => ['users.view'],
        'analytics.manage_settings' => ['analytics.view'],
        'users.update' => ['users.view'],
        'users.delete' => ['users.view'],
        'users.export' => ['users.view'],
        'users.map' => ['users.view'],
        'users.assign_roles' => ['users.view', 'users.update'],
        'events.create' => ['events.view'],
        'events.update' => ['events.view'],
        'events.delete' => ['events.view'],
        'attendance.view' => ['events.view'],
        'attendance.record' => ['events.view', 'attendance.view'],
        'small_groups.create' => ['small_groups.view'],
        'small_groups.update' => ['small_groups.view'],
        'small_groups.delete' => ['small_groups.view'],
        'group_members.view' => ['small_groups.view'],
        'group_members.add' => ['small_groups.view', 'group_members.view'],
        'group_members.remove' => ['small_groups.view', 'group_members.view'],
        'group_members.update_status' => ['small_groups.view', 'group_members.view'],
        'lessons.create' => ['lessons.view'],
        'lessons.update' => ['lessons.view'],
        'lessons.delete' => ['lessons.view'],
        'lessons.publish' => ['lessons.view', 'lessons.update'],
        'lesson_progress.view' => ['lessons.view', 'small_groups.view'],
        'lesson_progress.update' => ['lessons.view', 'small_groups.view', 'lesson_progress.view'],
    ];

    public static function keys(): array
    {
        return array_keys(array_merge(...array_values(self::GROUPS)));
    }

    public static function dependenciesFor(string $permission): array
    {
        return self::DEPENDENCIES[$permission] ?? [];
    }

    public static function withDependencies(array $permissions): array
    {
        $resolved = array_values(array_intersect(self::keys(), $permissions));

        do {
            $before = count($resolved);
            foreach ($resolved as $permission) {
                $resolved = array_merge($resolved, self::dependenciesFor($permission));
            }
            $resolved = array_values(array_unique($resolved));
        } while (count($resolved) !== $before);

        return $resolved;
    }

    public static function dependentsOf(string $permission): array
    {
        $dependents = [];
        foreach (self::DEPENDENCIES as $candidate => $dependencies) {
            if (in_array($permission, $dependencies, true)) {
                $dependents[] = $candidate;
                $dependents = array_merge($dependents, self::dependentsOf($candidate));
            }
        }

        return array_values(array_unique($dependents));
    }
}
