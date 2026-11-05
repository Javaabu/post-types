import { Link, router } from "@inertiajs/react"
import {
  Alert,
  Button,
  LinkButton,
  SearchForm,
  Table,
  TableCell,
  TableEmptyRow,
  TableHeading,
  TableRow,
} from "@javaabu/forms"
import type { TermsIndexProps } from "../../types"

export interface TermsIndexPageProps extends TermsIndexProps {
  /** Asks before deleting; `window.confirm` by default */
  confirm?: (message: string) => boolean
}

/** post-types::Terms/Index — a taxonomy's terms as a tree, with a search */
export default function Index({
  taxonomy,
  terms,
  search,
  create_url,
  index_url,
  status,
  strings,
  confirm = (message) => window.confirm(message),
}: TermsIndexPageProps) {
  const remove = (url: string) => {
    if (!confirm(strings.confirm_delete)) return
    router.delete(url, { preserveScroll: true })
  }

  return (
    <div className="flex flex-col gap-6" data-taxonomy={taxonomy.key}>
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

      <SearchForm action={index_url} placeholder={strings.search} model={{ search }} />

      <Table
        noCheckbox
        headers={
          <>
            <TableHeading label={strings.column_name} />
            <TableHeading label={strings.column_slug} />
            <TableHeading label={strings.column_posts} />
            <TableHeading label={strings.actions} />
          </>
        }
      >
        {terms.length === 0 ? (
          <TableEmptyRow columns={4} noCheckbox>
            {search ? strings.no_results : strings.empty}
          </TableEmptyRow>
        ) : (
          terms.map((term) => (
            <TableRow key={term.id} model={term} modelId={term.id} noCheckbox data-term={term.id}>
              <TableCell label={strings.column_name}>
                <span
                  className="font-medium"
                  dir="auto"
                  data-depth={term.depth}
                  style={{ paddingInlineStart: `${term.depth * 1.25}rem` }}
                >
                  {term.depth > 0 ? (
                    <span className="text-muted-foreground" aria-hidden="true">
                      {"— ".repeat(term.depth)}
                    </span>
                  ) : null}
                  {term.edit_url ? (
                    <Link href={term.edit_url} className="hover:underline">
                      {term.name}
                    </Link>
                  ) : (
                    term.name
                  )}
                </span>
              </TableCell>
              <TableCell label={strings.column_slug}>
                <bdi>{term.slug}</bdi>
              </TableCell>
              <TableCell label={strings.column_posts} value={String(term.posts_count)} />
              <TableCell label={strings.actions}>
                <div className="flex flex-wrap gap-2">
                  {term.edit_url ? (
                    <LinkButton url={term.edit_url} size="sm" color="light">
                      {strings.edit}
                    </LinkButton>
                  ) : null}
                  {term.delete_url ? (
                    <Button type="button" size="sm" color="light" onClick={() => remove(term.delete_url!)}>
                      {strings.delete}
                    </Button>
                  ) : null}
                </div>
              </TableCell>
            </TableRow>
          ))
        )}
      </Table>
    </div>
  )
}
