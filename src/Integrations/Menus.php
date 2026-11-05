<?php

namespace Javaabu\PostTypes\Integrations;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\DynamicMenubuilder\Facades\Menus as MenusFacade;
use Javaabu\DynamicMenubuilder\MenuManager;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Routing\PostTypeRoutes;

/**
 * Optional javaabu/dynamic-menubuilder support: every public post type is a
 * source of menu items (its posts, and its archive as the index page), keyed
 * by the post type's key
 */
class Menus
{
    /**
     * Whether javaabu/dynamic-menubuilder is installed and its provider registered
     */
    public static function installed(): bool
    {
        return class_exists(MenuManager::class) && app()->bound(MenuManager::class);
    }

    /**
     * Register a source per public post type, now and for post types registered later
     */
    public static function register(): void
    {
        if (! static::installed() || ! config('post-types.menus', true)) {
            return;
        }

        PostTypes::registered(function (PostType $type) {
            static::registerType($type);
        });
    }

    public static function registerType(PostType $type): void
    {
        if (! $type->isPublic()) {
            return;
        }

        $key = $type->getKey();

        MenusFacade::linkable($type->getModel(), $key)
            ->label(fn () => $type->getPluralLabel())
            ->query(fn (Builder $query) => $query->ofType($key))
            ->indexUrl(fn () => PostTypeRoutes::archiveUrl($type))
            ->indexTitle(fn () => $type->getPluralLabel());
    }
}
