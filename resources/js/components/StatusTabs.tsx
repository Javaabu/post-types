import { Link } from "@inertiajs/react"
import type { StatusTab } from "../types"

export interface StatusTabsProps {
  tabs: StatusTab[]
  label: string
}

/** All, each status, scheduled and the trash, with counts: links that filter the index */
export function StatusTabs({ tabs, label }: StatusTabsProps) {
  return (
    <nav aria-label={label}>
      <ul className="flex flex-wrap gap-x-4 gap-y-2 text-sm">
        {tabs.map((tab) => (
          <li key={tab.key}>
            <Link
              href={tab.url}
              data-tab={tab.key}
              aria-current={tab.active ? "page" : undefined}
              className={tab.active ? "font-semibold hover:underline" : "text-muted-foreground hover:underline"}
            >
              {tab.label} <span className="text-muted-foreground">({tab.count})</span>
            </Link>
          </li>
        ))}
      </ul>
    </nav>
  )
}
