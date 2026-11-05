// The props the Inertia pages get from PHP (PostsController, TermsController,
// PublicPostsController). The same data the Blade views get.

import type { BulkActionsSchema } from "@javaabu/bulk-actions"
import type { FiltersData } from "@javaabu/filters"
import type { StencilSchema } from "@javaabu/stencil"

export interface StatusData {
  key: string
  label: string
  public: boolean
  color: string
}

export interface TaxonomyData {
  key: string
  singular: string
  plural: string
  description: string | null
  slug: string
  hierarchical: boolean
  multiple: boolean
  required: boolean
  public: boolean
  icon: string
}

export interface PostTypeData {
  key: string
  singular: string
  plural: string
  description: string | null
  icon: string
  supports: string[]
  taxonomies: TaxonomyData[]
  statuses: StatusData[]
  default_status: string
  slug: string
  permalink: string
  public: boolean
  has_archive: boolean
}

export interface TranslationStatus {
  locale: string
  translated: boolean
  primary: boolean
  url?: string | null
}

export interface TermGroup<T> {
  taxonomy: string
  label: string
  terms: T[]
}

/** A row of the admin index */
export interface PostRow {
  id: number
  title: string
  slug: string
  status: string
  status_label: string
  status_color: string
  published: boolean
  scheduled: boolean
  trashed: boolean
  published_at: string | null
  published_at_label: string | null
  updated_at: string | null
  updated_at_label: string | null
  author: string | null
  terms: TermGroup<{ id: number; name: string }>[]
  thumbnail: string | null
  translations: TranslationStatus[]
  url: string | null
  edit_url: string | null
  delete_url: string | null
  restore_url: string | null
  force_delete_url: string | null
}

/** A post on the edit page */
export interface PostDetails extends PostRow {
  excerpt: string | null
  content: unknown
  meta: Record<string, unknown>
  taxonomies: Record<string, number | number[] | null>
  featured_image: string | null
  created_at: string | null
}

export interface StatusTab {
  key: string
  label: string
  count: number
  url: string
  active: boolean
}

export interface PaginatedPosts {
  data: PostRow[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
  path: string
  first_page_url: string
  last_page_url: string
  next_page_url: string | null
  prev_page_url: string | null
  links: { url: string | null; label: string; active: boolean }[]
}

export type PostsStrings = Record<
  | "title"
  | "description"
  | "add"
  | "new_title"
  | "edit_title"
  | "back"
  | "empty"
  | "no_results"
  | "column_title"
  | "column_author"
  | "column_status"
  | "column_date"
  | "column_terms"
  | "view"
  | "edit"
  | "trash"
  | "restore"
  | "force_delete"
  | "confirm_trash"
  | "confirm_force_delete"
  | "no_title"
  | "actions",
  string
>

/** post-types::Posts/Index */
export interface PostsIndexProps {
  post_type: PostTypeData
  posts: PaginatedPosts
  rows: PostRow[]
  filters: FiltersData
  bulk_actions: BulkActionsSchema
  tabs: StatusTab[]
  create_url: string | null
  index_url: string
  status: string | null
  strings: PostsStrings
}

/** post-types::Posts/Edit */
export interface PostsEditProps {
  post_type: PostTypeData
  post: PostDetails | null
  form: StencilSchema
  index_url: string
  status: string | null
  strings: PostsStrings
}

export interface TermRow {
  id: number
  name: string
  slug: string
  description: string | null
  parent_id: number | null
  order: number
  translations: TranslationStatus[]
  edit_url: string | null
  delete_url: string | null
  depth: number
  posts_count: number
}

export type TermsStrings = Record<
  | "title"
  | "description"
  | "add"
  | "new_title"
  | "edit_title"
  | "back"
  | "empty"
  | "no_results"
  | "search"
  | "column_name"
  | "column_slug"
  | "column_posts"
  | "actions"
  | "edit"
  | "delete"
  | "confirm_delete",
  string
>

/** post-types::Terms/Index */
export interface TermsIndexProps {
  taxonomy: TaxonomyData
  terms: TermRow[]
  search: string
  create_url: string | null
  index_url: string
  status: string | null
  strings: TermsStrings
}

/** post-types::Terms/Edit */
export interface TermsEditProps {
  taxonomy: TaxonomyData
  term: Omit<TermRow, "depth" | "posts_count"> | null
  form: StencilSchema
  index_url: string
  status: string | null
  strings: TermsStrings
}

/** A post on the public pages */
export interface PublicPost {
  id: number
  title: string
  slug: string
  url: string | null
  summary: string | null
  published_at: string | null
  published_at_label: string | null
  author: string | null
  image: string | null
  terms: TermGroup<{ name: string; url: string | null }>[]
}

export interface PublicPostDetails extends PublicPost {
  excerpt: string | null
  content: unknown
  content_html: string | null
  meta: Record<string, unknown>
  status: string
  published: boolean
}

export interface PublicTerm {
  id: number
  name: string
  slug: string
  description: string | null
  taxonomy: string
  url: string | null
}

export type PublicStrings = Record<
  "archive" | "empty" | "read_more" | "previous" | "next" | "by" | "published_on" | "preview" | "back" | "pagination",
  string
>

export interface PublicPagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  prev_url: string | null
  next_url: string | null
}

/** post-types::Public/Archive and post-types::Public/Term */
export interface PublicArchiveProps {
  post_type: PostTypeData
  posts: PublicPost[]
  pagination: PublicPagination
  taxonomy: TaxonomyData | null
  term: PublicTerm | null
  title: string
  archive_url: string | null
  strings: PublicStrings
}

/** post-types::Public/Single */
export interface PublicSingleProps {
  post_type: PostTypeData
  post: PublicPostDetails
  preview: boolean
  archive_url: string | null
  strings: PublicStrings
}
