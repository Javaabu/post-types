<?php

namespace Javaabu\PostTypes\Tests\Feature;

use Javaabu\DynamicMenubuilder\DynamicMenubuilder;
use Javaabu\DynamicMenubuilder\Facades\Menus;
use Javaabu\DynamicMenubuilder\MenuManager;
use Javaabu\PostTypes\Contracts\MenuLinkable;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\Tests\Http\InteractsWithPostTypes;
use Javaabu\PostTypes\Tests\TestCase;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;

#[RequiresMethod(MenuManager::class, 'linkable')]
class MenusTest extends TestCase
{
    use InteractsWithPostTypes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPostTypes();
    }

    #[Test]
    public function it_registers_a_menu_source_per_public_post_type(): void
    {
        $this->assertInstanceOf(\Javaabu\DynamicMenubuilder\Contracts\MenuLinkable::class, new Post);
        $this->assertTrue(is_a(Post::class, MenuLinkable::class, true));

        $news = Menus::getLinkable('news');

        $this->assertSame('News', $news->getLabel());
        $this->assertSame(url('news'), $news->getIndexUrl());
        $this->assertNull(Menus::getLinkable('page')->getIndexUrl());

        $post = $this->makePost(['status' => 'published']);
        $page = $this->makePost(['type' => 'page', 'title' => 'About', 'status' => 'published']);

        $this->assertTrue($news->contains($post));
        $this->assertFalse($news->contains($page));
        $this->assertSame('news', Menus::sourceFor($post)->getKey());
        $this->assertSame('page', Menus::sourceFor($page)->getKey());
    }

    #[Test]
    public function it_links_to_published_posts(): void
    {
        $post = $this->makePost(['status' => 'published', 'published_at' => '2026-03-04']);
        $draft = $this->makePost(['title' => 'ޑްރާފްޓް']);

        $this->assertSame($post->permalink(), $post->getMenuLinkUrl());
        $this->assertSame('ރާއްޖޭގެ ޚަބަރު', $post->getMenuLinkTitle());
        $this->assertTrue($post->isMenuLinkVisible());
        $this->assertFalse($draft->isMenuLinkVisible());
        $this->assertSame([$draft->id], Post::searchMenuLinks(Post::query(), 'ޑްރާފް')->pluck('id')->all());
    }

    #[Test]
    public function it_removes_menu_items_when_a_post_is_deleted_for_good(): void
    {
        $post = $this->makePost(['status' => 'published']);
        $model = DynamicMenubuilder::menuItemModel();
        $menu = DynamicMenubuilder::menuModel()::query()->create(['name' => 'Main']);

        $item = $menu->items()->create([
            'type' => 'model',
            'source' => 'news',
            'linkable_type' => $post->getMorphClass(),
            'linkable_id' => $post->id,
        ]);

        $post->delete();
        $this->assertNotNull($item->fresh());

        $post->forceDelete();
        $this->assertNull($item->fresh());
    }
}
