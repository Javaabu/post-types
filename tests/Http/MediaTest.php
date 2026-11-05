<?php

namespace Javaabu\PostTypes\Tests\Http;

use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Stencil\MediaPickerField;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Tests\TestCase;
use Javaabu\PostTypes\Tests\TestSupport\Models\User;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;

#[RequiresPhpExtension('gd')]
class MediaTest extends TestCase
{
    use InteractsWithPostTypes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPostTypes();
    }

    protected function image(User $user): Media
    {
        $path = tempnam(sys_get_temp_dir(), 'post-types') . '.png';
        $image = imagecreatetruecolor(20, 10);
        imagepng($image, $path);

        /** @var Media */
        return $user->addMedia($path)->toMediaCollection('mediapicker');
    }

    #[Test]
    public function it_saves_the_featured_image_and_other_media(): void
    {
        PostTypes::register(
            PostType::make('news')
                ->taxonomies(['categories', 'tags'])
                ->permalink('{year}/{slug}')
                ->media([MediaPickerField::make('gallery')->mediaType('image')->multiple()])
        );

        $user = $this->user();
        $first = $this->image($user);
        $second = $this->image($user);

        $this->actingAs($user)
            ->get('/admin/news/create')
            ->assertOk()
            ->assertSee('name="featured_image"', false)
            ->assertSee('name="gallery"', false);

        $this->actingAs($user)
            ->post('/admin/news', [
                'title' => 'ފޮޓޯ',
                'status' => 'draft',
                'featured_image' => $first->id,
                'gallery' => [$second->id, $first->id],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $post = Post::query()->sole();

        $this->assertSame($first->id, $post->getFirstAttachmentMedia(Post::FEATURED_IMAGE)->id);
        $this->assertSame([$second->id, $first->id], $post->getAttachmentMedia('gallery')->pluck('id')->all());
        $this->assertNotNull($post->featuredImageUrl());

        $this->actingAs($user)
            ->getJson('/admin/news')
            ->assertJsonPath('data.0.thumbnail', fn ($url) => is_string($url) && $url !== '');
    }

    #[Test]
    public function it_refuses_media_the_user_cant_see(): void
    {
        $other = $this->image($this->user());

        $this->actingAs($this->user(['view_news', 'edit_news']))
            ->post('/admin/news', ['title' => 'ފޮޓޯ', 'status' => 'draft', 'featured_image' => $other->id])
            ->assertSessionHasErrors('featured_image');
    }
}
