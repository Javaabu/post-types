<?php

namespace Javaabu\PostTypes\Tests\Http;

use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Translatable\Post as TranslatablePost;
use Javaabu\PostTypes\Models\Translatable\Term as TranslatableTerm;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Support\Translations;
use Javaabu\PostTypes\Tests\Concerns\InteractsWithTranslatable;
use Javaabu\PostTypes\Tests\TestCase;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Translatable\Facades\Languages;
use Javaabu\Translatable\Translatable;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;

#[RequiresMethod(Translatable::class, 'enabled')]
class TranslatablePostsTest extends TestCase
{
    use InteractsWithPostTypes;
    use InteractsWithTranslatable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPostTypes();
        $this->setUpLanguages(['en', 'dv']);

        PostTypes::register(
            PostType::make('news')
                ->labels('News article', 'News')
                ->taxonomies(['categories', 'tags'])
                ->permalink('{year}/{slug}')
                ->metaFields([TextInput::make('subtitle')->translatable(), TextInput::make('code')])
        );
    }

    #[Test]
    public function it_uses_the_translatable_models(): void
    {
        $this->assertTrue(Translations::enabled());
        $this->assertSame(TranslatablePost::class, PostTypes::postModel());
        $this->assertSame(TranslatableTerm::class, PostTypes::termModel());
    }

    #[Test]
    public function it_renders_an_input_per_language(): void
    {
        $this->actingAs($this->user())
            ->get('/admin/news/create')
            ->assertOk()
            ->assertSee('name="title_en"', false)
            ->assertSee('name="title_dv"', false)
            ->assertSee('name="excerpt_dv"', false)
            ->assertSee('name="meta[subtitle_dv]"', false)
            ->assertSee('name="meta[code]"', false)
            ->assertDontSee('name="meta[code_dv]"', false)
            ->assertDontSee('name="slug_dv"', false);
    }

    #[Test]
    public function it_saves_every_language(): void
    {
        $this->actingAs($this->user())
            ->post('/admin/news', [
                'title_en' => 'Local news',
                'title_dv' => 'ރާއްޖޭގެ ޚަބަރު',
                'excerpt_en' => 'Summary',
                'excerpt_dv' => 'ޚުލާޞާ',
                'status' => 'published',
                'meta' => ['subtitle_en' => 'Sub', 'subtitle_dv' => 'ސަބް', 'code' => 'A1'],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $post = TranslatablePost::query()->sole();

        $this->assertSame('Local news', $post->translate('title', 'en'));
        $this->assertSame('ރާއްޖޭގެ ޚަބަރު', $post->translate('title', 'dv'));
        $this->assertSame('ޚުލާޞާ', $post->translate('excerpt', 'dv'));
        $this->assertSame('local-news', $post->slug);
        $this->assertSame('ސަބް', $post->getMeta('subtitle', locale: 'dv'));
        $this->assertSame('Sub', $post->getMeta('subtitle', locale: 'en'));
        $this->assertSame('A1', $post->getMeta('code'));

        app()->setLocale('dv');
        Languages::setCurrentLocale('dv');

        $this->get($post->permalink())->assertOk()->assertSee('ރާއްޖޭގެ ޚަބަރު')->assertDontSee('Local news');
    }

    #[Test]
    public function it_translates_content(): void
    {
        $post = TranslatablePost::create(['type' => 'news', 'title' => 'News', 'status' => 'published']);

        $post->content_dv = ['type' => 'doc', 'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'ދިވެހި']]],
            ['type' => 'unknownBlock'],
        ]];
        $post->save();

        $document = $post->fresh()->contentDocument('dv');

        $this->assertSame('ދިވެހި', trim($document->toText()));
        $this->assertCount(1, $document->blocks());
    }

    #[Test]
    public function it_translates_terms(): void
    {
        $user = $this->user(['view_categories', 'edit_categories']);

        $this->actingAs($user)
            ->post('/admin/taxonomies/categories', ['name_en' => 'Sports', 'name_dv' => 'ކުޅިވަރު'])
            ->assertSessionHasNoErrors();

        $term = TranslatableTerm::query()->sole();

        $this->assertSame('ކުޅިވަރު', $term->translate('name', 'dv'));
        $this->assertSame('sports', $term->slug);
    }

    #[Test]
    public function it_works_with_translations_turned_off(): void
    {
        $this->withoutTranslatable(function () {
            $this->actingAs($this->user())
                ->post('/admin/news', ['title' => 'ޚަބަރު', 'status' => 'draft', 'meta' => ['subtitle' => 'ސަބް']])
                ->assertSessionHasNoErrors();
        });

        $this->assertSame('ޚަބަރު', TranslatablePost::query()->sole()->title);
    }
}
