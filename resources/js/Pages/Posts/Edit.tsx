import { Alert, LinkButton, Status } from "@javaabu/forms"
import { StencilForm, type StencilComponents } from "@javaabu/stencil"
import { postFormComponents } from "../../components/fields"
import type { PostsEditProps } from "../../types"

export interface PostsEditPageProps extends PostsEditProps {
  /** More stencil components for custom meta or media fields */
  components?: StencilComponents
}

/** post-types::Posts/Edit — the form of a new or an existing post */
export default function Edit({ post_type, post, form, index_url, status, strings, components }: PostsEditPageProps) {
  return (
    <div className="flex flex-col gap-6" data-post-type={post_type.key}>
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div className="flex flex-col gap-1">
          <a href={index_url} className="text-muted-foreground text-sm hover:underline">
            {strings.back}
          </a>
          <h1 className="text-2xl font-semibold">{post ? strings.edit_title : strings.new_title}</h1>
          {post ? (
            <div className="flex flex-wrap items-center gap-2 text-sm">
              <Status color={post.status_color} label={post.status_label} />
              {post.published_at_label ? (
                <span className="text-muted-foreground">{post.published_at_label}</span>
              ) : null}
            </div>
          ) : null}
        </div>
        {post?.url ? (
          <LinkButton url={post.url} color="light" target="_blank" endIcon="external-link">
            {strings.view}
          </LinkButton>
        ) : null}
      </div>

      {status ? (
        <Alert type="success">
          <span role="status">{status}</span>
        </Alert>
      ) : null}

      <StencilForm key={post?.id ?? "new"} schema={form} components={{ ...postFormComponents, ...components }} />
    </div>
  )
}
