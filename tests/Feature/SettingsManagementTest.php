<?php

namespace Tests\Feature;

use App\Livewire\Attendance\CreateEvent;
use App\Livewire\Settings\ManageAttendanceDefaults;
use App\Livewire\Settings\ManageChurchProfile;
use App\Livewire\Settings\ManageTags;
use App\Models\AttendanceSetting;
use App\Models\AuditLog;
use App\Models\ChurchSetting;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_workspace_is_admin_only(): void
    {
        $member = User::factory()->create();

        foreach (['settings.profile', 'settings.attendance', 'settings.tags', 'settings.access-control', 'settings.audit-logs'] as $route) {
            $this->actingAs($member)->get(route($route))->assertForbidden();
        }
    }

    public function test_admin_can_update_church_profile_and_change_is_audited(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(ManageChurchProfile::class)
            ->set('name', 'True Vine Pangasinan')
            ->set('short_name', 'TV Pangasinan')
            ->set('email', 'office@example.test')
            ->set('website', 'https://example.test')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('True Vine Pangasinan', ChurchSetting::current()->name);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'module' => 'church_settings', 'action' => 'updated']);
    }

    public function test_attendance_defaults_are_applied_only_when_creating_a_new_event(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(ManageAttendanceDefaults::class)
            ->set('attendance_required', true)
            ->set('reward_points', 14)
            ->set('penalty_points', 6)
            ->set('audience_mode', 'all_active')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(AttendanceSetting::current()->attendance_required);
        Livewire::actingAs($admin)->test(CreateEvent::class)
            ->assertSet('attendance_required', true)
            ->assertSet('attendance_reward_points', 14)
            ->assertSet('absence_penalty_points', 6)
            ->assertSet('audience_all_active', true);
    }

    public function test_tag_merge_moves_assignments_and_only_unused_tags_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $source = Tag::create(['type' => Tag::TYPE_MEMBER, 'name' => 'Helpers', 'normalized_name' => 'helpers']);
        $target = Tag::create(['type' => Tag::TYPE_MEMBER, 'name' => 'Volunteers', 'normalized_name' => 'volunteers']);
        $member = User::factory()->create();
        $member->tags()->attach($source);

        Livewire::actingAs($admin)->test(ManageTags::class)
            ->set('type', Tag::TYPE_MEMBER)
            ->set('mergeSourceId', $source->id)
            ->set('mergeTargetId', $target->id)
            ->call('merge')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('tags', ['id' => $source->id]);
        $this->assertDatabaseHas('taggables', ['tag_id' => $target->id, 'taggable_id' => $member->id, 'taggable_type' => User::class]);

        Livewire::actingAs($admin)->test(ManageTags::class)
            ->set('type', Tag::TYPE_MEMBER)
            ->call('delete', $target->id)
            ->assertStatus(422);

        DB::table('taggables')->where('tag_id', $target->id)->delete();
        Livewire::actingAs($admin)->test(ManageTags::class)
            ->set('type', Tag::TYPE_MEMBER)
            ->call('delete', $target->id)
            ->assertHasNoErrors();
        $this->assertDatabaseMissing('tags', ['id' => $target->id]);
    }

    public function test_audit_log_records_authentication_and_is_visible_to_admin(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Audit Administrator', 'birthdate' => '1990-01-02']);

        $this->post(route('login.store'), ['name' => $admin->name, 'birthdate' => '1990-01-02'])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'login', 'module' => 'authentication']);
        $this->actingAs($admin)->get(route('settings.audit-logs'))->assertOk()->assertSee('Audit Administrator');
        $this->assertNotNull(AuditLog::where('action', 'login')->first());
    }
}
