<?php

namespace Javaabu\PostTypes\Http;

use Illuminate\Database\Eloquent\Model;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\Models\Term;
use Javaabu\PostTypes\PostType;

/**
 * What the public pages and JSON get for posts and terms: the same in Blade,
 * React and JSON
 */
class PublicData
{
    public function __construct(protected PostType $type) {}

    public static function make(PostType $type): static
    {
        return new static($type);
    }

    /**
     * A post in a list
     *
     * @return array<string, mixed>
     */
    public function card(Post $post): array
    {
        return [
            'id' => $post->getKey(),
            'title' => (string) $post->title,
            'slug' => $post->slug,
            'url' => $post->permalink(),
            'summary' => $this->type->supportsFeature(PostType::EXCERPT) || $this->type->supportsFeature(PostType::CONTENT) ? $post->summary() : null,
            'published_at' => $post->published_at?->toIso8601String(),
            'published_at_label' => $post->published_at?->translatedFormat('j F Y'),
            'author' => $this->type->supportsFeature(PostType::AUTHOR) ? $this->authorName($post->author) : null,
            'image' => $this->type->supportsFeature(PostType::MEDIA) ? $post->featuredImageUrl() : null,
            'terms' => $this->terms($post),
        ];
    }

    /**
     * A post with its content and meta, for the single page
     *
     * @return array<string, mixed>
     */
    public function full(Post $post): array
    {
        return [
            ...$this->card($post),
            'excerpt' => $post->excerpt,
            'content' => $this->type->supportsFeature(PostType::CONTENT) ? $post->contentDocument()->toArray() : null,
            // Rendered by the Blade views of the blocks (media blocks resolve their files), for React's BlockContent html
            'content_html' => $this->type->supportsFeature(PostType::CONTENT) ? $post->contentDocument()->toHtml() : null,
            'meta' => $this->type->supportsFeature(PostType::META) ? $post->getAllMeta() : [],
            'status' => $post->status,
            'published' => $post->isPublished(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function term(Term $term): array
    {
        return [
            'id' => $term->getKey(),
            'name' => (string) $term->name,
            'slug' => $term->slug,
            'description' => $term->description,
            'taxonomy' => $term->taxonomy,
            'url' => $term->archiveUrl($this->type),
        ];
    }

    /**
     * The strings the public pages show, in the current locale
     *
     * @return array<string, string>
     */
    public function strings(): array
    {
        return [
            'archive' => $this->type->getPluralLabel(),
            'empty' => __('Nothing has been published here yet.'),
            'read_more' => __('Read more'),
            'previous' => __('Newer'),
            'next' => __('Older'),
            'by' => __('By :author', ['author' => ':author']),
            'published_on' => __('Published :date', ['date' => ':date']),
            'preview' => __('This is a preview. Visitors don\'t see this post yet.'),
            'back' => __('Back to :type', ['type' => $this->type->getPluralLabel()]),
            'pagination' => __('Pagination'),
        ];
    }

    /**
     * @return array<int, array{taxonomy: string, label: string, terms: array<int, array{name: string, url: string|null}>}>
     */
    protected function terms(Post $post): array
    {
        $groups = [];

        foreach ($this->type->getTaxonomies() as $key => $taxonomy) {
            $terms = $post->termsOf($key);

            if ($terms->isEmpty()) {
                continue;
            }

            $groups[] = [
                'taxonomy' => $key,
                'label' => $taxonomy->getPluralLabel(),
                'terms' => $terms->map(fn (Term $term) => [
                    'name' => (string) $term->name,
                    'url' => $term->archiveUrl($this->type),
                ])->values()->all(),
            ];
        }

        return $groups;
    }

    protected function authorName(?Model $author): ?string
    {
        if (! $author) {
            return null;
        }

        foreach (['name', 'display_name'] as $attribute) {
            if (filled($author->getAttribute($attribute))) {
                return (string) $author->getAttribute($attribute);
            }
        }

        return null;
    }
}
