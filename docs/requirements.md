---
title: Requirements
sidebar_position: 1.1
---

This package requires:

- PHP 8.4 or higher
- Laravel 12 or 13
- These Javaabu packages, installed with it:
  - [javaabu/stencil](https://github.com/Javaabu/stencil): the post and term forms
  - [javaabu/forms](https://github.com/Javaabu/forms) v2: the Blade pages
  - [javaabu/meta](https://github.com/Javaabu/meta): meta fields
  - [javaabu/mediapicker](https://github.com/Javaabu/mediapicker): the featured image and other media (it uses spatie/laravel-medialibrary)
  - [javaabu/block-editor](https://github.com/Javaabu/block-editor): the content
  - [javaabu/filters](https://github.com/Javaabu/filters) and [javaabu/bulk-actions](https://github.com/Javaabu/bulk-actions): the admin index
  - [javaabu/permissions](https://github.com/Javaabu/permissions) v3: permissions and policies
  - [javaabu/manifest](https://github.com/Javaabu/manifest): ships the styles and the React code to your app

Optional:

- [inertiajs/inertia-laravel](https://inertiajs.com) v2, for the Inertia React pages (`post-types.views` = `inertia`)
- [javaabu/translatable](https://github.com/Javaabu/translatable) v3, for posts and terms in several languages. See [Translations](advanced-usage/translations.md).
- [javaabu/dynamic-menubuilder](https://github.com/Javaabu/dynamic-menubuilder) v1, to add posts and archives to menus. See [Menus](advanced-usage/menus.md).

For the Inertia pages, your app needs React 19, [@javaabu/forms](https://github.com/Javaabu/react-forms), [@javaabu/onu](https://www.npmjs.com/package/@javaabu/onu), the npm packages of the block editor and the media picker, and manifest's Vite plugin, which resolves `@javaabu/post-types`, `@javaabu/stencil`, `@javaabu/filters`, `@javaabu/bulk-actions`, `@javaabu/block-editor` and `@javaabu/mediapicker`. Run `php artisan manifest:check` to see anything that's missing.
