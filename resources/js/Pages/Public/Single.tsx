import { BlockContent } from "@javaabu/block-editor/react"
import { Alert } from "@javaabu/forms"
import { PostMeta, PostTerms } from "../../components/PostCard"
import type { PublicSingleProps } from "../../types"

/** post-types::Public/Single — a post */
export default function Single({ post_type, post, preview, archive_url, strings }: PublicSingleProps) {
  return (
    <article className="flex flex-col gap-6" data-post-type={post_type.key} data-post={post.id}>
      {preview ? (
        <Alert type="warning">
          <span role="status">{strings.preview}</span>
        </Alert>
      ) : null}

      <header className="flex flex-col gap-2">
        {archive_url ? (
          <a href={archive_url} className="text-sm hover:underline">
            {strings.back}
          </a>
        ) : null}
        <h1 className="text-3xl font-semibold" dir="auto">
          {post.title}
        </h1>
        <PostMeta post={post} strings={strings} />
      </header>

      {post.image ? (
        <figure className="post-types-single__image">
          <img src={post.image} alt="" />
        </figure>
      ) : null}

      {post.content !== null ? (
        <div className="post-types-content">
          <BlockContent html={post.content_html} />
        </div>
      ) : post.excerpt ? (
        <p dir="auto">{post.excerpt}</p>
      ) : null}

      <PostTerms post={post} />
    </article>
  )
}
