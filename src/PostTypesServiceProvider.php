<?php

namespace Javaabu\PostTypes;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Javaabu\Manifest\Concerns\RegistersAssets;
use Javaabu\Manifest\Package;
use Javaabu\Permissions\Permissions;
use Javaabu\PostTypes\Assets\Assets;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Policies\PostPolicy;
use Javaabu\PostTypes\Policies\TermPolicy;
use Javaabu\PostTypes\Routing\PostTypeRoutes;

class PostTypesServiceProvider extends ServiceProvider
{
    use RegistersAssets;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/post-types.php', 'post-types');

        $this->app->singleton(PostTypesManager::class);
        $this->app->alias(PostTypesManager::class, 'post-types');
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'post-types');

        $this->registerFrontendAssets();
        $this->registerConfiguredTypes();
        $this->registerRouteMacros();
        $this->registerPermissions();

        if ($this->app->runningInConsole()) {
            if (PostTypes::$runs_migrations && config('post-types.run_migrations', true)) {
                $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            }

            $this->registerPublishing();
        }
    }

    /**
     * The Inertia pages (post-types::Posts/Index, …), the components apps
     * import as @javaabu/post-types, and the styles
     */
    protected function registerFrontendAssets(): void
    {
        $this->registerAssets(Assets::PACKAGE, __DIR__ . '/../resources', function (Package $package) {
            Assets::register($package);
        });
    }

    /**
     * The taxonomies and post types in config/post-types.php
     */
    protected function registerConfiguredTypes(): void
    {
        $manager = $this->app->make(PostTypesManager::class);

        $manager->taxonomy(array_values((array) config('post-types.taxonomies', [])));
        $manager->register(array_values((array) config('post-types.types', [])));
    }

    protected function registerRouteMacros(): void
    {
        Route::macro('postTypesAdmin', function (array $options = []) {
            PostTypeRoutes::registerAdmin($this, $options);
        });

        Route::macro('postTypes', function (array $options = []) {
            PostTypeRoutes::registerPublic($this, $options);
        });
    }

    /**
     * The permissions of every post type and taxonomy, and the default
     * policies, which the app can override. Post types registered by other
     * providers are included, so this waits until the app has booted.
     */
    protected function registerPermissions(): void
    {
        $post_policy = config('post-types.policies.post', PostPolicy::class);
        $term_policy = config('post-types.policies.term', TermPolicy::class);

        $this->app->booted(function () use ($post_policy, $term_policy) {
            $manager = $this->app->make(PostTypesManager::class);

            Permissions::policy(PostType::class, $post_policy);
            Permissions::policy(Taxonomy::class, $term_policy);

            foreach ($manager->modelClasses('post') as $model) {
                Permissions::policy($model, $post_policy);
            }

            foreach ($manager->modelClasses('term') as $model) {
                Permissions::policy($model, $term_policy);
            }

            if (! config('post-types.register_permissions', true)) {
                return;
            }

            foreach ($manager->types() as $type) {
                Permissions::register('post_type_' . $type->getKey(), $type->permissions(), label: $type->getPluralLabel());
            }

            foreach ($manager->taxonomies() as $taxonomy) {
                Permissions::register('taxonomy_' . $taxonomy->getKey(), $this->taxonomyPermissions($taxonomy), label: $taxonomy->getPluralLabel());
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function taxonomyPermissions(Taxonomy $taxonomy): array
    {
        $plural = mb_strtolower($taxonomy->getPluralLabel());

        return [
            $taxonomy->permissionFor('view') => "View {$plural}",
            $taxonomy->permissionFor('edit') => "Edit {$plural}",
            $taxonomy->permissionFor('delete') => "Delete {$plural}",
        ];
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../config/post-types.php' => config_path('post-types.php'),
        ], 'post-types-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'post-types-migrations');

        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/post-types'),
        ], 'post-types-translations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/post-types'),
        ], 'post-types-views');
    }
}
