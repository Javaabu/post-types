<?php

namespace Javaabu\PostTypes\Policies;

use Illuminate\Database\Eloquent\Model;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\PostType;

/**
 * Who may see and change posts, by the permissions of each post type
 * (`news` → view_news, edit_news, …)
 *
 * | Ability                          | Permission                                               |
 * | -------------------------------- | -------------------------------------------------------- |
 * | viewAny (a PostType)             | view_x or edit_x                                         |
 * | view (a post)                    | view_x or edit_x                                         |
 * | create (a PostType)              | edit_x                                                   |
 * | update (a post)                  | edit_x; with ownable(), also edit_others_x or the author |
 * | delete (trash a post)            | delete_x; with ownable(), also delete_others_x or the author |
 * | restore, viewTrash               | delete_x or force_delete_x                               |
 * | forceDelete                      | force_delete_x                                           |
 * | publish (a post or a PostType)   | publish_x                                                |
 *
 * Override it with `config('post-types.policies.post')`, `config('permissions.policies')`
 * or `Gate::policy()` for the post model and PostType.
 */
class PostPolicy
{
    public function viewAny(Model $user, PostType $type): bool
    {
        return $this->allows($user, $type, 'view', 'edit');
    }

    public function view(Model $user, Post $post): bool
    {
        $type = $post->postType();

        return $type !== null && $this->allows($user, $type, 'view', 'edit');
    }

    public function create(Model $user, PostType $type): bool
    {
        return $this->allows($user, $type, 'edit');
    }

    public function update(Model $user, Post $post): bool
    {
        $type = $post->postType();

        if (! $type || ! $this->allows($user, $type, 'edit')) {
            return false;
        }

        return $this->ownsOr($user, $post, $type, 'edit_others');
    }

    public function delete(Model $user, Post $post): bool
    {
        $type = $post->postType();

        if (! $type || ! $this->allows($user, $type, 'delete')) {
            return false;
        }

        return $this->ownsOr($user, $post, $type, 'delete_others');
    }

    public function restore(Model $user, Post $post): bool
    {
        $type = $post->postType();

        return $type !== null && $this->allows($user, $type, 'delete', 'force_delete');
    }

    public function forceDelete(Model $user, Post $post): bool
    {
        $type = $post->postType();

        return $type !== null && $this->allows($user, $type, 'force_delete');
    }

    public function viewTrash(Model $user, PostType $type): bool
    {
        return $this->allows($user, $type, 'delete', 'force_delete');
    }

    /**
     * Give a post a public status (or keep a published post published)
     */
    public function publish(Model $user, Post|PostType $post): bool
    {
        $type = $post instanceof PostType ? $post : $post->postType();

        return $type !== null && $this->allows($user, $type, 'publish');
    }

    /**
     * With ownable(), the author, or someone with the others permission
     */
    protected function ownsOr(Model $user, Post $post, PostType $type, string $others): bool
    {
        if (! $type->isOwnable()) {
            return true;
        }

        return $post->isOwnedBy($user) || $this->allows($user, $type, $others);
    }

    /**
     * Whether the user has any of the post type's permissions
     */
    protected function allows(Model $user, PostType $type, string ...$actions): bool
    {
        foreach ($actions as $action) {
            if ($user->can($type->permissionFor($action))) {
                return true;
            }
        }

        return false;
    }
}
