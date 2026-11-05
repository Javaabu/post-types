---
title: Permissions
sidebar_position: 7
---

Each post type and taxonomy registers its permissions with [javaabu/permissions](https://github.com/Javaabu/permissions) when the app boots, in a group of its own (`post_type_news`, `taxonomy_categories`). Run the sync to create them, then give them to roles:

```bash
php artisan permissions:sync
```

For a post type whose permission resource is `news` (the key, snake case, plural; change it with `->permission()`):

| Permission | Lets users |
| --- | --- |
| `view_news` | see the admin list and posts |
| `edit_news` | create and edit posts |
| `delete_news` | move posts to the trash, see the trash, restore |
| `force_delete_news` | delete posts for good (and see the trash, restore) |
| `publish_news` | give posts a public status, set publish dates, run Publish and Move to drafts |
| `edit_others_news`, `delete_others_news` | with `->ownable()` only: edit and trash posts by other authors |

For a taxonomy whose resource is `categories`: `view_categories`, `edit_categories` (create and edit terms) and `delete_categories`.

The descriptions ("Publish news") come from the labels in English. Add their Dhivehi to your app's `lang/dv.json` if you show them.

# The policies

`Javaabu\PostTypes\Policies\PostPolicy` answers for posts and `PostType`s; `TermPolicy` for terms and `Taxonomy`s.

| Ability | Argument | Needs |
| --- | --- | --- |
| `viewAny` | `PostType` | `view_x` or `edit_x` |
| `view` | post | `view_x` or `edit_x` |
| `create` | `PostType` | `edit_x` |
| `update` | post | `edit_x`; with `ownable()`, also being the author or `edit_others_x` |
| `delete` | post | `delete_x`; with `ownable()`, also being the author or `delete_others_x` |
| `restore`, `viewTrash` | post, `PostType` | `delete_x` or `force_delete_x` |
| `forceDelete` | post | `force_delete_x` |
| `publish` | post or `PostType` | `publish_x` |

```php
$user->can('create', PostTypes::get('news'));
$user->can('publish', $post);
```

```blade
@can('viewAny', \Javaabu\PostTypes\Facades\PostTypes::get('news'))
    <a href="{{ route('admin.news.index') }}">{{ __('News') }}</a>
@endcan
```

Users can be any model on any guard: policies type-hint `Illuminate\Database\Eloquent\Model`, posts relate to their author through a morph (`author_type`, `author_id`), and the routes check the guard they were registered for. Permissions are created for every guard in `permissions.guards`.

# Overriding

Override a policy in `config('post-types.policies')`, `config('permissions.policies')` or with `Gate::policy()` in your app (the app wins over the package):

```php
// config/post-types.php
'policies' => [
    'post' => App\Policies\PostPolicy::class,
    'term' => Javaabu\PostTypes\Policies\TermPolicy::class,
],
```

```php
namespace App\Policies;

use Illuminate\Database\Eloquent\Model;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\Policies\PostPolicy as BasePostPolicy;

class PostPolicy extends BasePostPolicy
{
    public function update(Model $user, Post $post): bool
    {
        // Published pages are locked
        if ($post->type === 'page' && $post->isPublished()) {
            return false;
        }

        return parent::update($user, $post);
    }
}
```

The policy is registered for the post and term models (the package's, their translatable versions, the configured ones and each post type's `model()`), and for `PostType` and `Taxonomy`. Set `register_permissions` to `false` to register the permissions yourself.
