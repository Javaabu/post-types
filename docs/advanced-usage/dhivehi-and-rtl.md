---
title: Dhivehi and RTL
sidebar_position: 7
---

# Translations

Every string the package shows is in `lang/en.json` and `lang/dv.json`, with the English string as the key. Pages, flash messages, statuses ("Draft", "Published", "Scheduled"), bulk actions, form labels and help all switch with the app's locale. The React pages get their strings translated from PHP, so they need no locale setup.

Publish the translations to change them: `php artisan vendor:publish --tag=post-types-translations` (to `lang/vendor/post-types`).

Your own labels (post types, taxonomies, statuses, meta fields) are translated with `__()` when shown, so add them to your app's `lang/dv.json`:

```json
{
    "News article": "ޚަބަރު",
    "News": "ޚަބަރުތައް",
    "Categories": "ބައިތައް"
}
```

Permission descriptions ("Publish news") are made from the English labels; add them to `lang/dv.json` too if you show them.

# RTL and Thaana

- The views and styles use logical CSS only (`ms-`, `pe-`, `start-`, `padding-inline-start`), checked by a test, so pages work right to left.
- Titles, excerpts, term names and descriptions use the Thaana keyboard; free text uses `dir="auto"` and `<bdi>`, so mixed Dhivehi and English shows correctly.
- Slugs keep Thaana with its fili (`ރާއްޖޭގެ-ޚަބަރު`), where `Str::slug()` would drop the vowels.
- Dates use Carbon's translated formats in the current locale.

# For review

These translations need a Dhivehi speaker's review:

| English | Dhivehi | Note |
| --- | --- | --- |
| Slug | ސްލަގް | Transliterated; a Dhivehi term may read better |
| Taxonomies | ބައިތައް | Used for "categories and tags" |
| Featured image | މައި ފޮޓޯ | |
| Move to trash / Trash / Trashed | ޓްރޭޝަށް ލާ / ޓްރޭޝް / ޓްރޭޝްކުރެވިފައި | Transliterated |
| Restore | އަނބުރާ ގެންނަ | |
| Pending review | ރިވިއުއަށް މަޑުކުރަނީ | |
| Scheduled | ޝެޑިއުލްކުރެވިފައި | Transliterated |
| By :author | :author ލިޔުއްވި | |
| Newer / Older | އާ ޕޯސްޓްތައް / ކުރީގެ ޕޯސްޓްތައް | Archive pagination |
| :type created. (and the other messages) | :type އުފެއްދިއްޖެ. | `:type` is the post type's label, so word order depends on it |
