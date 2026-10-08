<?php

namespace Tests\Feature;

use App\Livewire\Attendance\ViewEvent;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\SmallGroup;
use App\Models\SmallGroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceCheckInExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_the_filtered_check_in_list_as_csv(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::create([
            'title' => 'Sunday Worship',
            'event_date' => '2026-10-04',
            'location' => 'Main hall',
            'event_type' => 'service',
        ]);
        $included = User::factory()->create([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'phone' => '09171234567',
            'role' => User::ROLE_MEMBER,
        ]);
        $excluded = User::factory()->create(['name' => 'Jose Ramos']);
        $group = SmallGroup::create(['name' => 'Faith Builders', 'status' => SmallGroup::STATUS_ACTIVE]);
        $group->leaders()->attach($admin);
        SmallGroupMember::create([
            'small_group_id' => $group->id,
            'user_id' => $included->id,
            'status' => SmallGroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
        Attendance::create([
            'event_id' => $event->id,
            'user_id' => $included->id,
            'check_in_time' => Carbon::parse('2026-10-04 09:15:00'),
            'source' => 'qr_scan',
        ]);
        Attendance::create([
            'event_id' => $event->id,
            'user_id' => $excluded->id,
            'check_in_time' => Carbon::parse('2026-10-04 09:20:00'),
            'source' => 'manual',
        ]);

        Livewire::actingAs($admin)
            ->test(ViewEvent::class, ['event' => $event])
            ->set('attendanceSearch', 'Maria')
            ->call('downloadCheckIns')
            ->assertFileDownloaded(
                'sunday-worship-2026-10-04-check-ins.csv',
                "\xEF\xBB\xBF\"Member name\",Email,Role,\"Small groups\",Phone,\"Check-in date\",\"Check-in time\",Source\n\"Maria Santos\",maria@example.com,Member,\"Faith Builders\",09171234567,2026-10-04,\"9:15 AM\",\"Qr Scan\"\n",
                'text/csv; charset=UTF-8',
            );
    }
}
