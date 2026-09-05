<?php
namespace App\Livewire\Attendance;

use Livewire\Component;
use App\Models\Event;
use App\Models\Attendance;
use App\Models\SmallGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app')]
#[Title('Event Details')]
class ViewEvent extends Component
{
    public Event $event;
    public string $scannedUuid = '';
    public string $searchName = '';
    public string $attendanceSearch = '';
    public string $attendanceRoleFilter = '';
    public string $attendanceSmallGroupFilter = '';
    public string $attendanceLocationFilter = '';
    public string $attendanceBirthdateFrom = '';
    public string $attendanceBirthdateTo = '';
    public string $attendanceMinAge = '';
    public string $attendanceMaxAge = '';
    public string $message = '';
    public string $messageType = ''; // 'success' or 'error'

    public function mount(Event $event): void
    {
        $this->event = $event;
    }

    /**
     * Get minute-by-minute attendance data for the chart
     */
    public function getAttendanceChartData()
    {
        $attendances = $this->getAttendances();

        // Group by minute
        $minuteData = [];
        foreach ($attendances as $attendance) {
            $minute = $attendance->check_in_time->format('H:i');
            if (!isset($minuteData[$minute])) {
                $minuteData[$minute] = 0;
            }
            $minuteData[$minute]++;
        }

        ksort($minuteData);

        return [
            'labels' => array_keys($minuteData),
            'data' => array_values($minuteData),
        ];
    }

    /**
     * Get attendance statistics
     */
    public function getAttendanceStats()
    {
        $attendances = $this->getAttendances();
        $count = $attendances->count();

        if ($count === 0) {
            return [
                'total' => 0,
                'earliest' => null,
                'latest' => null,
                'peakHour' => null,
            ];
        }

        $times = $attendances->pluck('check_in_time');
        $hours = $attendances->groupBy(function ($attendance) {
            return $attendance->check_in_time->format('H:00');
        })->map->count();

        return [
            'total' => $count,
            'earliest' => $times->min()->format('H:i'),
            'latest' => $times->max()->format('H:i'),
            'peakHour' => $hours->keys()->get($hours->keys()->search($hours->max())),
        ];
    }

