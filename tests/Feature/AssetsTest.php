<?php

namespace Javaabu\PostTypes\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Javaabu\Manifest\Enums\NpmScopes;
use Javaabu\Manifest\Manifest;
use Javaabu\PostTypes\Assets\Assets;
use Javaabu\PostTypes\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AssetsTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete(base_path('package.json'));

        parent::tearDown();
    }

    /**
     * manifest:check's rows for this package, for an app with these npm packages
     *
     * @param  array<string, string>  $dependencies
     * @param  array<string, mixed>  $options
     * @return array<string, array<string, mixed>>
     */
    protected function check(array $dependencies, array $options = []): array
    {
        File::put(base_path('package.json'), json_encode(['devDependencies' => (object) $dependencies]));

        Artisan::call('manifest:check', ['--json' => true, ...$options]);

        return collect(json_decode(Artisan::output(), true) ?? [])
            ->filter(fn (array $row) => in_array(Assets::PACKAGE, $row['needed_by'], true))
            // Every package with assets needs the build tools
            ->reject(fn (array $row) => in_array($row['name'], ['vite', 'laravel-vite-plugin'], true))
            ->keyBy('name')
            ->all();
    }

    #[Test]
    public function it_registers_the_styles_and_the_pages(): void
    {
        $manifest = $this->app->make(Manifest::class);

        $this->assertTrue($manifest->has(Assets::STYLES));
        $this->assertArrayHasKey('post-types::Posts/Index', $manifest->pages());
        $this->assertArrayHasKey('post-types::Public/Single', $manifest->pages());
    }

    #[Test]
    public function it_only_needs_npm_packages_for_its_pages(): void
    {
        $package = $this->app->make(Manifest::class)->package(Assets::PACKAGE);

        $this->assertSame([], $package->npmDependencies(NpmScopes::ASSETS));
        $this->assertSame([], $package->npmPeerDependencies(NpmScopes::ASSETS));
        $this->assertArrayHasKey('@javaabu/forms', $package->npmDependencies(NpmScopes::PAGES));
        $this->assertArrayHasKey('react', $package->npmPeerDependencies(NpmScopes::PAGES));
    }

    #[Test]
    public function it_doesnt_ask_blade_apps_for_react_packages(): void
    {
        $rows = $this->check(['vite' => '^8.0', 'laravel-vite-plugin' => '^3.0']);

        $this->assertSame([], $rows);
    }

    #[Test]
    public function it_asks_inertia_apps_for_what_its_pages_need(): void
    {
        $rows = $this->check(['@inertiajs/react' => '^2.3', 'vite' => '^8.0']);

        $this->assertEqualsCanonicalizing(['@inertiajs/react', '@javaabu/forms', '@javaabu/onu', 'react', 'react-dom'], array_keys($rows));
        // Other packages may still list it for their assets
        $this->assertContains('pages', $rows['@javaabu/forms']['scopes']);
    }
}
