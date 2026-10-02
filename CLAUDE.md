# CLAUDE.md — Zed Theme Development

## Project Goal

Replicate the **UnityDark** VSCode theme as accurately as possible in Zed's theme format.
- VSCode source reference: https://github.com/funkjedi/vscode-user/blob/main/themes/UnityDark.json
- The Zed theme is ~95% complete. Work focuses on the remaining gaps.

---

## Repo Structure

```
./
├── themes/unity/
│   ├── extension.toml              # Extension manifest
│   ├── themes/unity.json           # ← MAIN THEME FILE (Zed format)
│   └── icon_themes/unity.json      # Icon theme (separate concern)
├── docs/decisions/                 # ADRs (NNNN-title.md, see README.md)
├── settings.json                   # Zed editor settings (includes theme_overrides and known issues)
├── keymap.json                     # Zed keybindings
└── README.md
```

**Primary edit target: `themes/unity/themes/unity.json`**

`settings.json` also contains a `theme_overrides` block for Unity Dark — this is used as a scratchpad for tokens that are hard to get right or currently broken. When a fix is confirmed working in `theme_overrides`, migrate it into the main theme file and remove the override.

Do not touch `settings_backup.json` or the prompts directory.

---

## Editor Context

- **Font:** MonacoLigaturized Nerd Font Mono, 15px
- **UI font size:** 18px
- **Keymap:** SublimeText base
- **Primary language:** PHP (via Intelephense LSP) — most syntax highlighting issues surface here
- **Other languages:** SQL, Blade templates (`*.blade.php`)
- **File icons:** disabled; folder icons enabled
- **Inline git blame:** disabled

This context matters for theme work: syntax token issues are most visible in PHP files, and Blade templates use a mixed PHP/HTML grammar that may require additional scope coverage.

---

## Priorities (in order)

1. **Syntax highlighting accuracy** — Token colors must match UnityDark's TextMate scopes as closely as Zed allows
2. **Color palette consistency** — Only use colors drawn from the UnityDark palette; do not introduce new colors
3. **UI/editor token coverage** — Map all relevant Zed UI tokens to appropriate UnityDark equivalents
4. **Accessibility** — Secondary concern; do not sacrifice fidelity to the source theme to chase contrast ratios

---

## Known Issues & Gaps

These are tracked in `settings.json` under `theme_overrides`. This is the canonical list of what's broken or missing.

### Currently broken (overrides not working as expected)

These tokens were added to `theme_overrides` but do not appear to be taking effect — likely because Zed doesn't support them as `theme_overrides` syntax keys, or the scope name doesn't match what Intelephense emits:

| Attempted token | Intended color | Notes |
|---|---|---|
| `constant.numeric` | `#b5cea8` | Numbers not rendering correctly |
| `entity.name.function` | `#e06c75` | Function names not picking up color |
| `keyword.operator` | `#ff0000` | Operators not responding (test color) |

