import { fireEvent, render, screen, within } from "@testing-library/react"
import type * as React from "react"
import { beforeEach, describe, expect, it, vi } from "vitest"
import {
  PostsEdit,
  PostsIndex,
  PublicArchive,
  PublicSingle,
  PublicTerm,
  TermsEdit,
  TermsIndex,
  type PostsEditProps,
  type PostsIndexProps,
  type PublicArchiveProps,
  type PublicSingleProps,
  type TermsEditProps,
  type TermsIndexProps,
} from "@javaabu/post-types"
import postsEdit from "./fixtures/posts-edit.json"
import postsIndex from "./fixtures/posts-index.json"
import publicArchive from "./fixtures/public-archive.json"
import publicSingle from "./fixtures/public-single.json"
import termsEdit from "./fixtures/terms-edit.json"
import termsIndex from "./fixtures/terms-index.json"

const visit = vi.fn()
const destroy = vi.fn()

vi.mock("@inertiajs/react", () => ({
  router: {
    visit: (...args: unknown[]) => visit(...args),
    delete: (...args: unknown[]) => destroy(...args),
    reload: vi.fn(),
  },
  Link: ({ href, children, ...props }: React.ComponentProps<"a">) => (
    <a href={href} {...props}>
      {children}
    </a>
  ),
}))

beforeEach(() => {
  visit.mockReset()
  destroy.mockReset()
})

describe("post-types::Posts/Index", () => {
  const props = postsIndex as unknown as PostsIndexProps

  it("lists the posts with their status, author and terms", () => {
    render(<PostsIndex {...props} />)

    expect(screen.getByRole("heading", { name: "News" })).toBeInTheDocument()
    expect(screen.getByRole("link", { name: "ރާއްޖޭގެ ޚަބަރު" })).toHaveAttribute("href", props.rows[0]!.edit_url!)
    expect(screen.getByRole("link", { name: "ޑްރާފްޓް" })).toBeInTheDocument()
    expect(screen.getByRole("link", { name: "ރާއްޖޭގެ ޚަބަރު" }).closest("tr")!.querySelector("bdi")).toHaveTextContent(
      "ކުޅިވަރު",
    )
    expect(screen.getAllByText("އަޙްމަދު").length).toBeGreaterThan(0)
    expect(screen.getByRole("link", { name: /Add News article/ })).toHaveAttribute("href", props.create_url!)
  })

  it("shows the status tabs with counts", () => {
    render(<PostsIndex {...props} />)

    const tabs = screen.getByRole("navigation", { name: "Status" })
    expect(within(tabs).getByText("All").closest("a")).toHaveAttribute("aria-current", "page")
    expect(tabs.querySelector("[data-tab=trashed]")).toBeInTheDocument()
  })

  it("asks before moving a post to the trash", () => {
    const confirm = vi.spyOn(window, "confirm").mockReturnValueOnce(false).mockReturnValueOnce(true)

    render(<PostsIndex {...props} />)

    const row = screen.getByRole("link", { name: "ޑްރާފްޓް" }).closest("tr")!
    fireEvent.click(within(row).getByRole("button", { name: "Move to trash" }))
    expect(visit).not.toHaveBeenCalled()

    fireEvent.click(within(row).getByRole("button", { name: "Move to trash" }))
    expect(visit).toHaveBeenCalledWith(props.rows[1]!.delete_url, { method: "delete", preserveScroll: true })

    confirm.mockRestore()
  })

  it("shows the empty state", () => {
    render(<PostsIndex {...props} rows={[]} />)

    expect(screen.getByText("No news yet.")).toBeInTheDocument()
  })
})