    /**
     * Get attendances for the event with small group information
     */
    protected function attendanceAgeSqlExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "((CAST(strftime('%Y', 'now') AS INTEGER) - CAST(strftime('%Y', birthdate) AS INTEGER)) - (CASE WHEN strftime('%m-%d', 'now') < strftime('%m-%d', birthdate) THEN 1 ELSE 0 END))";
        }

        if ($driver === 'pgsql') {
            return "(EXTRACT(YEAR FROM AGE(CURRENT_DATE, birthdate)))";
        }

        return "(TIMESTAMPDIFF(YEAR, birthdate, CURDATE()))";
    }

    public function clearAttendanceFilters(): void
    {
        $this->reset([
            'attendanceSearch',
            'attendanceRoleFilter',
            'attendanceSmallGroupFilter',
            'attendanceLocationFilter',
            'attendanceBirthdateFrom',
            'attendanceBirthdateTo',
            'attendanceMinAge',
            'attendanceMaxAge',
        ]);
    }

    public function getAttendances()
    {
        $query = $this->event->attendances()
            ->with([
                'user',
                'user.smallGroups' => function ($query) {
                    $query->where('small_group_members.status', 'active')
                        ->where('small_groups.status', 'active');
                }
            ])
            ->whereHas('user', function ($userQuery) {
                $userQuery->when($this->attendanceSearch !== '', function ($q) {
                    $q->where(function ($inner) {
                        $inner->where('name', 'like', '%' . $this->attendanceSearch . '%')
                            ->orWhere('email', 'like', '%' . $this->attendanceSearch . '%')
                            ->orWhere('phone', 'like', '%' . $this->attendanceSearch . '%')
                            ->orWhere('address', 'like', '%' . $this->attendanceSearch . '%');
                    });
                })
                    ->when($this->attendanceRoleFilter !== '', fn ($q) => $q->where('role', $this->attendanceRoleFilter))
                    ->when($this->attendanceSmallGroupFilter !== '', function ($q) {
                        $q->whereHas('smallGroups', function ($groupQuery) {
                            $groupQuery->where('small_groups.id', $this->attendanceSmallGroupFilter)
                                ->where('small_group_members.status', 'active');
                        });
                    })
                    ->when($this->attendanceLocationFilter !== '', function ($q) {
                        $q->where(function ($inner) {
                            $inner->where('address', 'like', '%' . $this->attendanceLocationFilter . '%')
                                ->orWhere('street_address', 'like', '%' . $this->attendanceLocationFilter . '%')
                                ->orWhere('city_code', 'like', '%' . $this->attendanceLocationFilter . '%')
                                ->orWhere('province_code', 'like', '%' . $this->attendanceLocationFilter . '%')
                                ->orWhere('barangay_code', 'like', '%' . $this->attendanceLocationFilter . '%');
                        });
                    })
                    ->when($this->attendanceBirthdateFrom !== '', fn ($q) => $q->whereDate('birthdate', '>=', $this->attendanceBirthdateFrom))
                    ->when($this->attendanceBirthdateTo !== '', fn ($q) => $q->whereDate('birthdate', '<=', $this->attendanceBirthdateTo))
                    ->when($this->attendanceMinAge !== '' || $this->attendanceMaxAge !== '', function ($q) {
                        $q->whereNotNull('birthdate');

                        if ($this->attendanceMinAge !== '') {
                            $q->whereRaw($this->attendanceAgeSqlExpression() . ' >= ?', [(int) $this->attendanceMinAge]);
                        }

                        if ($this->attendanceMaxAge !== '') {
                            $q->whereRaw($this->attendanceAgeSqlExpression() . ' <= ?', [(int) $this->attendanceMaxAge]);
                        }
                    });
            })
            ->latest('check_in_time');

        return $query->get();
    }

    /**
     * Search for users by name
     */
    public function getSearchResults()
    {
        if (strlen(trim($this->searchName)) < 2) {
            return collect();
        }

        return User::where('name', 'like', '%' . $this->searchName . '%')
            ->where('status', 'active')
            ->limit(10)
            ->get();
    }

    /**
     * Check in a user by their ID (from search)
     */
    public function checkInByUserId($userId): void
    {
        $this->message = '';
        $this->messageType = '';

        try {
            $user = User::find($userId);

            if (!$user) {
                $this->messageType = 'error';
                $this->message = 'User not found.';
                return;
            }

            // Check if attendance already exists
            $existingAttendance = Attendance::where('user_id', $user->id)
                ->where('event_id', $this->event->id)
                ->first();

            if ($existingAttendance) {
                $this->messageType = 'error';
                $this->message = $user->name . ' is already checked in for this event.';
                $this->searchName = '';
                return;
            }

            // Create attendance record
            Attendance::create([
                'user_id' => $user->id,
                'event_id' => $this->event->id,
                'check_in_time' => now(),
                'source' => 'manual',
            ]);

            $this->messageType = 'success';
            $this->message = $user->name . ' has been checked in successfully.';
            $this->searchName = '';

        } catch (\Exception $e) {
            $this->messageType = 'error';
            $this->message = 'An error occurred while recording attendance: ' . $e->getMessage();
            $this->searchName = '';
        }
    }

    /**
     * Handle QR code scan input
     * Expects a UUID to be scanned
     */
    public function handleQrScan(): void
    {
        // Reset message
        $this->message = '';
        $this->messageType = '';

        // Validate that UUID is provided
        if (empty($this->scannedUuid)) {
            $this->messageType = 'error';
            $this->message = 'No UUID scanned. Please scan a valid QR code.';
            return;
        }

        try {
            // Find user by UUID
            $user = User::where('uuid', trim($this->scannedUuid))->first();
            
            if (!$user) {
                $this->messageType = 'error';
                $this->message = 'User not found. Invalid QR code.';
                $this->scannedUuid = '';
                return;
            }

            // Check if attendance already exists
            $existingAttendance = Attendance::where('user_id', $user->id)
                ->where('event_id', $this->event->id)
                ->first();

            if ($existingAttendance) {
                $this->messageType = 'error';
                $this->message = $user->name . ' is already checked in for this event.';
                $this->scannedUuid = '';
                return;
            }

            // Create attendance record
            Attendance::create([
                'user_id' => $user->id,
                'event_id' => $this->event->id,
                'check_in_time' => now(),
                'source' => 'qr_scan',
            ]);

            $this->messageType = 'success';
            $this->message = $user->name . ' has been checked in successfully.';
            $this->scannedUuid = '';

        } catch (\Exception $e) {
            $this->messageType = 'error';
            $this->message = 'An error occurred while recording attendance: ' . $e->getMessage();
            $this->scannedUuid = '';
        }
    }

    public function render()
    {
        return view('livewire.attendance.view-event', [
            'event' => $this->event,
            'attendanceRoles' => User::ROLES,
            'attendanceStatuses' => User::STATUSES,
            'smallGroups' => SmallGroup::query()->active()->orderBy('name')->get(),
        ]);
    }
}
