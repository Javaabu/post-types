<?php

namespace Javaabu\PostTypes\Tests\Http;

use Javaabu\Manifest\Facades\Manifest;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\Models\Term;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Taxonomy;
use Javaabu\PostTypes\Tests\TestSupport\Models\Admin;
use Javaabu\PostTypes\Tests\TestSupport\Models\User;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\TextInput;

trait InteractsWithPostTypes
{
    /**
     * Post types are registered before the routes, as in an app (providers boot first)
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('post-types.taxonomies', [
            Taxonomy::make('categories')->labels('Category', 'Categories')->hierarchical(),
            Taxonomy::make('tags'),
        ]);

        $app['config']->set('post-types.types', [
            PostType::make('news')
                ->labels('News article', 'News')
                ->supports(['content', 'excerpt', 'media', 'meta', 'author', 'scheduling'])
                ->taxonomies(['categories', 'tags'])
                ->metaFields(fn () => [TextInput::make('subtitle')->maxLength(100), Checkbox::make('featured')])
                ->permalink('{year}/{slug}'),
            PostType::make('page')->labels('Page', 'Pages')->supports(['content'])->archive(false),
        ]);
    }

    protected function defineRoutes($router): void
    {
        $router->get('login', fn () => 'login')->name('login');

        $router->middleware('web')->group(function ($router) {
            $router->postTypesAdmin(['prefix' => 'admin', 'name' => 'admin.']);
            $router->postTypesAdmin(['guard' => 'web_admin', 'prefix' => 'staff', 'name' => 'staff.']);
            $router->postTypes();
        });
    }

    protected function setUpPostTypes(): void
    {
        Manifest::fake();

        $this->artisan('permissions:sync');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function user(array $permissions = ['view_news', 'edit_news', 'delete_news', 'force_delete_news', 'publish_news']): User
    {
        $user = User::create([
            'name' => 'އަޙްމަދު',
            'email' => uniqid() . '@example.com',
            'password' => 'secret',
        ]);

        $user->givePermissionTo($permissions);

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function admin(array $permissions = ['view_news', 'edit_news', 'delete_news', 'force_delete_news', 'publish_news']): Admin
    {
        $admin = Admin::create([
            'name' => 'Aisha',
            'email' => uniqid() . '@example.com',
            'password' => 'secret',
        ]);

        $admin->givePermissionTo($permissions);

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makePost(array $attributes = []): Post
    {
        return Post::create([
            'type' => 'news',
            'title' => 'ރާއްޖޭގެ ޚަބަރު',
            ...$attributes,
        ]);
    }

    protected function term(string $name, string $taxonomy = 'categories', ?int $parent = null): Term
    {
        return Term::create(['taxonomy' => $taxonomy, 'name' => $name, 'parent_id' => $parent]);
    }
}
