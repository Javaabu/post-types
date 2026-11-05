import { StencilBlockEditor } from "@javaabu/block-editor/react"
import { StencilMediaPicker } from "@javaabu/mediapicker"
import type { StencilComponents } from "@javaabu/stencil"

/**
 * The stencil node types the post form uses besides the built-ins: the content
 * (javaabu/block-editor) and the media pickers (javaabu/mediapicker)
 */
export const postFormComponents: StencilComponents = {
  "block-editor": StencilBlockEditor,
  mediapicker: StencilMediaPicker,
}
