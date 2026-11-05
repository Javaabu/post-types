import { Link } from "@inertiajs/react"
import type { PostRow, PostsStrings } from "../types"

/** The title cell: thumbnail, title (a link to edit) and slug */
export function PostTitle({ post, strings }: { post: PostRow; strings: PostsStrings }) {
  const title = post.title || strings.no_title

  return (
    <div className="flex items-center gap-3">
      {post.thumbnail ? (
        <img src={post.thumbnail} alt="" className="size-10 shrink-0 rounded object-cover" loading="lazy" />
      ) : null}
      <div className="flex flex-col">
        {post.edit_url ? (
          <Link href={post.edit_url} className="font-medium hover:underline" dir="auto">
            {title}
          </Link>
        ) : (
          <span className="font-medium" dir="auto">
            {title}
          </span>
        )}
        <span className="text-muted-foreground text-xs" dir="auto">
          {post.slug}
        </span>
      </div>
    </div>
  )
}