describe("post-types::Posts/Edit", () => {
  const props = postsEdit as unknown as PostsEditProps

  it("renders the post form with the content and media fields", () => {
    render(<PostsEdit {...props} />)

    expect(screen.getByRole("heading", { name: "Edit News article" })).toBeInTheDocument()
    expect(screen.getByDisplayValue("ރާއްޖޭގެ ޚަބަރު")).toBeInTheDocument()
    expect(document.querySelector("[data-stencil-block-editor=content]")).toBeInTheDocument()
    expect(document.querySelector("[data-stencil-mediapicker=featured_image]")).toBeInTheDocument()
    expect(document.querySelector("input[name='meta[subtitle]']")).toBeInTheDocument()
    expect(screen.getByRole("link", { name: /View/ })).toHaveAttribute("href", props.post!.url!)
  })

  it("has a title for new posts", () => {
    render(<PostsEdit {...props} post={null} />)

    expect(screen.getByRole("heading", { name: "New News article" })).toBeInTheDocument()
  })
})

describe("post-types::Terms", () => {
  it("lists terms as a tree and deletes after asking", () => {
    const props = termsIndex as unknown as TermsIndexProps

    render(<TermsIndex {...props} confirm={() => true} />)

    expect(screen.getByRole("heading", { name: "Categories" })).toBeInTheDocument()
    const child = screen.getByRole("link", { name: "ކުޅިވަރު" })
    expect(child.closest("[data-depth]")).toHaveAttribute("data-depth", "1")

    const row = child.closest("tr")!
    fireEvent.click(within(row).getByRole("button", { name: "Delete" }))
    expect(destroy).toHaveBeenCalledWith(props.terms[1]!.delete_url, { preserveScroll: true })
  })

  it("renders the term form", () => {
    render(<TermsEdit {...(termsEdit as unknown as TermsEditProps)} />)

    expect(screen.getByRole("heading", { name: "New Category" })).toBeInTheDocument()
    expect(document.querySelector("input[name=name]")).toBeInTheDocument()
  })
})

describe("post-types::Public", () => {
  it("renders the archive", () => {
    const props = publicArchive as unknown as PublicArchiveProps

    render(<PublicArchive {...props} />)

    expect(screen.getByRole("heading", { level: 1, name: "News" })).toBeInTheDocument()
    expect(screen.getByRole("link", { name: "ރާއްޖޭގެ ޚަބަރު" })).toHaveAttribute("href", props.posts[0]!.url!)
    expect(screen.getByText("ޚުލާޞާ")).toBeInTheDocument()
  })

  it("renders a term's archive with a link back", () => {
    const props = publicArchive as unknown as PublicArchiveProps

    render(
      <PublicTerm
        {...props}
        title="ކުޅިވަރު"
        term={{ id: 1, name: "ކުޅިވަރު", slug: "x", description: "ތަފްޞީލު", taxonomy: "categories", url: null }}
      />,
    )

    expect(screen.getByRole("heading", { level: 1, name: "ކުޅިވަރު" })).toBeInTheDocument()
    expect(screen.getByText("ތަފްޞީލު")).toBeInTheDocument()
    expect(screen.getByRole("link", { name: "Back to News" })).toBeInTheDocument()
  })

  it("renders an empty archive", () => {
    render(<PublicArchive {...(publicArchive as unknown as PublicArchiveProps)} posts={[]} />)

    expect(screen.getByText("Nothing has been published here yet.")).toBeInTheDocument()
  })

  it("renders a post with its content and terms", () => {
    const props = publicSingle as unknown as PublicSingleProps

    render(<PublicSingle {...props} />)

    expect(screen.getByRole("heading", { level: 1, name: "ރާއްޖޭގެ ޚަބަރު" })).toBeInTheDocument()
    expect(screen.getByText("ދިވެހި ބަސް")).toBeInTheDocument()
    expect(screen.getByRole("link", { name: "ކުޅިވަރު" })).toHaveAttribute("href", props.post.terms[0]!.terms[0]!.url!)
    expect(screen.queryByText(/This is a preview\./)).not.toBeInTheDocument()
  })

  it("marks previews", () => {
    render(<PublicSingle {...(publicSingle as unknown as PublicSingleProps)} preview />)

    expect(screen.getByText(/This is a preview\./)).toBeInTheDocument()
  })
})
