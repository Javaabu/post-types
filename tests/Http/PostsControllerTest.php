<?php

namespace Javaabu\PostTypes\Tests\Http;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Javaabu\BulkActions\Jobs\RunBulkAction;
use Javaabu\BulkActions\Models\BulkActionRun;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PostsControllerTest extends TestCase
{
    use InteractsWithPostTypes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPostTypes();
    }

    #[Test]
    public function it_registers_admin_routes_per_post_type(): void
    {
        $this->assertSame(url('admin/news'), route('admin.news.index'));
        $this->assertSame(url('staff/page/5/edit'), route('staff.page.edit', 5));
        $this->assertSame(url('admin/taxonomies/categories'), route('admin.taxonomies.categories.index'));
        $this->assertSame(url('admin/news/bulk'), route('admin.news.bulk'));
    }

    #[Test]
    public function it_lists_posts_with_status_tabs(): void
    {
        $this->makePost(['title' => 'ޝާއިޢުކުރެވިފައި', 'status' => 'published']);
        $this->makePost(['title' => 'ޑްރާފްޓް']);
        $this->makePost(['title' => 'ހިނގާ', 'type' => 'page']);

        $this->actingAs($this->user())
            ->get('/admin/news')
            ->assertOk()
            ->assertSee('ޝާއިޢުކުރެވިފައި')
            ->assertSee('ޑްރާފްޓް')
            ->assertDontSee('ހިނގާ')
            ->assertSee('data-tab="published"', false)
            ->assertSee('data-tab="trashed"', false)
            ->assertSee(url('admin/news/create'));
    }

    #[Test]
    public function it_filters_the_index(): void
    {
        Carbon::setTestNow('2026-11-06 10:00:00');

        $published = $this->makePost(['title' => 'ޝާއިޢު', 'status' => 'published', 'published_at' => '2026-11-01']);
        $scheduled = $this->makePost(['title' => 'ޝެޑިއުލް', 'status' => 'published', 'published_at' => '2026-12-01']);
        $draft = $this->makePost(['title' => 'ޑްރާފްޓް']);
        $category = $this->term('ކުޅިވަރު');
        $draft->syncTerms('categories', [$category->id]);

        $user = $this->user();

        $ids = fn (string $query) => collect($this->actingAs($user)->getJson('/admin/news?' . $query)->assertOk()->json('data'))->pluck('id')->all();

        $this->assertSame([$published->id], $ids('status=published'));
        $this->assertSame([$scheduled->id], $ids('status=scheduled'));
        $this->assertSame([$draft->id], $ids('status=draft'));
        $this->assertSame([$draft->id], $ids('categories[]=' . $category->id));
        $this->assertSame([$published->id], $ids('search=' . urlencode('ޝާއިޢު')));
        $this->assertSame([$draft->id, $published->id, $scheduled->id], $ids('orderby=title&order=ASC'));
    }

    #[Test]
    public function it_shows_the_trash_only_to_users_who_can_delete(): void
    {
        $this->makePost(['title' => 'ފޮހެލެވިފަ'])->delete();

        $this->actingAs($this->user(['view_news']))
            ->get('/admin/news?trashed=only')
            ->assertForbidden();

        $this->actingAs($this->user())
            ->get('/admin/news?trashed=only')
            ->assertOk()
            ->assertSee('ފޮހެލެވިފަ');
    }

    #[Test]
    public function it_creates_posts_with_meta_terms_and_an_author(): void
    {
        Carbon::setTestNow('2026-11-06 10:00:00');

        $user = $this->user();
        $category = $this->term('ކުޅިވަރު');
        $tag = $this->term('ފުޓުބޯޅަ', 'tags');

        $this->actingAs($user)
            ->get('/admin/news/create')
            ->assertOk()
            ->assertSee('name="title"', false)
            ->assertSee('name="meta[subtitle]"', false)
            ->assertSee('name="taxonomies[categories][]"', false);

        $response = $this->actingAs($user)->post('/admin/news', [
            'title' => 'ރާއްޖޭގެ ޚަބަރު',
            'excerpt' => 'ޚުލާޞާ',
            'content' => json_encode(['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'ދިވެހި']]]]]),
            'status' => 'published',
            'meta' => ['subtitle' => 'ސަބްޓައިޓަލް', 'featured' => '1'],
            'taxonomies' => ['categories' => [$category->id], 'tags' => [$tag->id]],
        ]);

        $post = Post::query()->sole();

        $response->assertRedirect(route('admin.news.edit', $post->id))->assertSessionHas('status', 'News article created.');

        $this->assertSame('news', $post->type);
        $this->assertSame('ރާއްޖޭގެ-ޚަބަރު', $post->slug);
        $this->assertSame('published', $post->status);
        $this->assertSame('2026-11-06 10:00:00', $post->published_at->toDateTimeString());
        $this->assertSame('ދިވެހި', $post->content->toText());
        $this->assertSame('ސަބްޓައިޓަލް', $post->getMeta('subtitle'));
        $this->assertTrue($post->getMeta('featured'));
        $this->assertSame([$category->id], $post->termsOf('categories')->modelKeys());
        $this->assertSame([$tag->id], $post->termsOf('tags')->modelKeys());
        $this->assertTrue($post->isOwnedBy($user));
    }

    #[Test]
    public function it_validates_posts(): void
    {
        $this->actingAs($this->user())
            ->from('/admin/news/create')
            ->post('/admin/news', ['title' => '', 'status' => 'nope'])
            ->assertRedirect('/admin/news/create')
            ->assertSessionHasErrors(['title', 'status']);

        $this->assertSame(0, Post::query()->count());
    }

    #[Test]
    public function it_needs_the_publish_permission_to_publish(): void
    {
        $writer = $this->user(['view_news', 'edit_news']);

        $this->actingAs($writer)
            ->post('/admin/news', ['title' => 'ޚަބަރު', 'status' => 'published'])
            ->assertSessionHasErrors('status');

        $this->actingAs($writer)
            ->post('/admin/news', ['title' => 'ޚަބަރު', 'status' => 'pending', 'published_at' => '2020-01-01 10:00'])
            ->assertRedirect();

        $post = Post::query()->sole();

        $this->assertSame('pending', $post->status);
        $this->assertNull($post->published_at);
    }

    #[Test]
    public function it_edits_and_updates_posts(): void
    {
        $post = $this->makePost(['title' => 'ކުރީގެ ސުރުހީ']);
        $post->setMeta('subtitle', 'ކުރީގެ');

        $this->actingAs($this->user())
            ->get("/admin/news/{$post->id}/edit")
            ->assertOk()
            ->assertSee('ކުރީގެ ސުރުހީ')
            ->assertSee('value="ކުރީގެ"', false);

        $this->actingAs($this->user())
            ->patch("/admin/news/{$post->id}", [
                'title' => 'އައު ސުރުހީ',
                'slug' => 'new-title',
                'status' => 'draft',
                'meta' => ['subtitle' => 'އައު'],
            ])
            ->assertRedirect(route('admin.news.edit', $post->id));

        $post->refresh();

        $this->assertSame('އައު ސުރުހީ', $post->title);
        $this->assertSame('new-title', $post->slug);
        $this->assertSame('އައު', $post->getMeta('subtitle'));
    }

    #[Test]
    public function it_doesnt_find_posts_of_another_type(): void
    {
        $page = $this->makePost(['type' => 'page']);

        $this->actingAs($this->user())
            ->get("/admin/news/{$page->id}/edit")
            ->assertNotFound();
    }

    #[Test]
    public function it_trashes_restores_and_deletes_posts(): void
    {
        $post = $this->makePost();
        $user = $this->user();

        $this->actingAs($user)->delete("/admin/news/{$post->id}")->assertRedirect(route('admin.news.index'));
        $this->assertSoftDeleted($post);

        $this->actingAs($user)->post("/admin/news/{$post->id}/restore")->assertRedirect(route('admin.news.edit', $post->id));
        $this->assertNotSoftDeleted($post);

        $post->delete();

        $this->actingAs($user)->delete("/admin/news/{$post->id}/force")->assertRedirect(route('admin.news.index'));
        $this->assertModelMissing($post);
    }

    #[Test]
    public function it_runs_bulk_actions(): void
    {
        $first = $this->makePost(['title' => 'A']);
        $second = $this->makePost(['title' => 'B']);
        $other = $this->makePost(['title' => 'C']);

        $this->actingAs($this->user())
            ->from('/admin/news')
            ->post('/admin/news/bulk', ['action' => 'publish', 'posts' => [$first->id, $second->id]])
            ->assertRedirect('/admin/news');

        $this->assertTrue($first->fresh()->isPublished());
        $this->assertTrue($second->fresh()->isPublished());
        $this->assertFalse($other->fresh()->isPublished());

        $this->actingAs($this->user())
            ->from('/admin/news')
            ->post('/admin/news/bulk', ['action' => 'delete', 'posts' => [$first->id]]);

        $this->assertSoftDeleted($first);
    }

    #[Test]
    public function it_queues_large_bulk_actions_for_the_post_type(): void
    {
        Queue::fake();
        config()->set('bulk-actions.queue_after', 1);

        $first = $this->makePost(['title' => 'ޚަބަރު 1']);
        $second = $this->makePost(['title' => 'ޚަބަރު 2']);
        $page = $this->makePost(['title' => 'ޞަފްޙާ', 'type' => 'page']);

        $this->actingAs($this->user())
            ->from('/admin/news')
            ->post('/admin/news/bulk', ['action' => 'publish', 'posts' => [$first->id, $second->id]])
            ->assertRedirect('/admin/news');

        $run = BulkActionRun::query()->sole();

        $this->assertSame(['post_type' => 'news'], $run->context);
        $this->assertFalse($first->fresh()->isPublished());
        Queue::assertPushed(RunBulkAction::class);

        // The job only has the run: the set is rebuilt for news from the context
        (new RunBulkAction($run))->handle();

        $this->assertSame(2, $run->fresh()->succeeded);
        $this->assertTrue($first->fresh()->isPublished());
        $this->assertTrue($second->fresh()->isPublished());
        $this->assertFalse($page->fresh()->isPublished());
    }

    #[Test]
    public function it_answers_json(): void
    {
        $post = $this->makePost(['status' => 'published']);

        $this->actingAs($this->user())
            ->getJson("/admin/news/{$post->id}/edit")
            ->assertOk()
            ->assertJsonPath('data.title', 'ރާއްޖޭގެ ޚަބަރު')
            ->assertJsonPath('data.status_label', 'Published')
            ->assertJsonPath('form.stencil', 1);

        $this->actingAs($this->user())
            ->postJson('/admin/news', ['title' => 'ޖޭސަން', 'status' => 'draft'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'ޖޭސަން');
    }

    #[Test]
    public function it_shows_the_pages_in_dhivehi(): void
    {
        $this->app->setLocale('dv');

        $this->makePost(['title' => 'ޑްރާފްޓް']);

        $this->actingAs($this->user())
            ->get('/admin/news')
            ->assertOk()
            ->assertSee('ހުރިހާ')
            ->assertSee('ޓްރޭޝަށް ލާ');

        $this->actingAs($this->user())
            ->get('/admin/news/create')
            ->assertOk()
            ->assertSee('ސުރުޚީ')
            ->assertSee('ޝާއިޢުކުރާ ތާރީޚް');
    }
}
