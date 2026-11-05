import { format } from "../format"
import type { PublicPost, PublicStrings } from "../types"

/** The date and author of a post */
export function PostMeta({ post, strings }: { post: PublicPost; strings: PublicStrings }) {
  return (
    <div className="post-types-meta">
      {post.published_at ? <time dateTime={post.published_at}>{post.published_at_label}</time> : null}
      {post.author ? <span>{format(strings.by, { author: post.author })}</span> : null}
    </div>
  )
}

/** The terms of a post, linked to their archives */
export function PostTerms({ post }: { post: PublicPost }) {
  return (
    <>
      {post.terms.map((group) => (
        <ul key={group.taxonomy} className="post-types-terms" aria-label={group.label}>
          {group.terms.map((term) => (
            <li key={term.name}>
              {term.url ? (
                <a href={term.url} className="hover:underline">
                  <bdi>{term.name}</bdi>
                </a>
              ) : (
                <bdi>{term.name}</bdi>
              )}
            </li>
          ))}
        </ul>
      ))}
    </>
  )
}

/** A post in an archive: image, title, date, author and summary */
export function PostCard({ post, strings }: { post: PublicPost; strings: PublicStrings }) {
  return (
    <article className="post-types-card" data-post={post.id}>
      {post.image ? (
        <a href={post.url ?? undefined} tabIndex={-1} aria-hidden="true">
          <img src={post.image} alt="" loading="lazy" />
        </a>
      ) : null}
      <h2 className="text-lg font-semibold" dir="auto">
        {post.url ? (
          <a href={post.url} className="hover:underline">
            {post.title}
          </a>
        ) : (
          post.title
        )}
      </h2>
      <PostMeta post={post} strings={strings} />
      {post.summary ? <p dir="auto">{post.summary}</p> : null}
    </article>
  )
}
