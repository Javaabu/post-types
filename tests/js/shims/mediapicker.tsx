// A stand-in for @javaabu/mediapicker in the tests and typecheck: the real module
// (resolved by javaabu/manifest in apps) needs Onu's media components.
import type { StencilComponentProps } from "@javaabu/stencil"

export function StencilMediaPicker({ node }: StencilComponentProps) {
  return <div data-stencil-mediapicker={node.name} />
}
