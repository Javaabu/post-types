<?php

namespace Javaabu\PostTypes\Tests\Http;

use Javaabu\PostTypes\Models\Term;
use Javaabu\PostTypes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TermsControllerTest extends TestCase
{
    use InteractsWithPostTypes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPostTypes();
    }

    #[Test]
    public function it_lists_terms_as_a_tree(): void
    {
        $news = $this->term('ޚަބަރު');
        $this->term('ކުޅިވަރު', parent: $news->id);
        $this->term('ފުޓުބޯޅަ', 'tags');

        $response = $this->actingAs($this->user(['view_categories']))
            ->get('/admin/taxonomies/categories')
            ->assertOk()
            ->assertSee('ޚަބަރު')
            ->assertSee('ކުޅިވަރު')
            ->assertDontSee('ފުޓުބޯޅަ');

        $this->assertSame([0, 1], collect($this->actingAs($this->user(['view_categories']))->getJson('/admin/taxonomies/categories')->json('data'))->pluck('depth')->all());

        $this->actingAs($this->user(['view_categories']))
            ->getJson('/admin/taxonomies/categories?search=' . urlencode('ކުޅި'))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'ކުޅިވަރު');
    }

    #[Test]
    public function it_creates_updates_and_deletes_terms(): void
    {
        $user = $this->user(['view_categories', 'edit_categories', 'delete_categories']);
        $parent = $this->term('ޚަބަރު');

        $this->actingAs($user)->get('/admin/taxonomies/categories/create')->assertOk()->assertSee('name="parent_id"', false);

        $this->actingAs($user)
            ->post('/admin/taxonomies/categories', ['name' => 'ކުޅިވަރު', 'parent_id' => $parent->id, 'order' => 2])
            ->assertRedirect(route('admin.taxonomies.categories.index'));

        $term = Term::query()->where('name', 'ކުޅިވަރު')->sole();

        $this->assertSame('categories', $term->taxonomy);
        $this->assertSame('ކުޅިވަރު', $term->slug);
        $this->assertSame($parent->id, $term->parent_id);

        $this->actingAs($user)
            ->patch("/admin/taxonomies/categories/{$term->id}", ['name' => 'Sports', 'slug' => 'sports', 'parent_id' => ''])
            ->assertRedirect();

        $this->assertSame('sports', $term->fresh()->slug);
        $this->assertNull($term->fresh()->parent_id);

        $this->actingAs($user)->delete("/admin/taxonomies/categories/{$term->id}")->assertRedirect();
        $this->assertModelMissing($term);
    }

    #[Test]
    public function it_doesnt_make_a_term_its_own_ancestor(): void
    {
        $user = $this->user(['edit_categories']);
        $parent = $this->term('A');
        $child = $this->term('B', parent: $parent->id);

        $this->actingAs($user)
            ->patch("/admin/taxonomies/categories/{$parent->id}", ['name' => 'A', 'parent_id' => $child->id])
            ->assertSessionHasErrors('parent_id');
    }

    #[Test]
    public function it_needs_the_taxonomy_permissions(): void
    {
        $term = $this->term('A');

        $this->actingAs($this->user(['view_news']))->get('/admin/taxonomies/categories')->assertForbidden();
        $this->actingAs($this->user(['view_categories']))->get('/admin/taxonomies/categories/create')->assertForbidden();
        $this->actingAs($this->user(['edit_categories']))->delete("/admin/taxonomies/categories/{$term->id}")->assertForbidden();
        $this->actingAs($this->user(['edit_categories']))->get("/admin/taxonomies/tags/{$term->id}/edit")->assertNotFound();
    }
}
