import { defineConfig } from "vitest/config"

const path = (relative: string) => new URL(relative, import.meta.url).pathname

// The javaabu/* React code comes from vendor/ (composer install first), as
// javaabu/manifest's Vite plugin resolves it in apps. The block editor and the
// media picker are replaced by small stand-ins (tests/js/shims): their editors
// need TipTap and Onu's media components, which the pages' tests don't exercise.
export default defineConfig({
  resolve: {
    alias: [
      { find: /^@javaabu\/post-types$/, replacement: path("./resources/js/index.ts") },
      { find: /^@javaabu\/stencil$/, replacement: path("./vendor/javaabu/stencil/resources/js/index.ts") },
      { find: /^@javaabu\/filters$/, replacement: path("./vendor/javaabu/filters/resources/js/index.ts") },
      { find: /^@javaabu\/bulk-actions$/, replacement: path("./vendor/javaabu/bulk-actions/resources/js/index.ts") },
      { find: /^@javaabu\/block-editor\/react$/, replacement: path("./tests/js/shims/block-editor-react.tsx") },
      { find: /^@javaabu\/mediapicker$/, replacement: path("./tests/js/shims/mediapicker.tsx") },
    ],
  },
  test: {
    environment: "jsdom",
    include: ["tests/js/**/*.test.{ts,tsx}"],
    setupFiles: ["tests/js/setup.ts"],
    coverage: {
      provider: "v8",
      include: ["resources/js/**/*.{ts,tsx}"],
      reporter: ["text", "clover", "lcov"],
      reportsDirectory: "coverage",
    },
  },
})
