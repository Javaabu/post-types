import { describe, expect, it } from "vitest"
import { format } from "@javaabu/post-types"

describe("format", () => {
  it("replaces placeholders, longest first", () => {
    expect(format(":count ޚަބަރު, :count_trashed ފުހެލެވިފަ", { count: 3, count_trashed: 1 })).toBe(
      "3 ޚަބަރު, 1 ފުހެލެވިފަ",
    )
  })

  it("leaves unknown placeholders", () => {
    expect(format("Edit :name")).toBe("Edit :name")
  })
})
