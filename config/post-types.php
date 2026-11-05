<?php

use Javaabu\PostTypes\Http\Controllers\PostsController;
use Javaabu\PostTypes\Http\Controllers\PublicPostsController;
use Javaabu\PostTypes\Http\Controllers\TermsController;
use Javaabu\PostTypes\Policies\PostPolicy;
use Javaabu\PostTypes\Policies\TermPolicy;

return [

    /*
    |--------------------------------------------------------------------------
    | Post types and taxonomies
    |--------------------------------------------------------------------------
    |
    | Post type and taxonomy definitions: PostType or Taxonomy instances, or the
    | class names of subclasses. You can also register them from a service
    | provider with PostTypes::register() and PostTypes::taxonomy().
    |
    */

    'types' => [
        // App\PostTypes\NewsPostType::class,
    ],

    'taxonomies' => [
        // Javaabu\PostTypes\Taxonomy::make('categories')->hierarchical(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Permalinks
    |--------------------------------------------------------------------------
    |
    | Override a post type's URL base and permalink structure without changing
    | its definition, by post type key. A structure is relative to the base
    | and uses {slug}, {id}, {year}, {month}, {day} and taxonomy keys
    | ({categories}): 'news' => ['base' => 'blog', 'structure' => '{year}/{slug}'].
    |
    */

    'permalinks' => [
        // 'news' => ['base' => 'blog', 'structure' => '{year}/{month}/{slug}'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | The models and tables of posts and taxonomy terms. Extend the models to
    | add to them. Null models mean the package's own, or their translatable
    | versions when javaabu/translatable is enabled.
    |
    */

    'models' => [
        'post' => null,
        'term' => null,
    ],

    'tables' => [
        'posts' => 'posts',
        'terms' => 'post_terms',
        'post_term' => 'post_term',
    ],

    /*
    | Run the package's migrations. Turn off after publishing them.
    */

    'run_migrations' => true,

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | The stack the admin and public pages render with: blade (javaabu/forms
    | components and stencil's Blade driver) or inertia (React pages shipped
    | through javaabu/manifest). Requests that expect JSON always get JSON.
    |
    */

    'views' => env('POST_TYPES_VIEWS', 'blade'),

    /*
    | The Blade layouts the admin and public pages extend, and the section
    | they fill.
    */

    'layout' => 'layouts.app',

    'public_layout' => 'layouts.app',

    'layout_section' => 'content',

    /*
    | Rows per page on the admin index and on public archives.
    */

    'per_page' => 20,

    'archive_per_page' => 12,

    /*
    |--------------------------------------------------------------------------
    | Controllers and authorization
    |--------------------------------------------------------------------------
    |
    | Replace a controller with a subclass, or pass `controllers` to the route
    | macros. The policies are the package defaults; apps can also override
    | them with config('permissions.policies') or Gate::policy(). Turn off
    | register_permissions to register your own.
    |
    */

    'controllers' => [
        'posts' => PostsController::class,
        'terms' => TermsController::class,
        'public' => PublicPostsController::class,
    ],

    'policies' => [
        'post' => PostPolicy::class,
        'term' => TermPolicy::class,
    ],

    'register_permissions' => true,

];
