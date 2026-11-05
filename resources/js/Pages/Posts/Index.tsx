import { router } from "@inertiajs/react"
import { BulkActionsTable } from "@javaabu/bulk-actions"
import { FilterBar, SortableHeading } from "@javaabu/filters"
import { Alert, LinkButton, Pagination, Status, TableCell, TableEmptyRow, TableHeading, TableRow } from "@javaabu/forms"
import { PostActions } from "../../components/PostActions"
import { PostTitle } from "../../components/PostTitle"
import { StatusTabs } from "../../components/StatusTabs"
import type { PostsIndexProps } from "../../types"

/** post-types::Posts/Index — a post type's posts, with status tabs, filters and bulk actions */
export default function Index({
  post_type,
  posts,
  rows,
  filters,
  bulk_actions,
  tabs,
  create_url,
  status,
  strings,
}: PostsIndexProps) {
  const hasAuthor = post_type.supports.includes("author")
  const hasTerms = post_type.taxonomies.length > 0
  const columns = 4 + (hasAuthor ? 1 : 0) + (hasTerms ? 1 : 0) + 1
  const filtered = filters.chips.length > 0

  return (
    <div className="flex flex-col gap-6" data-post-type={post_type.key}>
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div className="flex flex-col gap-1">
          <h1 className="text-2xl font-semibold">{strings.title}</h1>
          {strings.description ? <p className="text-muted-foreground">{strings.description}</p> : null}
        </div>
        {create_url ? (
          <LinkButton url={create_url} startIcon="plus">
            {strings.add}
          </LinkButton>
        ) : null}
      </div>

      {status ? (
        <Alert type="success">
          <span role="status">{status}</span>
        </Alert>
      ) : null}

      <StatusTabs tabs={tabs} label={strings.column_status} />

      <FilterBar filters={filters} />

      <BulkActionsTable
        bulkActions={bulk_actions}
        onRunFinished={() => router.reload()}
        headers={
          <>
            <SortableHeading filters={filters} sort="title" label={strings.column_title} />
            <TableHeading label={strings.column_status} />
            {hasAuthor ? <TableHeading label={strings.column_author} /> : null}
            {hasTerms ? <TableHeading label={strings.column_terms} /> : null}
            <SortableHeading filters={filters} sort="published_at" label={strings.column_date} />
            <TableHeading label={strings.actions} />
          </>
        }
        pagination={<Pagination paginator={posts} />}
      >
        {rows.length === 0 ? (
          <TableEmptyRow columns={columns}>{filtered ? strings.no_results : strings.empty}</TableEmptyRow>
        ) : (
          rows.map((post) => (
            <TableRow key={post.id} model={post} modelId={post.id} name={bulk_actions.name}>
              <TableCell label={strings.column_title}>
                <PostTitle post={post} strings={strings} />
              </TableCell>
              <TableCell label={strings.column_status}>
                <Status color={post.status_color} label={post.status_label} />
              </TableCell>
              {hasAuthor ? <TableCell label={strings.column_author} value={post.author ?? "-"} /> : null}
              {hasTerms ? (
                <TableCell label={strings.column_terms}>
                  {post.terms.map((group) => (
                    <div key={group.taxonomy} className="text-sm">
                      <span className="text-muted-foreground">{group.label}:</span>{" "}
                      <bdi>{group.terms.map((term) => term.name).join(", ")}</bdi>
                    </div>
                  ))}
                </TableCell>
              ) : null}
              <TableCell label={strings.column_date} value={post.published_at_label ?? "-"} />
              <TableCell label={strings.actions}>
                <PostActions post={post} strings={strings} />
              </TableCell>
            </TableRow>
          ))
        )}
      </BulkActionsTable>
    </div>
  )
}
