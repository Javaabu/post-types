<?php

namespace Javaabu\PostTypes\Assets;

use Javaabu\Manifest\Enums\NpmScopes;
use Javaabu\Manifest\Package;

/**
 * The package's frontend assets, registered with javaabu/manifest: the
 * styles (Blade and Inertia), the Inertia pages and the React code apps import
 * as @javaabu/post-types, and the npm packages only the pages and React code need
 */
class Assets
{
    public const string PACKAGE = 'post-types';

    /** Styles for the admin and public pages (logical properties only) */
    public const string STYLES = 'post-types::styles';

    public static function register(Package $package): void
    {
        $package
            ->js('js')
            ->pages()
            ->dist('../dist')
            ->style('styles', 'css/post-types.css')
            // The styles import nothing; only the Inertia pages and the
            // components apps import as @javaabu/post-types need these, so
            // Blade apps' manifest:check doesn't ask for them
            ->npm([
                '@javaabu/forms' => 'github:Javaabu/react-forms#next-gen',
                '@javaabu/onu' => '^0.2.1',
            ], peer_dependencies: [
                'react' => '^19.0',
                'react-dom' => '^19.0',
                '@inertiajs/react' => '^2.0 || ^3.0',
            ], for: NpmScopes::PAGES);
    }
}
