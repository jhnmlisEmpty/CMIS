<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventAttendanceExpectation;
use App\Models\EventAudienceRule;
use App\Models\MemberEngagementStat;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceAnalyticsService
{
    public function processDueEvents(): array
    {
        $snapshotted = 0;
        $finalized = 0;

        Event::query()
            ->where('attendance_required', true)
            ->whereNull('audience_snapshotted_at')
            ->whereDate('event_date', '<=', today())
            ->orderBy('event_date')
            ->each(function (Event $event) use (&$snapshotted): void {
                $this->snapshotEvent($event);
                $snapshotted++;
            });

        Event::query()
            ->where('attendance_required', true)
            ->whereNull('attendance_finalized_at')
            ->whereDate('event_date', '<', today())
            ->orderBy('event_date')
            ->each(function (Event $event) use (&$finalized): void {
                $this->finalizeEvent($event);
                $finalized++;
            });

        return compact('snapshotted', 'finalized');
    }

    public function snapshotEvent(Event $event): void
    {
        if (! $event->attendance_required || $event->audience_snapshotted_at) {
            return;
        }

        DB::transaction(function () use ($event): void {
            $lockedEvent = Event::query()->lockForUpdate()->findOrFail($event->id);
            if ($lockedEvent->audience_snapshotted_at) {
                return;
            }

            $memberIds = $this->resolveAudience($lockedEvent);
            $presentIds = $lockedEvent->attendances()->whereIn('user_id', $memberIds)->pluck('user_id')->all();

            foreach ($memberIds as $userId) {
                $expectation = EventAttendanceExpectation::firstOrCreate(
                    ['event_id' => $lockedEvent->id, 'user_id' => $userId],
                    [
                        'status' => in_array($userId, $presentIds, true)
                            ? EventAttendanceExpectation::STATUS_PRESENT
                            : EventAttendanceExpectation::STATUS_PENDING,
                        'reward_points' => $lockedEvent->attendance_reward_points,
                        'penalty_points' => $lockedEvent->absence_penalty_points,
                    ],
                );

                $groupIds = User::find($userId)?->smallGroupMemberships()
                    ->where('status', 'active')
                    ->whereHas('smallGroup', fn ($query) => $query->where('status', 'active'))
                    ->pluck('small_group_id')
                    ->all() ?? [];
                $expectation->groups()->syncWithoutDetaching($groupIds);
            }

            $lockedEvent->update(['audience_snapshotted_at' => now()]);
        });
    }

    public function recordCheckIn(Event $event, int $userId, string $source): void
    {
        if ($event->attendance_required && ! $event->audience_snapshotted_at) {
            $this->snapshotEvent($event);
        }

        $expectation = DB::transaction(function () use ($event, $userId, $source): ?EventAttendanceExpectation {
            $lockedEvent = Event::query()->lockForUpdate()->findOrFail($event->id);

            $lockedEvent->attendances()->firstOrCreate(
                ['user_id' => $userId],
                ['check_in_time' => now(), 'source' => $source],
            );

            if (! $lockedEvent->attendance_required) {
                return null;
            }

            $expectation = $lockedEvent->attendanceExpectations()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $expectation) {
                return null;
            }

            $expectation->update([
                'status' => EventAttendanceExpectation::STATUS_PRESENT,
                'finalized_at' => now(),
            ]);

            return $expectation;
        });

        if ($expectation) {
            $this->recalculateMember($userId);
        }
    }

    public function finalizeEvent(Event $event): void
    {
        if (! $event->attendance_required || $event->attendance_finalized_at) {
            return;
        }

        $this->snapshotEvent($event);

        $userIds = DB::transaction(function () use ($event): array {
            $lockedEvent = Event::query()->lockForUpdate()->findOrFail($event->id);
            if ($lockedEvent->attendance_finalized_at) {
                return [];
            }

            $presentIds = $lockedEvent->attendances()->pluck('user_id')->all();
            $expectations = $lockedEvent->attendanceExpectations()->lockForUpdate()->get();
            foreach ($expectations as $expectation) {
                $expectation->update([
                    'status' => in_array($expectation->user_id, $presentIds, true)
                        ? EventAttendanceExpectation::STATUS_PRESENT
                        : EventAttendanceExpectation::STATUS_ABSENT,
                    'finalized_at' => now(),
                ]);
            }

            $lockedEvent->update(['attendance_finalized_at' => now()]);

            return $expectations->pluck('user_id')->all();
        });

        foreach ($userIds as $userId) {
            $this->recalculateMember($userId);
        }
    }

    public function correctExpectation(EventAttendanceExpectation $expectation, string $status): void
    {
        abort_unless(in_array($status, [
            EventAttendanceExpectation::STATUS_PRESENT,
            EventAttendanceExpectation::STATUS_ABSENT,
        ], true), 422);
        abort_unless($expectation->event->attendance_finalized_at, 422, 'Attendance is not finalized yet.');

        if ($status === EventAttendanceExpectation::STATUS_PRESENT) {
            $expectation->event->attendances()->firstOrCreate(
                ['user_id' => $expectation->user_id],
                ['check_in_time' => now(), 'source' => 'correction'],
            );
        } else {
            $expectation->event->attendances()->where('user_id', $expectation->user_id)->delete();
        }
        $expectation->update(['status' => $status, 'finalized_at' => now()]);
        $this->recalculateMember($expectation->user_id);
    }

    public function recalculateMember(int $userId): MemberEngagementStat
    {
        return DB::transaction(function () use ($userId): MemberEngagementStat {
            $score = 100;
            $currentStreak = 0;
            $longestStreak = 0;
            $attended = 0;
            $missed = 0;

            $expectations = EventAttendanceExpectation::query()
                ->where('user_id', $userId)
                ->whereNotNull('finalized_at')
                ->whereIn('status', [EventAttendanceExpectation::STATUS_PRESENT, EventAttendanceExpectation::STATUS_ABSENT])
                ->with('event:id,event_date')
                ->get()
                ->sortBy(fn ($item) => $item->event->event_date->format('Y-m-d').'-'.str_pad((string) $item->event_id, 12, '0', STR_PAD_LEFT));

            foreach ($expectations as $expectation) {
                if ($expectation->status === EventAttendanceExpectation::STATUS_PRESENT) {
                    $delta = $expectation->reward_points;
                    $attended++;
                    $currentStreak++;
                    $longestStreak = max($longestStreak, $currentStreak);
                } else {
                    $delta = -$expectation->penalty_points;
                    $missed++;
                    $currentStreak = 0;
                }

                $score = max(0, min(100, $score + $delta));
                $expectation->update(['points_delta' => $delta, 'score_after' => $score]);
            }

            return MemberEngagementStat::updateOrCreate(
                ['user_id' => $userId],
                [
                    'current_score' => $score,
                    'current_streak' => $currentStreak,
                    'longest_streak' => $longestStreak,
                    'required_events' => $expectations->count(),
                    'attended_events' => $attended,
                    'missed_events' => $missed,
                    'last_calculated_at' => now(),
                ],
            );
        });
    }

    private function resolveAudience(Event $event): Collection
    {
        $rules = $event->audienceRules()->get();
        $query = User::query()->where('status', User::STATUS_ACTIVE)->where(function ($query) use ($rules): void {
            foreach ($rules as $rule) {
                if ($rule->audience_type === EventAudienceRule::TYPE_ALL_ACTIVE) {
                    $query->orWhereNotNull('id');
                } elseif ($rule->audience_type === EventAudienceRule::TYPE_ROLE) {
                    $query->orWhere('role', $rule->audience_key);
                } elseif ($rule->audience_type === EventAudienceRule::TYPE_USER) {
                    $query->orWhere('id', (int) $rule->audience_key);
                } elseif ($rule->audience_type === EventAudienceRule::TYPE_SMALL_GROUP) {
                    $query->orWhereHas('smallGroupMemberships', function ($membershipQuery) use ($rule): void {
                        $membershipQuery->where('small_group_id', (int) $rule->audience_key)
                            ->where('status', 'active');
                    });
                }
            }
        });

        return $rules->isEmpty() ? collect() : $query->pluck('id')->unique()->values();
    }
}
