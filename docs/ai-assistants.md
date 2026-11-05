---
title: AI assistants
sidebar_position: 1.25
---

The package ships guidance for AI coding assistants.

- **Laravel Boost**: `php artisan boost:install` (or `boost:update`) picks up the package's guideline (`resources/boost/guidelines/core.blade.php`) and the `javaabu-post-types` skill (`resources/boost/skills/javaabu-post-types`). The skill covers defining post types and taxonomies, meta fields, media and content, the routes and permalinks, permissions, templates, React, translations, menus and common mistakes.
- **llms.txt**: `vendor/javaabu/post-types/llms.txt` has the rules for using the package and an index of every docs page. Point an assistant at it when Boost isn't installed.