**When investigating these:** check what semantic tokens Intelephense actually emits (Zed's LSP inspector or `editor: copy highlight styles` can help). The TextMate scope name in the override may not match the token Intelephense provides — Zed may be using semantic highlighting instead of TextMate grammar for PHP.

### Working override (migrate when confirmed stable)

| Token | Color | Notes |
|---|---|---|
| `operator` | `#e16b75` | Dollar sign in PHP variables. Side effect: also colors `=` — acceptable for now |

### Missing PHP syntax colors

These elements currently have no color or the wrong color. Priority order:

1. `use` statements
2. Class names in method/function parameter type hints
3. Class name after the `new` keyword
4. Backslash `\` in namespace separators

### Selection colors

The other primary remaining issue. When investigating, check all of these Zed tokens:

| Zed token | Purpose |
|---|---|
| `editor.selection.background` | Active selection in focused editor |
| `editor.inactive_selection_background` | Selection in unfocused/inactive editor pane |
| `editor.selection_highlight_background` | Other occurrences of the selected word |
| `search.match_background` | Find/search match — often visually confused with selection |
| `terminal.selection_background` | Terminal panel selection |

Cross-reference against UnityDark VSCode source:
- `editor.selectionBackground`
- `editor.inactiveSelectionBackground`
- `editor.selectionHighlightBackground`
- `editor.wordHighlightBackground`
- `editor.wordHighlightStrongBackground`

> **Alpha gotcha:** Zed uses `#RRGGBBAA` for transparency (e.g. `#264F7866` = `#264F78` at ~40% opacity). If the UnityDark source specifies opacity separately, encode it into the hex before applying.

---

## Approach: `theme_overrides` vs. `unity.json`

Zed supports two places where theme tokens can be set:

- **`themes/unity/themes/unity.json`** — the extension theme file, loaded when the theme is active
- **`settings.json` → `theme_overrides.Unity Dark`** — user-level overrides, applied on top of the theme file

Use `theme_overrides` for **experimentation and debugging**. Once a fix is confirmed, move it into `unity.json` and remove the override entry. This keeps `settings.json` clean and makes the theme portable.

Note the reference comments already in `settings.json`:
- https://github.com/zed-industries/zed/blob/main/crates/theme/src/fallback_themes.rs — canonical list of all Zed theme tokens
- `~/.config/zed/themes/unity/themes/unity.json` — local path to the active theme file

---

## Semantic vs. TextMate Highlighting in PHP

PHP files processed by Intelephense use **semantic highlighting** (LSP-provided token types) rather than TextMate grammar scopes for many tokens. This means:

- A TextMate scope like `entity.name.function` may be ignored if Intelephense provides a semantic `function` token instead
- The effective token to style may be a Zed semantic key (`function`, `type`, `variable`, etc.) rather than a dotted TextMate path
- When a `theme_overrides` syntax entry isn't working, check if Intelephense is emitting a different token type

To debug: place the cursor on the token in question and run **`editor: copy highlight styles`** from the command palette. This shows the exact token type and scope Zed is using to color that element.

---

## VSCode → Zed Token Mapping

### Syntax (TextMate / semantic)

| VSCode scope | Zed syntax key |
|---|---|
| `string` | `string` |
| `comment` | `comment` |
| `keyword`, `keyword.control` | `keyword` |
| `storage.type` | `type` |
| `entity.name.function` | `function` |
| `entity.name.type` | `type` |
| `variable` | `variable` |
| `constant.numeric` | `number` |
| `constant.language` | `constant` or `boolean` |
| `support.function` | `function.builtin` |
| `punctuation` | `punctuation` |
| `keyword.operator` | `operator` |

### UI Tokens

| VSCode token | Zed token |
|---|---|
| `editor.background` | `editor.background` |
| `editor.foreground` | `editor.foreground` |
| `editor.lineHighlightBackground` | `editor.active_line.background` |
| `editor.selectionBackground` | `editor.selection.background` |
| `editor.inactiveSelectionBackground` | `editor.inactive_selection_background` |
| `editorGutter.background` | `editor.gutter.background` |
| `editorLineNumber.foreground` | `editor.line_number` |
| `editorLineNumber.activeForeground` | `editor.active_line_number` |
| `editorCursor.foreground` | `editor.cursor` |
| `editorIndentGuide.background` | `editor.indent_guide` |
| `editorIndentGuide.activeBackground` | `editor.indent_guide_active` |
| `statusBar.background` | `status_bar.background` |
| `tab.activeBackground` | `tab_bar.background` + `tab.active_background` |
| `tab.inactiveBackground` | `tab.inactive_background` |
| `sideBar.background` | `panel.background` |
| `list.activeSelectionBackground` | `element.selected` |
| `list.hoverBackground` | `element.hover` |

---

## Workflow

### Fixing a color token
1. Look up the equivalent token in the UnityDark VSCode source
2. Extract the exact hex (encoding any separate opacity into `#RRGGBBAA`)
3. Map it to the correct Zed token using the tables above
4. Test in `theme_overrides` first, confirm it works, then migrate to `unity.json`

### Debugging a token that isn't responding
1. Place cursor on the problem token in a PHP file
2. Run `editor: copy highlight styles` from the command palette
3. Use the reported token type/scope to find the correct Zed key
4. Check whether it's a semantic token (Intelephense) or TextMate scope — they require different approaches

### Migrating a working override to the theme file
1. Confirm the override produces the correct result in `settings.json`
2. Add the token to `themes/unity/themes/unity.json` under the correct section
3. Remove the entry from `theme_overrides` in `settings.json`
4. Reload the theme and verify the result is unchanged

---

## Do Not

- Introduce colors not present in the UnityDark palette
- Chase WCAG compliance at the expense of theme fidelity
- Edit `settings_backup.json`, the prompts directory, or icon files unless explicitly asked
- Assume VSCode and Zed scope/token names are identical — always verify via the mapping tables or `editor: copy highlight styles`
- Leave working fixes in `theme_overrides` permanently — migrate them to `unity.json`
