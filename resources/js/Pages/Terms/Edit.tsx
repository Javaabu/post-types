import { Alert } from "@javaabu/forms"
import { StencilForm } from "@javaabu/stencil"
import type { TermsEditProps } from "../../types"

/** post-types::Terms/Edit — the form of a new or an existing term */
export default function Edit({ taxonomy, term, form, index_url, status, strings }: TermsEditProps) {
  return (
    <div className="flex flex-col gap-6" data-taxonomy={taxonomy.key}>
      <div className="flex flex-col gap-1">
        <a href={index_url} className="text-muted-foreground text-sm hover:underline">
          {strings.back}
        </a>
        <h1 className="text-2xl font-semibold">{term ? strings.edit_title : strings.new_title}</h1>
      </div>

      {status ? (
        <Alert type="success">
          <span role="status">{status}</span>
        </Alert>
      ) : null}

      <StencilForm key={term?.id ?? "new"} schema={form} />
    </div>
  )
}
