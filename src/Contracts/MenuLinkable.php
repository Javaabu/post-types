<?php

namespace Javaabu\PostTypes\Contracts;

/*
 * javaabu/dynamic-menubuilder is optional. When it's installed, this is its
 * MenuLinkable contract, so posts can be added to menus; when it isn't, it's an
 * empty interface and posts work without it.
 */
if (interface_exists(\Javaabu\DynamicMenubuilder\Contracts\MenuLinkable::class)) {
    class_alias(\Javaabu\DynamicMenubuilder\Contracts\MenuLinkable::class, MenuLinkable::class);
} else {
    interface MenuLinkable {}
}
