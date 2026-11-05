---
title: Translations
sidebar_position: 4
---

Posts and terms can be written in several languages with [javaabu/translatable](https://github.com/Javaabu/translatable). It's optional: without it (or with it turned off), posts have one language and everything else works the same.

# Setting up

```bash
composer require javaabu/translatable
```

Set up its languages (see its docs). When translatable is enabled and `post-types.models.post` / `.term` aren't set, the package uses its translatable models, which keep translations in the `translations` and `lang` columns the migration already adds:

| Model | Translatable fields |
| --- | --- |
| `Javaabu\PostTypes\Models\Translatable\Post` | `title`, `excerpt`, `content` |
| `Javaabu\PostTypes\Models\Translatable\Term` | `name`, `description` |

Slugs, statuses and dates are shared by every language. To translate more fields or use your own model, extend the translatable model and set it in the config.

# Forms

The post form shows one input per language for the title, excerpt and content (`title_en`, `title_dv`, …), and the term form for the name and description. The post's primary language is required; the others are optional. Dhivehi inputs are right to left with the Thaana keyboard.

Meta fields marked `->translatable()` get one input per language too (`meta[subtitle_en]`, `meta[subtitle_dv]`), stored by javaabu/meta one row per language:

```php
PostType::make('news')->metaFields([
    TextInput::make('subtitle')->translatable(),
    TextInput::make('code'),                      // one value for every language
]);
```

This works because `Post` implements stencil's `ResolvesTranslatableFields` (through javaabu/meta's `HasMeta`), which answers for the post's own fields and its meta fields.

# Reading

```php
$post->title;                               // the current language, falling back to the primary
$post->translate('title', 'dv');
$post->title_dv;
$post->contentDocument('dv');               // translated content as a Document (sanitized like the column)
$post->getMeta('subtitle', locale: 'dv');
```

The public pages show the current language (translatable's current locale). The admin index shows which languages each post has (`translations` in the row data).

# Turning it off

Set `Javaabu\PostTypes\Support\Translations::$enabled = false`, or turn translatable off in its config. Every check goes through `Support\Translations`, so nothing references translatable's classes when it isn't installed.
