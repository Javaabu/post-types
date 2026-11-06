import { readdirSync, readFileSync } from "node:fs"
import { dirname, join, relative, resolve } from "node:path"
import { describe, expect, it } from "vitest"

const root = resolve(import.meta.dirname, "../../resources/js")
const stylesheet = resolve(import.meta.dirname, "../../resources/css/post-types.css")

const files = (readdirSync(root, { recursive: true }) as string[])
  .filter((file) => file.endsWith(".tsx"))
  .map((file) => join(root, file))

// Apps render these through Inertia, where manifest doesn't inject the styles:
// each component that uses the package's classes imports them, so Vite bundles them
describe("styles", () => {
  const styled = files.filter((file) => readFileSync(file, "utf8").includes('"post-types-'))

  it("finds the components that use the package's classes", () => {
    expect(styled.length).toBeGreaterThanOrEqual(3)
  })

  it.each(styled.map((file) => [relative(root, file), file]))("%s imports the stylesheet", (_name, file) => {
    const path = relative(dirname(file), stylesheet)

    expect(readFileSync(file, "utf8")).toContain(`import "${path}"`)
  })
})
