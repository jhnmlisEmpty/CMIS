<?php

namespace Tests\Feature;

use App\Livewire\SmallGroup\CreateSmallGroup;
use App\Livewire\SmallGroup\IndexSmallGroup;
use App\Livewire\SmallGroup\UpdateSmallGroup;
use App\Models\SmallGroup;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SmallGroupTaggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_editor_can_select_an_existing_tag_and_create_a_reusable_tag_on_save(): void
    {
        $admin = User::factory()->admin()->create();
        $leader = User::factory()->create();
        $families = Tag::create(['type' => Tag::TYPE_SMALL_GROUP, 'name' => 'Families', 'normalized_name' => 'families']);

        $component = Livewire::actingAs($admin)
            ->test(CreateSmallGroup::class)
            ->set('name', 'North District')
            ->set('leader_ids', [$leader->id])
            ->set('tag_ids', [$families->id])
            ->set('tagSearch', '  Young   Adults  ')
            ->call('addTag')
            ->assertSet('pending_tags', ['Young Adults'])
            ->assertSet('tag_ids', [$families->id]);

        $this->assertDatabaseMissing('tags', ['normalized_name' => 'young adults']);

        $component
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name'])
            ->assertSet('pending_tags', ['Young Adults'])
            ->assertSet('tag_ids', [$families->id]);

        $this->assertDatabaseMissing('tags', ['normalized_name' => 'young adults']);

        $component
            ->set('name', 'North District')
            ->call('save')
            ->assertHasNoErrors();

        $group = SmallGroup::where('name', 'North District')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            ['Families', 'Young Adults'],
            $group->tags()->pluck('name')->all(),
        );
        $this->assertDatabaseHas('tags', ['name' => 'Young Adults', 'normalized_name' => 'young adults']);
    }

    public function test_tag_names_are_case_insensitive_and_assignments_can_be_updated(): void
    {
        $admin = User::factory()->admin()->create();
        $leader = User::factory()->create();
        $youth = Tag::create(['type' => Tag::TYPE_SMALL_GROUP, 'name' => 'Youth', 'normalized_name' => 'youth']);
        $families = Tag::create(['type' => Tag::TYPE_SMALL_GROUP, 'name' => 'Families', 'normalized_name' => 'families']);
        $group = SmallGroup::create(['name' => 'Elevate', 'status' => SmallGroup::STATUS_ACTIVE]);
        $group->leaders()->attach($leader);
        $group->tags()->attach($families);

        Livewire::actingAs($admin)
            ->test(UpdateSmallGroup::class, ['smallGroup' => $group])
            ->set('tagSearch', '  YOUTH ')
            ->call('addTag')
            ->assertSet('pending_tags', [])
            ->assertSet('tag_ids', fn ($tagIds) => collect($tagIds)->map(fn ($id) => (int) $id)->contains($youth->id))
            ->set('tag_ids', [$youth->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, Tag::count());
        $this->assertEquals([$youth->id], $group->fresh()->tags()->pluck('tags.id')->all());
    }

    public function test_directory_searches_and_filters_groups_by_tag(): void
    {
        $admin = User::factory()->admin()->create();
        $leader = User::factory()->create();
        $youngAdults = Tag::create(['type' => Tag::TYPE_SMALL_GROUP, 'name' => 'Young Adults', 'normalized_name' => 'young adults']);
        $families = Tag::create(['type' => Tag::TYPE_SMALL_GROUP, 'name' => 'Families', 'normalized_name' => 'families']);
        $elevate = SmallGroup::create(['name' => 'Elevate', 'status' => SmallGroup::STATUS_ACTIVE]);
        $elevate->leaders()->attach($leader);
        $elevate->tags()->attach($youngAdults);
        $homeBuilders = SmallGroup::create(['name' => 'Home Builders', 'status' => SmallGroup::STATUS_ACTIVE]);
        $homeBuilders->leaders()->attach($leader);
        $homeBuilders->tags()->attach($families);

        Livewire::actingAs($admin)
            ->test(IndexSmallGroup::class)
            ->set('search', 'Young Adults')
            ->assertSee('Tags')
            ->assertSee('Young Adults')
            ->assertViewHas('smallGroups', fn ($groups) => $groups->pluck('id')->all() === [$elevate->id])
            ->set('search', '')
            ->set('tagFilter', (string) $families->id)
            ->assertViewHas('smallGroups', fn ($groups) => $groups->pluck('id')->all() === [$homeBuilders->id])
            ->call('clearFilters')
            ->assertSet('tagFilter', '');
    }

    public function test_user_without_group_edit_permission_cannot_change_tags(): void
    {
        $member = User::factory()->create();
        $group = SmallGroup::create(['name' => 'Protected Group', 'status' => SmallGroup::STATUS_ACTIVE]);

        $this->actingAs($member)
            ->get(route('small-groups.edit', $group))
            ->assertForbidden();
    }
}
