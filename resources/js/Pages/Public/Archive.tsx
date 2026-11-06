// The package styles, so Vite bundles them with any page that renders this
import "../../../css/post-types.css"
import { PostCard } from "../../components/PostCard"
import type { PublicArchiveProps } from "../../types"

/** post-types::Public/Archive — a post type's published posts, newest first */
export default function Archive({
  post_type,
  posts,
  pagination,
  term,
  title,
  archive_url,
  strings,
}: PublicArchiveProps) {
  return (
    <div className="flex flex-col gap-6" data-post-type={post_type.key}>
      <header className="flex flex-col gap-1">
        {term && archive_url ? (
          <a href={archive_url} className="text-sm hover:underline">
            {strings.back}
          </a>
        ) : null}
        <h1 className="text-3xl font-semibold" dir="auto">
          {title}
        </h1>
        {term?.description ? <p dir="auto">{term.description}</p> : null}
      </header>

      {posts.length > 0 ? (
        <div className="post-types-archive">
          {posts.map((post) => (
            <PostCard key={post.id} post={post} strings={strings} />
          ))}
        </div>
      ) : (
        <p>{strings.empty}</p>
      )}

      {pagination.prev_url || pagination.next_url ? (
        <nav className="post-types-pagination" aria-label={strings.pagination}>
          {pagination.prev_url ? (
            <a href={pagination.prev_url} rel="prev">
              {strings.previous}
            </a>
          ) : (
            <span />
          )}
          {pagination.next_url ? (
            <a href={pagination.next_url} rel="next">
              {strings.next}
            </a>
          ) : null}
        </nav>
      ) : null}
    </div>
  )
}
