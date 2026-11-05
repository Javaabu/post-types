<?php

namespace Javaabu\PostTypes\Tests\Http;

use Illuminate\Support\Carbon;
use Javaabu\PostTypes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PublicPagesTest extends TestCase
{
    use InteractsWithPostTypes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPostTypes();

        Carbon::setTestNow('2026-11-06 10:00:00');
    }

    #[Test]
    public function it_registers_public_routes_from_the_permalink(): void
    {
        $this->assertSame(url('news'), route('news.index'));
        $this->assertSame(url('news/categories/sports'), route('news.term', ['taxonomy' => 'categories', 'term' => 'sports']));
        $this->assertFalse(app('router')->has('page.index'));
        $this->assertTrue(app('router')->has('page.show'));

        $post = $this->makePost(['status' => 'published', 'published_at' => '2026-03-04']);

        $this->assertSame(url('news/2026/' . rawurlencode('ރާއްޖޭގެ-ޚަބަރު')), $post->permalink());
        $this->assertSame(url('page/about'), $this->makePost(['type' => 'page', 'title' => 'About'])->permalink());
    }

    #[Test]
    public function it_shows_the_archive_of_published_posts(): void
    {
        $this->makePost(['title' => 'ޝާއިޢު', 'status' => 'published', 'published_at' => '2026-11-01', 'excerpt' => 'ޚުލާޞާ']);
        $this->makePost(['title' => 'ޝެޑިއުލް', 'status' => 'published', 'published_at' => '2026-12-01']);
        $this->makePost(['title' => 'ޑްރާފްޓް']);

        $this->get('/news')
            ->assertOk()
            ->assertSee('ޝާއިޢު')
            ->assertSee('ޚުލާޞާ')
            ->assertDontSee('ޝެޑިއުލް')
            ->assertDontSee('ޑްރާފްޓް');

        $this->getJson('/news')->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function it_shows_term_archives_with_child_terms(): void
    {
        $news = $this->term('Local news');
        $sports = $this->term('Sports', parent: $news->id);

        $this->makePost(['title' => 'ކުޅިވަރު', 'status' => 'published'])->syncTerms('categories', [$sports->id]);
        $this->makePost(['title' => 'އެހެން', 'status' => 'published']);

        $this->get('/news/categories/local-news')
            ->assertOk()
            ->assertSee('Local news')
            ->assertSee('ކުޅިވަރު')
            ->assertDontSee('އެހެން');

        $this->get('/news/categories/nope')->assertNotFound();
        $this->get('/news/tags/local-news')->assertNotFound();
    }

    #[Test]
    public function it_shows_single_posts(): void
    {
        $post = $this->makePost([
            'title' => 'ރާއްޖޭގެ ޚަބަރު',
            'status' => 'published',
            'published_at' => '2026-03-04',
            'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'ދިވެހި ބަސް']]]]],
        ]);

        $this->get($post->permalink())
            ->assertOk()
            ->assertSee('ރާއްޖޭގެ ޚަބަރު')
            ->assertSee('ދިވެހި ބަސް');

        $this->getJson($post->permalink())->assertJsonPath('data.title', 'ރާއްޖޭގެ ޚަބަރު');
    }

    #[Test]
    public function it_redirects_other_paths_to_the_permalink(): void
    {
        $post = $this->makePost(['status' => 'published', 'published_at' => '2026-03-04']);

        $this->get('/news/2025/' . rawurlencode($post->slug))->assertRedirect($post->permalink())->assertStatus(301);
    }

    #[Test]
    public function it_hides_unpublished_posts_except_from_editors(): void
    {
        $draft = $this->makePost(['title' => 'ޑްރާފްޓް', 'published_at' => '2026-01-01']);
        $scheduled = $this->makePost(['title' => 'ޝެޑިއުލް', 'status' => 'published', 'published_at' => '2026-12-01']);

        $this->get($draft->permalink())->assertNotFound();
        $this->get($scheduled->permalink())->assertNotFound();

        $this->actingAs($this->user())
            ->get($draft->permalink())
            ->assertOk()
            ->assertSee('This is a preview.');

        $this->actingAs($this->user(['view_news']))->get($draft->permalink())->assertNotFound();
    }

    #[Test]
    public function it_uses_the_apps_templates_first(): void
    {
        $this->makePost(['title' => 'ޝާއިޢު', 'status' => 'published']);

        view()->addNamespace('test', __DIR__ . '/../TestSupport/views');
        file_put_contents($path = __DIR__ . '/../TestSupport/views/post-types/news/archive.blade.php', 'custom archive: {{ count($posts) }}');

        try {
            $this->get('/news')->assertOk()->assertSee('custom archive: 1');
        } finally {
            unlink($path);
        }
    }
}
