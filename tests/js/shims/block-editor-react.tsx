// Stand-ins for @javaabu/block-editor/react in the tests and typecheck: the real
// module (resolved by javaabu/manifest in apps) needs TipTap. Same names and props.
import type { StencilComponentProps } from "@javaabu/stencil"

export interface BlockContentProps {
  content?: unknown
  html?: string | null
  className?: string
}

export function BlockContent({ content, html, className }: BlockContentProps) {
  if (html) return <div className={className} data-block-content="" dangerouslySetInnerHTML={{ __html: html }} />
  return <div className={className} data-block-content="" data-content={JSON.stringify(content ?? null)} />
}

export function StencilBlockEditor({ node }: StencilComponentProps) {
  return <div data-stencil-block-editor={node.name} />
}
