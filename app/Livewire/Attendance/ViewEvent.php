<?php
namespace App\Livewire\Attendance;

use Livewire\Component;
use App\Models\Event;
use App\Models\Attendance;
use App\Models\User;
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
        $attendances = $this->event->attendances()
            ->with('user')
            ->get();

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
    public function getAttendances()
    {
        return $this->event->attendances()
            ->with([
                'user',
                'user.smallGroups' => function ($query) {
                    $query->where('small_group_members.status', 'active')
                        ->where('small_groups.status', 'active');
                }
            ])
            ->latest('check_in_time')
            ->get();
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
        ]);
    }
}
