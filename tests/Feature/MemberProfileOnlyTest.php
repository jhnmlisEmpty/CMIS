<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberProfileOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_and_edit_own_profile_but_is_forbidden_from_ungranted_pages(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $otherMember = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)->get(route('profile'))->assertOk();
        $this->actingAs($member)->get(route('profile.edit'))->assertOk();
        $this->actingAs($member)->get(route('home'))->assertForbidden();
        $this->actingAs($member)->get(route('users.index'))->assertForbidden();
        $this->actingAs($member)->get(route('users.show', $otherMember))->assertForbidden();
        $this->actingAs($member)->get(route('small-groups.index'))->assertForbidden();
        $this->actingAs($member)->get(route('events.index'))->assertForbidden();
    }

    public function test_staff_can_continue_to_access_workspace_pages(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('home'))->assertOk();
        $this->actingAs($admin)->get(route('users.index'))->assertOk();
    }
}
