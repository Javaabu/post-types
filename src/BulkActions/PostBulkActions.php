<?php

namespace Javaabu\PostTypes\BulkActions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Javaabu\BulkActions\Actions\Action;
use Javaabu\BulkActions\Actions\DeleteAction;
use Javaabu\BulkActions\Actions\ForceDeleteAction;
use Javaabu\BulkActions\Actions\RestoreAction;
use Javaabu\BulkActions\BulkActions;
use Javaabu\PostTypes\Filters\PostFilters;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Status;

/**
 * The bulk actions of a post type's admin index (javaabu/bulk-actions): publish,
 * move to drafts, trash, restore and delete for good, each checked against
 * the post policy for every post
 *
 * They run during the request: bulk-actions' queued runs rebuild a set with
 * `make()`, which can't know the post type.
 */
class PostBulkActions extends BulkActions
{
    protected ?PostType $post_type = null;

    public static function for(PostType $post_type): static
    {
        return static::make()->postType($post_type);
    }

    public function postType(PostType $post_type): static
    {
        $this->post_type = $post_type;

        return $this
            ->model($post_type->getModel())
            ->name('posts')
            ->query(fn () => $post_type->query())
            ->filters(fn (Builder $query, array $filters) => PostFilters::for($post_type)->fromQuery($filters)->applyFilters($query))
            ->queueAfter(false);
    }

    public function getPostType(): ?PostType
    {
        return $this->post_type;
    }

    protected function actions(): array
    {
        $type = $this->post_type;

        if (! $type) {
            return [];
        }

        $public = $type->getPublicStatuses()[0] ?? null;
        $draft = $type->hasStatus(Status::DRAFT) ? Status::DRAFT : $type->getDefaultStatus();

        return array_values(array_filter([
            $public ? Action::make('publish')
                ->label(fn () => __('Publish'))
                ->icon('send')
                ->color('success')
                ->can('publish', [$type])
                ->canEach('update')
                ->successMessage(fn ($result) => trans_choice('{1} :count post published.|[0,*] :count posts published.', $result->succeeded, ['count' => $result->succeeded]))
                ->each(function (Post $post) use ($public) {
                    $post->status = $public;
                    $post->published_at ??= Carbon::now();
                    $post->save();
                }) : null,
            Action::make('unpublish')
                ->label(fn () => __('Move to drafts'))
                ->icon('file-pen')
                ->can('publish', [$type])
                ->canEach('update')
                ->successMessage(fn ($result) => trans_choice('{1} :count post moved to drafts.|[0,*] :count posts moved to drafts.', $result->succeeded, ['count' => $result->succeeded]))
                ->each(function (Post $post) use ($draft) {
                    $post->status = $draft;
                    $post->save();
                }),
            DeleteAction::make()->label(fn () => __('Move to trash')),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ]));
    }
}
