---
title: JavaScript API
sidebar_position: 6
---

The React code is in the package's `resources/js`. javaabu/manifest's Vite plugin lets your app import it as `@javaabu/post-types` and renders the pages as `post-types::Posts/Index` and so on. It isn't on npm.

```tsx
import {
  PostsIndex,
  PostsEdit,
  TermsIndex,
  TermsEdit,
  PublicArchive,
  PublicTerm,
  PublicSingle,
  PostCard,
  PostMeta,
  PostTerms,
  PostActions,
  PostTitle,
  StatusTabs,
  postFormComponents,
  format,
  type PostsIndexProps,
  type PublicSingleProps,
} from "@javaabu/post-types"
```

| Export | |
| --- | --- |
| `PostsIndex`, `PostsEdit`, `TermsIndex`, `TermsEdit` | The admin pages |
| `PublicArchive`, `PublicTerm`, `PublicSingle` | The public pages |
| `PostCard`, `PostMeta`, `PostTerms` | Parts of the public pages: a post in a list, its date and author, its terms |
| `PostTitle`, `PostActions`, `StatusTabs` | Parts of the admin index |
| `postFormComponents` | The stencil components the post form needs: `block-editor` (`StencilBlockEditor`) and `mediapicker` (`StencilMediaPicker`) |
| `format(text, replacements)` | Replaces `:name` placeholders in the translated strings |
| Types | `PostTypeData`, `TaxonomyData`, `StatusData`, `PostRow`, `PostDetails`, `TermRow`, `PublicPost`, `PublicPostDetails`, `PublicTerm`, and every page's props |

# Using the parts

Build your own public pages from the parts and the same props:

```tsx
// resources/js/pages/News/Show.tsx, used with ->pages(['single' => 'News/Show'])
import { BlockContent } from "@javaabu/block-editor/react"
import { PostMeta, PostTerms, type PublicSingleProps } from "@javaabu/post-types"

export default function Show({ post, strings }: PublicSingleProps) {
  return (
    <article dir="auto">
      <h1>{post.title}</h1>
      <PostMeta post={post} strings={strings} />
      <p>{String(post.meta.subtitle ?? "")}</p>
      <BlockContent html={post.content_html} />
      <PostTerms post={post} />
    </article>
  )
}
```

# Custom fields in the post form

`PostsEdit` renders the post form with stencil's `StencilForm` and `postFormComponents`. Pass more components for custom meta or media field types:

```tsx
<PostsEdit {...props} components={{ rating: RatingField }} />
```

Or register them for every form with stencil's `StencilProvider` at the root of your app.

# What the pages need

The admin pages use `@javaabu/forms` (inside your `FormsProvider` with the Inertia router), `@javaabu/filters`' `FilterBar` and `SortableHeading`, `@javaabu/bulk-actions`' `BulkActionsTable`, `@javaabu/stencil`'s `StencilForm`, and the block editor's and media picker's React fields. Wrap the app in the media picker's `MediapickerProvider`. `php artisan manifest:check` lists the npm packages your app is missing.
