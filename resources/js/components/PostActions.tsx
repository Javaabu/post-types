import { router } from "@inertiajs/react"
import { Button, LinkButton } from "@javaabu/forms"
import type { PostRow, PostsStrings } from "../types"

export interface PostActionsProps {
  post: PostRow
  strings: PostsStrings
  /** Asks before trashing or deleting; `window.confirm` by default */
  confirm?: (message: string) => boolean
}

/** View, edit, trash, restore and delete for good, as the user may */
export function PostActions({ post, strings, confirm = (message) => window.confirm(message) }: PostActionsProps) {
  const visit = (url: string, method: "delete" | "post", question?: string) => {
    if (question && !confirm(question)) return
    router.visit(url, { method, preserveScroll: true })
  }

  return (
    <div className="flex flex-wrap gap-2">
      {post.url ? (
        <LinkButton url={post.url} size="sm" color="light" target="_blank">
          {strings.view}
        </LinkButton>
      ) : null}
      {post.edit_url ? (
        <LinkButton url={post.edit_url} size="sm" color="light">
          {strings.edit}
        </LinkButton>
      ) : null}
      {post.delete_url ? (
        <Button
          type="button"
          size="sm"
          color="light"
          onClick={() => visit(post.delete_url!, "delete", strings.confirm_trash)}
        >
          {strings.trash}
        </Button>
      ) : null}
      {post.restore_url ? (
        <Button type="button" size="sm" color="light" onClick={() => visit(post.restore_url!, "post")}>
          {strings.restore}
        </Button>
      ) : null}
      {post.force_delete_url ? (
        <Button
          type="button"
          size="sm"
          color="danger"
          onClick={() => visit(post.force_delete_url!, "delete", strings.confirm_force_delete)}
        >
          {strings.force_delete}
        </Button>
      ) : null}
    </div>
  )
}
