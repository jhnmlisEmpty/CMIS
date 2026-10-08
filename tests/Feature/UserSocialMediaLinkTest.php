<?php

namespace Tests\Feature;

use App\Livewire\User\EditProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserSocialMediaLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_add_a_social_media_link_to_their_profile(): void
    {
        $member = User::factory()->create([
            'role' => User::ROLE_MEMBER,
            'gender' => User::GENDER_FEMALE,
        ]);

        Livewire::actingAs($member)
            ->test(EditProfile::class)
            ->set('socialMediaUrl', 'https://instagram.com/member.profile')
            ->call('save')
            ->assertHasNoErrors();

        $member = $member->fresh();

        $this->assertSame('https://instagram.com/member.profile', $member->social_media_url);

        $this->actingAs($member)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('https://instagram.com/member.profile')
            ->assertSee('target="_blank"', false);
    }

    public function test_social_media_link_must_be_a_web_url(): void
    {
        $member = User::factory()->create([
            'role' => User::ROLE_MEMBER,
            'gender' => User::GENDER_MALE,
        ]);

        Livewire::actingAs($member)
            ->test(EditProfile::class)
            ->set('socialMediaUrl', 'instagram.com/member.profile')
            ->call('save')
            ->assertHasErrors(['socialMediaUrl' => 'url']);

        $this->assertNull($member->fresh()->social_media_url);
    }
}
