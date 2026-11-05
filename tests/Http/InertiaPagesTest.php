<?php

namespace Javaabu\PostTypes\Tests\Http;

use Illuminate\Support\Carbon;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Inertia\Testing\AssertableInertia;
use Javaabu\PostTypes\Tests\TestCase;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;

/**
 * The Inertia pages' props. With POST_TYPES_UPDATE_FIXTURES=1 it writes them
 * to tests/js/fixtures, which the React page tests render.
 */
#[RequiresMethod(InertiaServiceProvider::class, 'register')]
class InertiaPagesTest extends TestCase
{
    use InteractsWithPostTypes {
        defineEnvironment as postTypesEnvironment;
    }

    protected function defineEnvironment($app): void
    {
        $this->postTypesEnvironment($app);

        $app['config']->set('post-types.views', 'inertia');
        $app['config']->set('inertia.testing.ensure_pages_exist', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPostTypes();

        Carbon::setTestNow('2026-11-06 10:00:00');
    }

    /**
     * @param  array<string, mixed>  $props
     */
    protected function writeFixture(string $name, array $props): void
    {
        unset($props['errors']);

        if (getenv('POST_TYPES_UPDATE_FIXTURES')) {
            file_put_contents(
                __DIR__ . "/../js/fixtures/{$name}.json",
                json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
            );
        }
    }

    #[Test]
    public function it_renders_the_index_page(): void
    {
        $category = $this->term('ކުޅިވަރު');
        $post = $this->makePost(['title' => 'ރާއްޖޭގެ ޚަބަރު', 'status' => 'published', 'published_at' => '2026-11-01 08:00']);
        $post->syncTerms('categories', [$category->id]);
        $post->author()->associate($user = $this->user())->save();
        $this->makePost(['title' => 'ޑްރާފްޓް']);

        $response = $this->actingAs($user)
            ->get('/admin/news')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('post-types::Posts/Index')
                ->where('post_type.key', 'news')
                ->where('rows.0.title', 'ރާއްޖޭގެ ޚަބަރު')
                ->where('rows.0.author', 'އަޙްމަދު')
                ->where('rows.0.terms.0.terms.0.name', 'ކުޅިވަރު')
                ->where('rows.1.status_label', 'Draft')
                ->where('posts.total', 2)
                ->where('filters.id', 'filter')
                ->where('bulk_actions.name', 'posts')
                ->where('tabs.0.key', 'all')
                ->where('create_url', 'http://localhost/admin/news/create')
                ->etc());

        $this->writeFixture('posts-index', $response->viewData('page')['props']);
    }

    #[Test]
    public function it_renders_the_edit_page(): void
    {
        $post = $this->makePost(['title' => 'ރާއްޖޭގެ ޚަބަރު', 'status' => 'published']);

        $response = $this->actingAs($this->user())
            ->get("/admin/news/{$post->id}/edit")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('post-types::Posts/Edit')
                ->where('post.id', $post->id)
                ->where('form.stencil', 1)
                ->where('form.method', 'PATCH')
                ->where('form.state.title', 'ރާއްޖޭގެ ޚަބަރު')
                ->etc());

        $this->writeFixture('posts-edit', $response->viewData('page')['props']);
    }

    #[Test]
    public function it_renders_the_terms_pages(): void
    {
        $parent = $this->term('ޚަބަރު');
        $this->term('ކުޅިވަރު', parent: $parent->id);

        $user = $this->user(['view_categories', 'edit_categories', 'delete_categories']);

        $response = $this->actingAs($user)
            ->get('/admin/taxonomies/categories')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('post-types::Terms/Index')
                ->where('terms.1.depth', 1)
                ->etc());

        $this->writeFixture('terms-index', $response->viewData('page')['props']);

        $response = $this->actingAs($user)
            ->get('/admin/taxonomies/categories/create')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('post-types::Terms/Edit')
                ->where('term', null)
                ->etc());

        $this->writeFixture('terms-edit', $response->viewData('page')['props']);
    }

    #[Test]
    public function it_renders_the_public_pages(): void
    {
        $category = $this->term('ކުޅިވަރު');
        $post = $this->makePost([
            'title' => 'ރާއްޖޭގެ ޚަބަރު',
            'excerpt' => 'ޚުލާޞާ',
            'status' => 'published',
            'published_at' => '2026-11-01 08:00',
            'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'ދިވެހި ބަސް']]]]],
        ]);
        $post->syncTerms('categories', [$category->id]);

        $response = $this->get('/news')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('post-types::Public/Archive')
                ->where('posts.0.title', 'ރާއްޖޭގެ ޚަބަރު')
                ->where('posts.0.terms.0.terms.0.url', 'http://localhost/news/categories/' . rawurlencode('ކުޅިވަރު'))
                ->etc());

        $this->writeFixture('public-archive', $response->viewData('page')['props']);

        $this->get('/news/categories/' . rawurlencode('ކުޅިވަރު'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('post-types::Public/Term')
                ->where('title', 'ކުޅިވަރު')
                ->etc());

        $response = $this->get($post->url())
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('post-types::Public/Single')
                ->where('post.title', 'ރާއްޖޭގެ ޚަބަރު')
                ->where('preview', false)
                ->etc());

        $this->writeFixture('public-single', $response->viewData('page')['props']);
    }
}
