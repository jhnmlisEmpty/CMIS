<?php

namespace Tests\Feature;

use App\Livewire\Attendance\CreateEvent;
use App\Livewire\Attendance\IndexEvent;
use App\Livewire\Components\UsersMap;
use App\Livewire\Lessons\ManageLessons;
use App\Livewire\User\IndexUser;
use App\Livewire\User\UpdateUser;
use App\Models\Event;
use App\Models\Lesson;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SystemWideTaggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_names_are_reusable_within_separate_record_libraries(): void
    {
        $memberTag = Tag::create(['type' => Tag::TYPE_MEMBER, 'name' => 'Youth', 'normalized_name' => 'youth']);
        $eventTag = Tag::create(['type' => Tag::TYPE_EVENT, 'name' => 'Youth', 'normalized_name' => 'youth']);
        $lessonTag = Tag::create(['type' => Tag::TYPE_LESSON, 'name' => 'Youth', 'normalized_name' => 'youth']);

        $this->assertNotSame($memberTag->id, $eventTag->id);
        $this->assertNotSame($eventTag->id, $lessonTag->id);
        $this->assertSame(3, Tag::where('normalized_name', 'youth')->count());
    }

    public function test_staff_can_manage_member_tags_but_members_can_only_view_them(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $memberTag = Tag::create(['type' => Tag::TYPE_MEMBER, 'name' => 'Volunteer', 'normalized_name' => 'volunteer']);
        $eventTag = Tag::create(['type' => Tag::TYPE_EVENT, 'name' => 'Volunteer', 'normalized_name' => 'volunteer']);

        Livewire::actingAs($admin)
            ->test(UpdateUser::class, ['user' => $member])
            ->set('tag_ids', [$eventTag->id])
            ->call('save')
            ->assertHasErrors(['tag_ids.0'])
            ->set('tag_ids', [$memberTag->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals([$memberTag->id], $member->fresh()->tags()->pluck('tags.id')->all());

        $this->actingAs($member)->get(route('profile'))->assertOk()->assertSee('Volunteer');
        $this->actingAs($member)->get(route('profile.edit'))->assertOk()->assertDontSee('Member tags');
    }

    public function test_member_directory_and_map_search_and_filter_by_member_tags(): void
    {
        $admin = User::factory()->admin()->create();
        $volunteerTag = Tag::create(['type' => Tag::TYPE_MEMBER, 'name' => 'Volunteer', 'normalized_name' => 'volunteer']);
        $volunteer = User::factory()->create(['name' => 'Tagged Person']);
        $volunteer->tags()->attach($volunteerTag);
        $otherMember = User::factory()->create(['name' => 'Other Person']);

        Livewire::actingAs($admin)
            ->test(IndexUser::class)
            ->set('search', 'Volunteer')
            ->assertSee('Tags')
            ->assertSee('Volunteer')
            ->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$volunteer->id])
            ->set('search', '')
            ->set('tagFilter', (string) $volunteerTag->id)
            ->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$volunteer->id]);

        Livewire::actingAs($admin)
            ->test(UsersMap::class)
            ->set('statusFilter', '')
            ->set('tagFilter', (string) $volunteerTag->id)
            ->assertViewHas('users', fn ($users) => $users->pluck('id')->contains($volunteer->id)
                && ! $users->pluck('id')->contains($otherMember->id));
    }

    public function test_event_tags_are_searchable_without_changing_attendance_audience_rules(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateEvent::class)
            ->set('title', 'Community Day')
            ->set('description', 'Serve the neighborhood')
            ->set('event_date', now()->addWeek()->toDateString())
            ->set('location', 'Church Grounds')
            ->set('event_type', 'Outreach')
            ->set('tagSearch', 'Community')
            ->call('addTag')
            ->call('submit')
            ->assertHasNoErrors();

        $event = Event::where('title', 'Community Day')->firstOrFail();
        $tag = Tag::forType(Tag::TYPE_EVENT)->where('normalized_name', 'community')->firstOrFail();
        $this->assertEquals([$tag->id], $event->tags()->pluck('tags.id')->all());
        $this->assertFalse($event->attendance_required);
        $this->assertSame(0, $event->audienceRules()->count());

        Livewire::actingAs($admin)
            ->test(IndexEvent::class)
            ->set('tagFilter', (string) $tag->id)
            ->assertSee('Tags')
            ->assertSee('Community')
            ->assertViewHas('events', fn ($events) => $events->pluck('id')->all() === [$event->id]);
    }

    public function test_lesson_tags_filter_the_library_and_form_state_does_not_leak(): void
    {
        $admin = User::factory()->admin()->create();
        $foundations = Tag::create(['type' => Tag::TYPE_LESSON, 'name' => 'Foundations', 'normalized_name' => 'foundations']);
        $lesson = Lesson::create(['title' => 'Faith Basics', 'order' => 1, 'status' => Lesson::STATUS_DRAFT]);
        $lesson->tags()->attach($foundations);
        $otherLesson = Lesson::create(['title' => 'Serving Others', 'order' => 2, 'status' => Lesson::STATUS_DRAFT]);

        $component = Livewire::actingAs($admin)
            ->test(ManageLessons::class)
            ->set('search', 'Foundations')
            ->assertViewHas('lessons', fn ($lessons) => $lessons->pluck('id')->all() === [$lesson->id])
            ->set('search', '')
            ->set('tagFilter', (string) $foundations->id)
            ->assertViewHas('lessons', fn ($lessons) => $lessons->pluck('id')->all() === [$lesson->id])
            ->call('editLesson', $lesson->id)
            ->assertSet('tag_ids', [$foundations->id])
            ->set('tagSearch', 'Discipleship')
            ->call('addTag')
            ->assertSet('pending_tags', ['Discipleship'])
            ->call('cancelForm')
            ->assertSet('tag_ids', [])
            ->assertSet('pending_tags', [])
            ->call('editLesson', $otherLesson->id)
            ->assertSet('tag_ids', [])
            ->assertSet('pending_tags', []);

        $this->assertDatabaseMissing('tags', ['type' => Tag::TYPE_LESSON, 'normalized_name' => 'discipleship']);
    }

    public function test_lesson_editor_creates_and_syncs_lesson_tags(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageLessons::class)
            ->call('showCreateForm')
            ->set('title', 'Prayer Foundations')
            ->set('description', 'A shared guide to prayer')
            ->set('order', 1)
            ->set('status', Lesson::STATUS_DRAFT)
            ->set('tagSearch', 'Prayer')
            ->call('addTag')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('tag_ids', [])
            ->assertSet('pending_tags', []);

        $lesson = Lesson::where('title', 'Prayer Foundations')->firstOrFail();
        $this->assertSame(['Prayer'], $lesson->tags()->pluck('name')->all());
        $this->assertDatabaseHas('tags', [
            'type' => Tag::TYPE_LESSON,
            'normalized_name' => 'prayer',
        ]);
    }
}
