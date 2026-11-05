<?php

namespace Javaabu\PostTypes\Tests\Feature;

use Javaabu\Meta\MetaFields;
use Javaabu\PostTypes\Exceptions\PostTypesException;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Status;
use Javaabu\PostTypes\Taxonomy;
use Javaabu\PostTypes\Tests\TestCase;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\TextInput;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\Test;

class DefinitionsTest extends TestCase
{
    #[Test]
    public function it_registers_post_types_with_defaults(): void
    {
        PostTypes::register(PostType::make('news'));

        $news = PostTypes::get('news');

        $this->assertSame('News', $news->getSingularLabel());
        $this->assertSame('news', $news->getSlug());
        $this->assertSame('{slug}', $news->getPermalinkStructure());
        $this->assertSame('news', $news->getPermissionResource());
        $this->assertSame('view_news', $news->permissionFor('view'));
        $this->assertTrue($news->supportsFeature(PostType::CONTENT));
        $this->assertFalse($news->supportsFeature(PostType::META));
        $this->assertSame(['draft', 'pending', 'published'], array_keys($news->getStatuses()));
        $this->assertSame(['published'], $news->getPublicStatuses());
        $this->assertSame('draft', $news->getDefaultStatus());
        $this->assertTrue($news->hasArchive());
    }

    #[Test]
    public function it_defines_post_types_fluently(): void
    {
        PostTypes::taxonomy(Taxonomy::make('categories')->labels('Category', 'Categories')->hierarchical());
        PostTypes::status(Status::make('archived')->label('Archived')->color('info'));

        PostTypes::register(
            PostType::make('event')
                ->labels('Event', 'Events')
                ->icon('calendar')
                ->supports(['content', 'media'])
                ->taxonomies(['categories', Taxonomy::make('venues')])
                ->statuses(['draft', 'published', 'archived'])
                ->slug('/events/')
                ->permalink('{year}/{slug}')
                ->archive(per_page: 6)
                ->ownable()
        );

        $event = PostTypes::get('event');

        $this->assertSame('Events', $event->getPluralLabel());
        $this->assertSame('events', $event->getPermissionResource());
        $this->assertSame(['categories', 'venues'], $event->getTaxonomyKeys());
        $this->assertTrue(PostTypes::hasTaxonomy('venues'));
        $this->assertTrue($event->getTaxonomies()['categories']->isHierarchical());
        $this->assertSame('Archived', $event->getStatus('archived')->getLabel());
        $this->assertSame('events', $event->getSlug());
        $this->assertSame(6, $event->getArchivePerPage());
        $this->assertArrayHasKey('edit_others_events', $event->permissions());
        $this->assertSame('Publish events', $event->permissions()['publish_events']);
    }

    #[Test]
    public function it_registers_meta_fields_under_the_post_type(): void
    {
        PostTypes::register(PostType::make('news')->metaFields([
            TextInput::make('subtitle'),
            Checkbox::make('featured'),
        ]));

        $post = new Post(['type' => 'news']);

        $this->assertTrue(PostType::make('x')->metaFields([])->supportsFeature(PostType::META));
        $this->assertSame(['subtitle', 'featured'], array_keys(app(MetaFields::class)->fields($post)));
        $this->assertSame([], app(MetaFields::class)->fields(new Post(['type' => 'page'])));
    }

    #[Test]
    public function it_lets_config_override_permalinks(): void
    {
        config()->set('post-types.permalinks.news', ['base' => 'blog', 'structure' => '{year}/{month}/{slug}']);

        $news = PostType::make('news');

        $this->assertSame('blog', $news->getSlug());
        $this->assertSame('{year}/{month}/{slug}', $news->getPermalinkStructure());
    }

    #[Test]
    public function it_rejects_invalid_definitions(): void
    {
        $this->expectException(PostTypesException::class);

        PostType::make('News Items');
    }

    #[Test]
    public function it_rejects_unknown_features(): void
    {
        $this->expectException(PostTypesException::class);

        PostType::make('news')->supports(['comments']);
    }

    #[Test]
    public function it_rejects_permalinks_without_slug_or_id(): void
    {
        $this->expectException(PostTypesException::class);

        PostType::make('news')->permalink('{year}')->getPermalink();
    }

    protected function configuredTypes($app): void
    {
        $app['config']->set('post-types.taxonomies', [Taxonomy::make('tags')]);
        $app['config']->set('post-types.types', [PostType::make('page')->public()->archive(false)]);
    }

    #[Test]
    #[DefineEnvironment('configuredTypes')]
    public function it_registers_post_types_from_config(): void
    {
        $this->assertTrue(PostTypes::has('page'));
        $this->assertTrue(PostTypes::hasTaxonomy('tags'));
        $this->assertFalse(PostTypes::get('page')->hasArchive());
    }
}
