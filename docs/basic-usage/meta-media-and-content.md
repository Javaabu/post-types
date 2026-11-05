---
title: Meta, media and content
sidebar_position: 3
---

# Meta fields

`metaFields()` adds fields stored with [javaabu/meta](https://github.com/Javaabu/meta). They're registered for the post model in a meta **group** named after the post type, so each post type has its own fields, and `Post::metaGroup()` returns the post's type.

```php
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\DatePicker;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\TextInput;

PostType::make('event')->metaFields([
    TextInput::make('subtitle')->maxLength(255)->translatable(),
    DatePicker::make('starts_on')->required(),
    Select::make('audience')->options(['public' => 'Public', 'staff' => 'Staff']),
    Checkbox::make('featured'),
    TextInput::make('tickets_url')->url()->visibleWhen('meta.featured', true),
    'capacity' => 'integer',
]);
```

The post form shows them in a "Details" section, nested under `meta` (inputs `meta[subtitle]`). Conditions inside take the full path: `visibleWhen('meta.featured', true)`. Values are typed by meta (dates, booleans, integers) and saved with the post.

```php
$post->getMeta('starts_on');            // Carbon
$post->setMeta('featured', true);
$post->getAllMeta();
Post::create(['type' => 'event', 'title' => '…', 'meta' => ['starts_on' => '2026-12-01']]);

Post::query()->ofType('event')->whereMeta('featured', true)->orderByMeta('starts_on')->get();
```

Pass a closure to `metaFields()` to build the fields when they're needed (with translated labels, or options from the database). See javaabu/meta's docs for types, queries and translatable fields.

# Media

With the `media` feature, posts have a **featured image**: the `featured_image` attachment collection of [javaabu/mediapicker](https://github.com/Javaabu/mediapicker), picked from the media library in the post form.

```php
$post->featuredImageUrl();              // the original, or null
$post->featuredImageUrl('card');        // an attachment conversion, once made
$post->getFirstAttachment(Post::FEATURED_IMAGE);
```

Add more pickers with `media()`. Each saves to the attachment collection it names (or its name); a picker without `multiple()` holds one file:

```php
use Javaabu\Mediapicker\Stencil\MediaPickerField;

PostType::make('news')->media([
    MediaPickerField::make('gallery')->mediaType('image')->multiple(),
    MediaPickerField::make('attachments')->collection('documents')->mediaType('document')->multiple(),
]);

$post->getAttachmentMedia('gallery');
```

Picked ids are validated with mediapicker's `LibraryMedia` rule: people can only attach media they may see. To declare conversions, extend the post model and add `registerAttachmentConversions()` (see mediapicker's docs), then point `post-types.models.post` or the post type's `model()` at it.

# Content

With the `content` feature, posts have a body edited with [javaabu/block-editor](https://github.com/Javaabu/block-editor). It's stored as TipTap JSON, cast to a `Document` and sanitized on save. The post type's `blockProfile()` picks the blocks the editor offers; mediapicker's image, gallery, file and video blocks are there when it's installed.

```php
$post->content;                         // Javaabu\BlockEditor\Document
$post->contentDocument()->toHtml();
$post->summary(200);                    // the excerpt, or the start of the content
```

```blade
<x-block-editor::content :content="$post->contentDocument()" />
```

```tsx
import { BlockContent } from "@javaabu/block-editor/react"

<BlockContent content={post.content} html={post.content_html} />
```

The public single page renders the content for you. `content_html` is rendered by the blocks' Blade views on the server, so blocks that resolve their files (mediapicker's) show the current URLs.
