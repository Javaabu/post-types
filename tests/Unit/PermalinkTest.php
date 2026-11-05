<?php

namespace Javaabu\PostTypes\Tests\Unit;

use Javaabu\PostTypes\Exceptions\PostTypesException;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Routing\Permalink;
use Javaabu\PostTypes\Support\Slug;
use Javaabu\PostTypes\Taxonomy;
use Javaabu\PostTypes\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class PermalinkTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function slugs(): array
    {
        return [
            'thaana keeps its fili' => ['ރާއްޖޭގެ ޚަބަރު', 'ރާއްޖޭގެ-ޚަބަރު'],
            'english' => ['Hello, World!', 'hello-world'],
            'mixed' => ['Malé މާލެ 2026', 'malé-މާލެ-2026'],
            'only punctuation' => ['?!', ''],
        ];
    }

    #[Test]
    #[DataProvider('slugs')]
    public function it_makes_slugs(string $value, string $expected): void
    {
        $this->assertSame($expected, Slug::make($value));
    }

    #[Test]
    public function it_compiles_structures_to_routes(): void
    {
        $permalink = new Permalink(PostType::make('news'), '/{year}/{month}/{day}/{slug}/');

        $this->assertSame('{year}/{month}/{day}/{slug}', $permalink->getStructure());
        $this->assertSame('{post_year}/{post_month}/{post_day}/{post_slug}', $permalink->routeUri());
        $this->assertSame([
            'post_year' => '[0-9]{4}',
            'post_month' => '[0-9]{2}',
            'post_day' => '[0-9]{2}',
            'post_slug' => '[^/]+',
        ], $permalink->wheres());
        $this->assertFalse($permalink->usesId());
    }

    #[Test]
    public function it_matches_posts_to_parameters(): void
    {
        PostTypes::taxonomy(Taxonomy::make('categories'));
        $permalink = new Permalink(PostType::make('news'), 'news-{id}');

        $post = new Post(['type' => 'news', 'title' => 'x']);
        $post->id = 7;

        $this->assertTrue($permalink->usesId());
        $this->assertSame('news-7', $permalink->pathFor($post));
        $this->assertTrue($permalink->matches($post, ['post_id' => '7']));
        $this->assertFalse($permalink->matches($post, ['post_id' => '8']));
    }

    #[Test]
    public function it_rejects_unknown_tokens(): void
    {
        $this->expectException(PostTypesException::class);
        $this->expectExceptionMessage('{author} isn\'t a token or a taxonomy');

        new Permalink(PostType::make('news'), '{author}/{slug}');
    }

    #[Test]
    public function it_rejects_repeated_tokens(): void
    {
        $this->expectException(PostTypesException::class);

        new Permalink(PostType::make('news'), '{slug}/{slug}');
    }
}
