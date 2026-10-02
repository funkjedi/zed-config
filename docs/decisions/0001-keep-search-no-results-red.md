# 0001. Keep search "no results" text red

- **Status:** Accepted
- **Date:** 2026-10-02

## Context

When a buffer or project search finds no matches, Zed renders the query text in red. The goal was to keep the query in the normal text color instead.

Zed has no dedicated theme token or setting for this. The query is colored with the global `error` status color (`#c24038` in `themes/unity/themes/unity.json`). The same color is used for error diagnostics (squiggles, inline text, gutter icons), the diagnostics panel and status bar error count, file names with errors in tabs and the project panel, failure notifications, and error-tinted buttons and messages.

## Decision

Leave `error` as `#c24038`. Accept the red "no results" query text.

## Consequences

- Error diagnostics and error UI stay clearly visible and faithful to UnityDark.
- The search query keeps turning red when nothing matches.
- Revisit if Zed adds a separate theme token or setting for the search query's no-match color.
