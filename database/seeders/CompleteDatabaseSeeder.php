<?php

namespace Database\Seeders;

use App\Models\AnalyticsSetting;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventAttendanceExpectation;
use App\Models\EventAudienceRule;
use App\Models\Lesson;
use App\Models\MemberEngagementStat;
use App\Models\RolePermission;
use App\Models\SmallGroup;
use App\Models\SmallGroupMember;
use App\Models\SmallGroupMemberProgress;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompleteDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = $this->seedUsers();
        $this->seedRolePermissions();
        $groups = $this->seedSmallGroups($users);
        $lessons = $this->seedLessons();
        $memberships = $this->seedGroupMemberships($groups, $users);
        $this->seedLessonProgress($memberships, $lessons);
        $events = $this->seedEvents();
        $this->seedAttendanceData($events, $users, $groups);
        $this->seedAnalytics($users);
    }

    /** @return array<string, User> */
    private function seedUsers(): array
    {
        $accounts = [
            ['name' => 'Admin User', 'email' => 'admin@cmis.com', 'role' => User::ROLE_ADMIN, 'gender' => User::GENDER_MALE],
            ['name' => 'Pastor John', 'email' => 'pastor@cmis.com', 'role' => User::ROLE_PASTOR, 'gender' => User::GENDER_MALE],
            ['name' => 'Maria Santos', 'email' => 'ministry@cmis.com', 'role' => User::ROLE_MINISTRY_HEAD, 'gender' => User::GENDER_FEMALE],
            ['name' => 'Pedro Cruz', 'email' => 'leader@cmis.com', 'role' => User::ROLE_SMALL_GROUP_LEADER, 'gender' => User::GENDER_MALE],
        ];

        foreach (range(1, 16) as $number) {
            $accounts[] = [
                'name' => 'Church Member '.$number,
                'email' => sprintf('member%02d@cmis.com', $number),
                'role' => User::ROLE_MEMBER,
                'gender' => $number % 2 === 0 ? User::GENDER_FEMALE : User::GENDER_MALE,
            ];
        }

        $users = [];
        foreach ($accounts as $index => $account) {
            $users[$account['email']] = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $account['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'gender' => $account['gender'],
                    'birthdate' => now()->subYears(22 + ($index % 35))->toDateString(),
                    'phone' => '+63 917 555 '.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'address' => ($index + 1).' Faith Street, San Fernando, Pangasinan',
                    'region_code' => '010000000',
                    'province_code' => '015500000',
                    'city_code' => '015533000',
                    'barangay_code' => '015533001',
                    'street_address' => ($index + 1).' Faith Street',
                    'latitude' => 16.6159 + ($index * 0.001),
                    'longitude' => 120.3167 + ($index * 0.001),
                    'role' => $account['role'],
                    'status' => $index === 19 ? User::STATUS_INACTIVE : User::STATUS_ACTIVE,
                ],
            );
        }

        return $users;
    }

    private function seedRolePermissions(): void
    {
        $permissions = PermissionRegistry::keys();
        $rolePermissions = [
            User::ROLE_PASTOR => $permissions,
            User::ROLE_MINISTRY_HEAD => $permissions,
            User::ROLE_SMALL_GROUP_LEADER => [
                'dashboard.view', 'events.view', 'attendance.view', 'attendance.record',
                'small_groups.view', 'group_members.view', 'group_members.add',
                'group_members.remove', 'group_members.update_status', 'lessons.view',
                'lesson_progress.view', 'lesson_progress.update',
            ],
            User::ROLE_MEMBER => [
                'dashboard.view', 'events.view', 'attendance.view', 'small_groups.view',
                'group_members.view', 'lessons.view', 'lesson_progress.view',
            ],
        ];

        foreach ($rolePermissions as $role => $selectedPermissions) {
            foreach (PermissionRegistry::withDependencies($selectedPermissions) as $permission) {
                RolePermission::updateOrCreate(
                    ['role' => $role, 'permission' => $permission],
                    [],
                );
            }
        }
    }

    /** @param array<string, User> $users */
    /** @return array<string, SmallGroup> */
    private function seedSmallGroups(array $users): array
    {
        $groups = [];
        foreach ([
            ['name' => 'Faith Builders', 'leader' => 'leader@cmis.com', 'status' => SmallGroup::STATUS_ACTIVE],
            ['name' => 'Young Adults Fellowship', 'leader' => 'member01@cmis.com', 'status' => SmallGroup::STATUS_ACTIVE],
            ['name' => 'Hope Circle', 'leader' => 'member02@cmis.com', 'status' => SmallGroup::STATUS_INACTIVE],
        ] as $group) {
            $groups[$group['name']] = SmallGroup::updateOrCreate(
                ['name' => $group['name']],
                [
                    'description' => 'A welcoming small group for prayer, Bible study, and fellowship.',
                    'leader_id' => $users[$group['leader']]->id,
                    'status' => $group['status'],
                ],
            );
        }

        return $groups;
    }

    /** @return array<int, Lesson> */
    private function seedLessons(): array
    {
        $lessons = [];
        foreach ([
            ['title' => 'Knowing God', 'description' => 'Foundations of knowing God through Scripture.', 'status' => Lesson::STATUS_PUBLISHED],
            ['title' => 'Growing in Faith', 'description' => 'Practical habits for spiritual growth.', 'status' => Lesson::STATUS_PUBLISHED],
            ['title' => 'Serving with Purpose', 'description' => 'Discovering gifts and serving the church.', 'status' => Lesson::STATUS_PUBLISHED],
            ['title' => 'Living in Community', 'description' => 'Building healthy Christian relationships.', 'status' => Lesson::STATUS_DRAFT],
        ] as $order => $lesson) {
            $lessons[] = Lesson::updateOrCreate(
                ['title' => $lesson['title']],
                $lesson + ['order' => $order + 1, 'content' => 'Discussion guide and reflection questions for '.$lesson['title'].'.'],
            );
        }

        return $lessons;
    }

    /** @param array<string, SmallGroup> $groups */
    /** @param array<string, User> $users */
    /** @return array<int, SmallGroupMember> */
    private function seedGroupMemberships(array $groups, array $users): array
    {
        $memberships = [];
        $assignments = [
            'Faith Builders' => ['leader@cmis.com', 'member03@cmis.com', 'member04@cmis.com', 'member05@cmis.com', 'member06@cmis.com'],
            'Young Adults Fellowship' => ['member01@cmis.com', 'member07@cmis.com', 'member08@cmis.com', 'member09@cmis.com', 'member10@cmis.com'],
            'Hope Circle' => ['member02@cmis.com', 'member11@cmis.com', 'member12@cmis.com'],
        ];

        foreach ($assignments as $groupName => $emails) {
            foreach ($emails as $index => $email) {
                $memberships[] = SmallGroupMember::updateOrCreate(
                    ['small_group_id' => $groups[$groupName]->id, 'user_id' => $users[$email]->id],
                    [
                        'status' => $index === count($emails) - 1 && $groupName === 'Hope Circle'
                            ? SmallGroupMember::STATUS_INACTIVE
                            : SmallGroupMember::STATUS_ACTIVE,
                        'joined_at' => now()->subDays(30 + $index),
                    ],
                );
            }
        }

        return $memberships;
    }

    /** @param array<int, SmallGroupMember> $memberships */
    /** @param array<int, Lesson> $lessons */
    private function seedLessonProgress(array $memberships, array $lessons): void
    {
        foreach ($memberships as $memberIndex => $membership) {
            foreach ($lessons as $lessonIndex => $lesson) {
                $status = ($memberIndex + $lessonIndex) % 4 === 0
                    ? SmallGroupMemberProgress::STATUS_COMPLETED
                    : (($memberIndex + $lessonIndex) % 3 === 0
                        ? SmallGroupMemberProgress::STATUS_IN_PROGRESS
                        : SmallGroupMemberProgress::STATUS_NOT_STARTED);

                SmallGroupMemberProgress::updateOrCreate(
                    ['small_group_member_id' => $membership->id, 'lesson_id' => $lesson->id],
                    [
                        'status' => $status,
                        'completed_at' => $status === SmallGroupMemberProgress::STATUS_COMPLETED ? now()->subDays(3) : null,
                        'notes' => $status === SmallGroupMemberProgress::STATUS_COMPLETED ? 'Completed during group discussion.' : null,
                    ],
                );
            }
        }
    }

    /** @return array<string, Event> */
    private function seedEvents(): array
    {
        $events = [];
        foreach ([
            ['title' => 'Sunday Worship Service', 'days' => -14, 'type' => 'worship', 'required' => true, 'finalized' => true],
            ['title' => 'Community Prayer Night', 'days' => -7, 'type' => 'prayer', 'required' => true, 'finalized' => true],
            ['title' => 'Bible Study: Living by Faith', 'days' => -2, 'type' => 'study', 'required' => true, 'finalized' => false],
            ['title' => 'Youth Fellowship', 'days' => 7, 'type' => 'fellowship', 'required' => false, 'finalized' => false],
            ['title' => 'Church Anniversary Celebration', 'days' => 21, 'type' => 'celebration', 'required' => true, 'finalized' => false],
        ] as $event) {
            $events[$event['title']] = Event::updateOrCreate(
                ['title' => $event['title']],
                [
                    'description' => 'A church-wide gathering for worship, growth, and fellowship.',
                    'event_date' => now()->addDays($event['days'])->toDateString(),
                    'location' => 'True Vine World Harvest Church',
                    'event_type' => $event['type'],
                    'attendance_required' => $event['required'],
                    'attendance_reward_points' => 10,
                    'absence_penalty_points' => 5,
                    'audience_snapshotted_at' => $event['finalized'] ? now()->subDays(1) : null,
                    'attendance_finalized_at' => $event['finalized'] ? now()->subDays(1) : null,
                ],
            );
        }

        return $events;
    }

    /** @param array<string, Event> $events */
    /** @param array<string, User> $users */
    /** @param array<string, SmallGroup> $groups */
    private function seedAttendanceData(array $events, array $users, array $groups): void
    {
        foreach ($events as $event) {
            EventAudienceRule::updateOrCreate(
                ['event_id' => $event->id, 'audience_type' => EventAudienceRule::TYPE_ALL_ACTIVE, 'audience_key' => null],
                [],
            );

            if (! $event->attendance_required) {
                continue;
            }

            foreach (array_values(array_filter($users, fn (User $user) => $user->isActive())) as $index => $user) {
                $present = $event->event_date->isPast() && $index % 4 !== 0;
                $expectation = EventAttendanceExpectation::updateOrCreate(
                    ['event_id' => $event->id, 'user_id' => $user->id],
                    [
                        'status' => $present ? EventAttendanceExpectation::STATUS_PRESENT : ($event->event_date->isPast() ? EventAttendanceExpectation::STATUS_ABSENT : EventAttendanceExpectation::STATUS_PENDING),
                        'reward_points' => $event->attendance_reward_points,
                        'penalty_points' => $event->absence_penalty_points,
                        'points_delta' => $present ? $event->attendance_reward_points : ($event->event_date->isPast() ? -$event->absence_penalty_points : null),
                        'score_after' => $present ? 100 : ($event->event_date->isPast() ? 95 : null),
                        'finalized_at' => $event->attendance_finalized_at,
                    ],
                );

                if ($present) {
                    Attendance::updateOrCreate(
                        ['user_id' => $user->id, 'event_id' => $event->id],
                        ['check_in_time' => $event->event_date->copy()->setTime(9, 0), 'source' => $index % 2 === 0 ? 'qr_scan' : 'manual'],
                    );
                }

                if ($index % 3 === 0) {
                    DB::table('event_attendance_expectation_groups')->updateOrInsert(
                        ['expectation_id' => $expectation->id, 'small_group_id' => array_values($groups)[$index % count($groups)]->id],
                        [],
                    );
                }
            }
        }
    }

    /** @param array<string, User> $users */
    private function seedAnalytics(array $users): void
    {
        $settings = AnalyticsSetting::query()->firstOrCreate([]);
        $settings->update([
            'low_score_threshold' => 60,
            'low_attendance_threshold' => 60,
            'rolling_days' => 90,
            'minimum_required_events' => 3,
        ]);

        foreach ($users as $user) {
            $required = EventAttendanceExpectation::where('user_id', $user->id)->count();
            $attended = EventAttendanceExpectation::where('user_id', $user->id)->where('status', EventAttendanceExpectation::STATUS_PRESENT)->count();
            $missed = EventAttendanceExpectation::where('user_id', $user->id)->where('status', EventAttendanceExpectation::STATUS_ABSENT)->count();

            MemberEngagementStat::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'current_score' => $required > 0 ? max(0, min(100, (int) round(($attended / $required) * 100))) : 100,
                    'current_streak' => $attended,
                    'longest_streak' => $attended,
                    'required_events' => $required,
                    'attended_events' => $attended,
                    'missed_events' => $missed,
                    'last_calculated_at' => now(),
                ],
            );
        }
    }
}
