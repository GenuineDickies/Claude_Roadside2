# Style Guide (UI/System)

## Color System
| Token | Value | Usage |
|-------|-------|-------|
| --bg-primary | #0C0E12 | Page background |
| --bg-secondary | #12151B | Card/sidebar background |
| --bg-surface | #181C24 | Elevated surface |
| --navy-500 | #2B5EA7 | UI chrome: buttons, links, nav, borders |
| --amber-500 | #F59E0B | Status: pending/warning |
| --green-500 | #22C55E | Status: success/active |
| --red-500 | #EF4444 | Status: urgent/error |
| --blue-500 | #3B82F6 | Status: in-progress |

**Rule:** Navy = UI chrome ONLY. Signal colors = status ONLY. Never mix.

## Typography
| Font | Usage |
|------|-------|
| DM Sans 400/500/600/700 | All UI text |
| JetBrains Mono 400/500/600 | Data values, counts, dates, codes |

## Page Template
Every page must have:
1. Gradient header with navy bottom border
2. Scoped CSS with page prefix
3. Responsive grid layout

## Components
- Stat cards with colored left border
- Tables with sticky headers
- Status badges (colored by signal)
- Navy primary buttons, ghost secondary buttons
- Dark-themed modals

## Last Updated
2026-02-12