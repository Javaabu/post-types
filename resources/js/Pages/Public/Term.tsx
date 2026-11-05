import Archive from "./Archive"
import type { PublicArchiveProps } from "../../types"

/** post-types::Public/Term — the archive of a term's posts */
export default function Term(props: PublicArchiveProps) {
  return <Archive {...props} />
}
