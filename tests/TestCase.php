<?php

namespace Javaabu\PostTypes\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Javaabu\BlockEditor\BlockEditorServiceProvider;
use Javaabu\BulkActions\BulkActionsServiceProvider;
use Javaabu\DynamicMenubuilder\DynamicMenubuilderServiceProvider;
use Javaabu\Filters\FiltersServiceProvider;
use Javaabu\Forms\FormsServiceProvider;
use Javaabu\Manifest\ManifestServiceProvider;
use Javaabu\Mediapicker\MediapickerServiceProvider;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Meta\MetaServiceProvider;
use Javaabu\Permissions\Models\Permission;
use Javaabu\Permissions\Models\Role;
use Javaabu\Permissions\PermissionsServiceProvider;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\PostTypesManager;
use Javaabu\PostTypes\PostTypesServiceProvider;
use Javaabu\PostTypes\Routing\PostTypeRoutes;
use Javaabu\PostTypes\Support\Translations;
use Javaabu\PostTypes\Tests\TestSupport\Models\Admin;
use Javaabu\PostTypes\Tests\TestSupport\Models\User;
use Javaabu\PostTypes\Tests\TestSupport\Providers\TestServiceProvider;
use Javaabu\Stencil\StencilServiceProvider;
use Javaabu\Translatable\TranslatableServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];

        $config->set('app.key', 'base64:yWa/ByhLC/GUvfToOuaPD7zDwB64qkc/QkaQOrT5IpE=');
        $config->set('database.default', 'testing');
        $config->set('session.serialization', 'php');
        $config->set('permission.models.permission', Permission::class);
        $config->set('permission.models.role', Role::class);
        $config->set('permissions.guards', ['web', 'web_admin']);
        $config->set('media-library.media_model', Media::class);
        $config->set('media-library.queue_conversions_by_default', false);
        $config->set('inertia.testing.ensure_pages_exist', false);

        $config->set('auth.guards', [
            'web' => ['driver' => 'session', 'provider' => 'users'],
            'web_admin' => ['driver' => 'session', 'provider' => 'admins'],
        ]);

        $config->set('auth.providers', [
            'users' => ['driver' => 'eloquent', 'model' => User::class],
            'admins' => ['driver' => 'eloquent', 'model' => Admin::class],
        ]);

        $config->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => sys_get_temp_dir() . '/post-types-tests/media',
            'url' => '/media',
        ]);
    }

    protected function getPackageProviders($app): array
    {
        $providers = [
            PermissionServiceProvider::class,
            PermissionsServiceProvider::class,
            ManifestServiceProvider::class,
            FormsServiceProvider::class,
            StencilServiceProvider::class,
            MetaServiceProvider::class,
            BlockEditorServiceProvider::class,
            MediaLibraryServiceProvider::class,
            MediapickerServiceProvider::class,
            FiltersServiceProvider::class,
            BulkActionsServiceProvider::class,
            PostTypesServiceProvider::class,
            TestServiceProvider::class,
        ];

        if (class_exists(InertiaServiceProvider::class)) {
            $providers[] = InertiaServiceProvider::class;
        }

        if (class_exists(DynamicMenubuilderServiceProvider::class)) {
            $providers[] = DynamicMenubuilderServiceProvider::class;
        }

        if (class_exists(TranslatableServiceProvider::class)) {
            $providers[] = TranslatableServiceProvider::class;
        }

        return $providers;
    }

    protected function tearDown(): void
    {
        PostTypes::clearResolvedInstances();
        PostTypeRoutes::flush();
        Translations::$enabled = true;

        parent::tearDown();
    }

    protected function postTypes(): PostTypesManager
    {
        return $this->app->make(PostTypesManager::class);
    }
}
