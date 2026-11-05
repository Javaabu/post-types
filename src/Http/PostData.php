<?php

namespace Javaabu\PostTypes\Http;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\Models\Term;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Routing\PostTypeRoutes;
use Javaabu\PostTypes\Support\Translations;

/**
 * What the admin pages and JSON responses get for a post: the same in Blade,
 * React and JSON
 */
class PostData
{
    public function __construct(
        protected Request $request,
        protected PostType $type,
    ) {}

    public static function make(Request $request, PostType $type): static
    {
        return new static($request, $type);
    }

    /**
     * A row of the index
     *
     * @return array<string, mixed>
     */
    public function row(Post $post): array
    {
        $gate = $this->gate();
        $trashed = $post->trashed();
        $id = $post->getKey();

        return [
            'id' => $id,
            'title' => (string) $post->title,
            'slug' => $post->slug,
            'status' => $post->status,
            'status_label' => $post->displayStatus(),
            'status_color' => $post->displayStatusColor(),
            'published' => $post->isPublished(),
            'scheduled' => $post->isScheduled(),
            'trashed' => $trashed,
            'published_at' => $post->published_at?->toIso8601String(),
            'published_at_label' => $post->published_at?->translatedFormat('j M Y, H:i'),
            'updated_at' => $post->updated_at?->toIso8601String(),
            'updated_at_label' => $post->updated_at?->diffForHumans(),
            'author' => $this->authorName($post->author),
            'terms' => $this->terms($post),
            'thumbnail' => $this->type->supportsFeature(PostType::MEDIA) ? $this->thumbnail($post) : null,
            'translations' => Translations::status($post),
            'url' => $trashed ? null : $post->permalink(),
            'edit_url' => ! $trashed && $gate->allows('update', $post) ? $this->route('edit', $id) : null,
            'delete_url' => ! $trashed && $gate->allows('delete', $post) ? $this->route('destroy', $id) : null,
            'restore_url' => $trashed && $gate->allows('restore', $post) ? $this->route('restore', $id) : null,
            'force_delete_url' => $trashed && $gate->allows('forceDelete', $post) ? $this->route('force-delete', $id) : null,
        ];
    }

    /**
     * A post with its content, excerpt, meta and term ids, for the edit page and JSON
     *
     * @return array<string, mixed>
     */
    public function full(Post $post): array
    {
        $post->loadMissing(['terms', 'meta']);

        return [
            ...$this->row($post),
            'excerpt' => $post->excerpt,
            'content' => $post->contentDocument()->toArray(),
            'meta' => $post->getAllMeta(),
            'taxonomies' => $post->taxonomies,
            'featured_image' => $this->type->supportsFeature(PostType::MEDIA) ? $post->featuredImageUrl() : null,
            'created_at' => $post->created_at?->toIso8601String(),
        ];
    }

    /**
     * The strings the admin pages show, in the current locale
     *
     * @return array<string, string>
     */
    public function strings(): array
    {
        $type = $this->type;

        return [
            'title' => $type->getPluralLabel(),
            'description' => (string) $type->getDescription(),
            'add' => __('Add :type', ['type' => $type->getSingularLabel()]),
            'new_title' => __('New :type', ['type' => $type->getSingularLabel()]),
            'edit_title' => __('Edit :type', ['type' => $type->getSingularLabel()]),
            'back' => __('Back to :type', ['type' => $type->getPluralLabel()]),
            'empty' => __('No :type yet.', ['type' => mb_strtolower($type->getPluralLabel())]),
            'no_results' => __('Nothing matches these filters.'),
            'column_title' => __('Title'),
            'column_author' => __('Author'),
            'column_status' => __('Status'),
            'column_date' => __('Date'),
            'column_terms' => __('Taxonomies'),
            'view' => __('View'),
            'edit' => __('Edit'),
            'trash' => __('Move to trash'),
            'restore' => __('Restore'),
            'force_delete' => __('Delete for good'),
            'confirm_trash' => __('Move this post to the trash?'),
            'confirm_force_delete' => __('Delete this post for good? This can\'t be undone.'),
            'no_title' => __('(no title)'),
            'actions' => __('Actions'),
        ];
    }

    /**
     * @return array<int, array{taxonomy: string, label: string, terms: array<int, array{id: mixed, name: string}>}>
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
                'terms' => $terms->map(fn (Term $term) => ['id' => $term->getKey(), 'name' => (string) $term->name])->values()->all(),
            ];
        }

        return $groups;
    }

    protected function thumbnail(Post $post): ?string
    {
        $attachment = $post->getFirstAttachment(Post::FEATURED_IMAGE);

        if (! $attachment) {
            return null;
        }

        return method_exists($attachment, 'getAvailableUrl')
            ? $attachment->getAvailableUrl(['mediapicker-thumb', 'thumb'])
            : $attachment->getUrl();
    }

    protected function authorName(?Model $author): ?string
    {
        if (! $author) {
            return null;
        }

        foreach (['name', 'display_name', 'email'] as $attribute) {
            if (filled($author->getAttribute($attribute))) {
                return (string) $author->getAttribute($attribute);
            }
        }

        return '#' . $author->getKey();
    }

    protected function route(string $action, mixed $id): string
    {
        return route(PostTypeRoutes::namePrefix($this->request) . $this->type->getKey() . '.' . $action, $id);
    }

    protected function gate(): Gate
    {
        $gate = app(Gate::class);
        $user = $this->request->user(PostTypeRoutes::guard($this->request));

        return $user ? $gate->forUser($user) : $gate;
    }
}
