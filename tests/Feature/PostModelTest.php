<?php

namespace Javaabu\PostTypes\Tests\Feature;

use Illuminate\Support\Carbon;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\Models\Term;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Taxonomy;
use Javaabu\PostTypes\Tests\TestCase;
use Javaabu\PostTypes\Tests\TestSupport\Models\User;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\TextInput;
use PHPUnit\Framework\Attributes\Test;

class PostModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        PostTypes::taxonomy(Taxonomy::make('categories')->hierarchical());
        PostTypes::taxonomy(Taxonomy::make('tags'));

        PostTypes::register(
            PostType::make('news')
                ->taxonomies(['categories', 'tags'])
                ->metaFields([TextInput::make('subtitle'), Checkbox::make('featured')])
        );

        PostTypes::register(PostType::make('page'));
    }

    #[Test]
    public function it_gives_posts_unique_slugs_per_post_type(): void
    {
        $first = Post::create(['type' => 'news', 'title' => 'ރާއްޖޭގެ ޚަބަރު']);
        $second = Post::create(['type' => 'news', 'title' => 'ރާއްޖޭގެ ޚަބަރު']);
        $page = Post::create(['type' => 'page', 'title' => 'ރާއްޖޭގެ ޚަބަރު']);
        $custom = Post::create(['type' => 'news', 'title' => 'x', 'slug' => 'My Custom Slug']);

        $this->assertSame('ރާއްޖޭގެ-ޚަބަރު', $first->slug);
        $this->assertSame('ރާއްޖޭގެ-ޚަބަރު-2', $second->slug);
        $this->assertSame('ރާއްޖޭގެ-ޚަބަރު', $page->slug);
        $this->assertSame('my-custom-slug', $custom->slug);

        $first->update(['title' => 'Changed']);
        $this->assertSame('ރާއްޖޭގެ-ޚަބަރު', $first->fresh()->slug);
    }

    #[Test]
    public function it_sets_the_publish_date_of_published_posts(): void
    {
        Carbon::setTestNow('2026-11-06 10:00:00');

        $draft = Post::create(['type' => 'news', 'title' => 'ޑްރާފްޓް']);
        $published = Post::create(['type' => 'news', 'title' => 'ޝާއިޢު', 'status' => 'published']);

        $this->assertNull($draft->published_at);
        $this->assertSame('2026-11-06 10:00:00', $published->published_at->toDateTimeString());
        $this->assertTrue($published->isPublished());
        $this->assertFalse($draft->isPublished());
    }

    #[Test]
    public function it_scopes_published_scheduled_and_unpublished_posts(): void
    {
        Carbon::setTestNow('2026-11-06 10:00:00');

        $published = Post::create(['type' => 'news', 'title' => 'A', 'status' => 'published', 'published_at' => '2026-11-01']);
        $scheduled = Post::create(['type' => 'news', 'title' => 'B', 'status' => 'published', 'published_at' => '2026-12-01']);
        $draft = Post::create(['type' => 'news', 'title' => 'C']);
        $pending = Post::create(['type' => 'page', 'title' => 'D', 'status' => 'pending']);

        $this->assertSame([$published->id], Post::query()->published()->pluck('id')->all());
        $this->assertSame([$scheduled->id], Post::query()->scheduled()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$scheduled->id, $draft->id, $pending->id], Post::query()->unpublished()->pluck('id')->all());
        $this->assertSame([$pending->id], Post::query()->ofType('page')->pluck('id')->all());
        $this->assertSame([$draft->id], Post::query()->withStatus('draft')->pluck('id')->all());

        $this->assertTrue($scheduled->isScheduled());
        $this->assertSame('Scheduled', $scheduled->displayStatus());
        $this->assertSame('Pending review', $pending->displayStatus());
        $this->assertSame('warning', $pending->displayStatusColor());
    }

    #[Test]
    public function it_searches_titles_and_excerpts(): void
    {
        $match = Post::create(['type' => 'news', 'title' => 'މާލޭގެ ޚަބަރު']);
        Post::create(['type' => 'news', 'title' => 'Other', 'excerpt' => '100% sure']);
        $excerpt = Post::create(['type' => 'news', 'title' => 'x', 'excerpt' => 'ހުޅުމާލެ']);

        $this->assertSame([$match->id], Post::query()->search('މާލޭ')->pluck('id')->all());
        $this->assertSame([$excerpt->id], Post::query()->search('ހުޅު')->pluck('id')->all());
        $this->assertSame(1, Post::query()->search('100%')->count());
        $this->assertSame(3, Post::query()->search('  ')->count());
    }

    #[Test]
    public function it_syncs_terms_per_taxonomy(): void
    {
        $sports = Term::create(['taxonomy' => 'categories', 'name' => 'ކުޅިވަރު']);
        $local = Term::create(['taxonomy' => 'categories', 'name' => 'Local']);
        $tag = Term::create(['taxonomy' => 'tags', 'name' => 'ފުޓުބޯޅަ']);

        $post = Post::create(['type' => 'news', 'title' => 'ޚަބަރު']);

        $post->syncTerms('categories', [$sports->id, $tag->id]);
        $post->syncTerms('tags', [$tag]);

        $this->assertSame([$sports->id], $post->termsOf('categories')->modelKeys());
        $this->assertSame([$tag->id], $post->termsOf('tags')->modelKeys());

        $post->syncTerms('categories', [$local->id]);

        $this->assertSame([$local->id], $post->fresh()->termsOf('categories')->modelKeys());
        $this->assertSame([$tag->id], $post->fresh()->termsOf('tags')->modelKeys());

        $this->assertSame([$post->id], Post::query()->withTerms('categories', 'local')->pluck('id')->all());
        $this->assertSame([], Post::query()->withTerms('categories', $sports)->pluck('id')->all());
    }

    #[Test]
    public function it_keeps_term_trees(): void
    {
        $news = Term::create(['taxonomy' => 'categories', 'name' => 'News', 'order' => 1]);
        $local = Term::create(['taxonomy' => 'categories', 'name' => 'Local', 'parent_id' => $news->id]);
        $sports = Term::create(['taxonomy' => 'categories', 'name' => 'Sports', 'order' => 0]);

        $tree = array_map(fn (array $node) => [$node['term']->name, $node['depth']], Term::tree('categories'));

        $this->assertSame([['Sports', 0], ['News', 0], ['Local', 1]], $tree);
        $this->assertSame('News › Local', $local->path());
        $this->assertTrue($news->isSelfOrDescendant($local->id));
        $this->assertFalse($local->isSelfOrDescendant($news->id));

        $news->delete();

        $this->assertNull($local->fresh()->parent_id);
    }

    #[Test]
    public function it_saves_meta_of_the_post_type(): void
    {
        $post = Post::create([
            'type' => 'news',
            'title' => 'ޚަބަރު',
            'meta' => ['subtitle' => 'ސަބްޓައިޓަލް', 'featured' => true, 'unknown' => 'x'],
        ]);

        $this->assertSame('ސަބްޓައިޓަލް', $post->getMeta('subtitle'));
        $this->assertTrue($post->getMeta('featured'));
        $this->assertFalse($post->hasMeta('unknown'));
        $this->assertSame([$post->id], Post::query()->whereMeta('featured', true)->pluck('id')->all());
    }

    #[Test]
    public function it_relates_authors_of_any_user_model(): void
    {
        $user = User::create(['name' => 'އަޙްމަދު', 'email' => 'a@example.com', 'password' => 'secret']);

        $post = Post::create(['type' => 'news', 'title' => 'ޚަބަރު']);
        $post->author()->associate($user)->save();

        $this->assertTrue($post->fresh()->author->is($user));
        $this->assertTrue($post->isOwnedBy($user));
        $this->assertSame([$post->id], Post::query()->byAuthor($user)->pluck('id')->all());
    }

    #[Test]
    public function it_builds_permalink_paths(): void
    {
        PostTypes::register(PostType::make('news')->taxonomies(['categories'])->permalink('{categories}/{year}/{month}/{day}/{slug}'));

        $post = Post::create(['type' => 'news', 'title' => 'ޚަބަރު', 'status' => 'published', 'published_at' => '2026-03-04']);

        $this->assertNull($post->permalinkPath());

        $post->syncTerms('categories', [Term::create(['taxonomy' => 'categories', 'name' => 'Sports'])->id]);

        $this->assertSame('sports/2026/03/04/' . rawurlencode('ޚަބަރު'), $post->fresh()->permalinkPath());
        $this->assertSame('{term_categories}/{post_year}/{post_month}/{post_day}/{post_slug}', $post->postType()->getPermalink()->routeUri());
    }

    #[Test]
    public function it_summarises_content(): void
    {
        $post = Post::create([
            'type' => 'news',
            'title' => 'ޚަބަރު',
            'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'ދިވެހި ބަސް']]]]],
        ]);

        $this->assertSame('ދިވެހި ބަސް', $post->fresh()->summary());

        $post->excerpt = 'ޚުލާޞާ';

        $this->assertSame('ޚުލާޞާ', $post->summary());
    }
}
