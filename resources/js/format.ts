/**
 * Replaces `:name` placeholders, as Laravel's `__()` does: the strings the
 * pages show come translated from PHP with their placeholders left in
 */
export function format(template: string, replacements: Record<string, string | number> = {}): string {
  return Object.entries(replacements)
    .sort(([a], [b]) => b.length - a.length)
    .reduce((text, [key, value]) => text.split(`:${key}`).join(String(value)), template)
}
