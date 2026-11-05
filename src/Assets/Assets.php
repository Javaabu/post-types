<?php

namespace Javaabu\PostTypes\Assets;

use Javaabu\Manifest\Package;

/**
 * The package's frontend assets, registered with javaabu/manifest
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
            ->npm([
                '@javaabu/forms' => 'github:Javaabu/react-forms#next-gen',
                '@javaabu/onu' => '^0.2.1',
            ], [
                'react' => '^19.0',
                'react-dom' => '^19.0',
                '@inertiajs/react' => '^2.0 || ^3.0',
            ]);
    }
}
