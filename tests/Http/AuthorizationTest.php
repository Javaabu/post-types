<?php

namespace Javaabu\PostTypes\Tests\Http;

use Illuminate\Support\Facades\Gate;
use Javaabu\Permissions\Models\Permission;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Tests\TestCase;
use Javaabu\PostTypes\Tests\TestSupport\Http\CustomPostsController;
use Javaabu\PostTypes\Tests\TestSupport\Policies\DenyAllPolicy;
use PHPUnit\Framework\Attributes\Test;

class AuthorizationTest extends TestCase
{
    use InteractsWithPostTypes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPostTypes();
    }

    #[Test]
    public function it_registers_permissions_per_post_type_and_taxonomy(): void
    {
        $names = Permission::query()->where('guard_name', 'web')->pluck('name')->all();

        foreach (['view_news', 'edit_news', 'delete_news', 'force_delete_news', 'publish_news', 'view_pages', 'view_categories', 'edit_tags', 'delete_tags'] as $permission) {
            $this->assertContains($permission, $names);
        }

        $this->assertNotContains('edit_others_news', $names);
        $this->assertTrue(Permission::query()->where('guard_name', 'web_admin')->where('name', 'publish_news')->exists());
    }

    #[Test]
    public function it_needs_a_signed_in_user(): void
    {
        $this->get('/admin/news')->assertRedirect();
        $this->getJson('/admin/news')->assertUnauthorized();
    }

    #[Test]
    public function it_checks_each_ability(): void
    {
        $post = $this->makePost();

        $this->actingAs($this->user([]))->get('/admin/news')->assertForbidden();
        $this->actingAs($this->user(['view_news']))->get('/admin/news')->assertOk()->assertDontSee(url('admin/news/create'));
        $this->actingAs($this->user(['view_news']))->get('/admin/news/create')->assertForbidden();
        $this->actingAs($this->user(['view_news']))->get("/admin/news/{$post->id}/edit")->assertForbidden();
        $this->actingAs($this->user(['edit_news']))->delete("/admin/news/{$post->id}")->assertForbidden();
        $this->actingAs($this->user(['view_pages', 'edit_pages']))->get('/admin/news')->assertForbidden();

        $post->delete();

        $this->actingAs($this->user(['delete_news']))->delete("/admin/news/{$post->id}/force")->assertForbidden();
        $this->actingAs($this->user(['delete_news']))->post("/admin/news/{$post->id}/restore")->assertRedirect();
    }

    #[Test]
    public function it_works_for_another_guard(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'web_admin')
            ->post('/staff/news', ['title' => 'ސްޓާފް', 'status' => 'draft'])
            ->assertRedirect();

        $post = Post::query()->sole();

        $this->assertTrue($post->author->is($admin));
        $this->assertSame('admin', $post->author_type);

        $this->actingAs($admin, 'web_admin')->get("/staff/news/{$post->id}/edit")->assertOk();
    }

    #[Test]
    public function it_keeps_guards_apart(): void
    {
        // A web user isn't signed in to the staff (web_admin) routes
        $this->actingAs($this->user(), 'web')->get('/staff/news')->assertRedirect();
    }

    #[Test]
    public function it_limits_ownable_post_types_to_their_authors(): void
    {
        PostTypes::register(PostType::make('news')->ownable()->taxonomies(['categories', 'tags'])->permalink('{year}/{slug}'));
        $this->artisan('permissions:sync');

        $author = $this->user(['view_news', 'edit_news', 'delete_news']);
        $other = $this->user(['view_news', 'edit_news', 'delete_news']);
        $editor = $this->user(['view_news', 'edit_news', 'edit_others_news']);

        $post = $this->makePost();
        $post->author()->associate($author)->save();

        $this->actingAs($author)->get("/admin/news/{$post->id}/edit")->assertOk();
        $this->actingAs($other)->get("/admin/news/{$post->id}/edit")->assertForbidden();
        $this->actingAs($editor)->get("/admin/news/{$post->id}/edit")->assertOk();
        $this->actingAs($editor)->delete("/admin/news/{$post->id}")->assertForbidden();
    }

    #[Test]
    public function it_lets_the_app_override_the_policy(): void
    {
        Gate::policy(Post::class, DenyAllPolicy::class);
        Gate::policy(PostType::class, DenyAllPolicy::class);

        $this->actingAs($this->user())->get('/admin/news')->assertForbidden();
    }

    #[Test]
    public function it_lets_the_app_override_the_controller(): void
    {
        $this->app['router']->middleware('web')->group(function ($router) {
            $router->postTypesAdmin(['prefix' => 'custom', 'name' => 'custom.', 'only' => ['news'], 'controllers' => ['posts' => CustomPostsController::class]]);
        });

        $this->app['router']->getRoutes()->refreshNameLookups();

        $this->actingAs($this->user())->post('/custom/news', ['title' => 'ކަސްޓަމް', 'status' => 'draft'])->assertRedirect(url('custom/news/1/edit'));

        $this->assertSame([1], CustomPostsController::$saved);
        $this->assertFalse(app('router')->has('custom.page.index'));
    }
}
