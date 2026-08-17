---
name: writing-js
description: JavaScript coding standards for the Quedamos wp-child-theme-template theme — the single-bundle entry pattern (scripts.js), where page and component modules live under assets/scripts/js/, element-presence guards, named exports, the no-inline-<script>-in-PHP rule, and how PHP passes data to JS via wp_localize_script rather than hidden DOM elements. Use whenever creating or modifying any .js under themes/wp-child-theme-template/assets/scripts/.
---

# Writing JavaScript (wp-child-theme-template)

These conventions apply to **all** JS in `themes/wp-child-theme-template/assets/scripts/`. Read before
adding or changing any `.js` file.

Sibling skills:
- [writing-php](../writing-php/SKILL.md) — PHP conventions (including the inline-script rule from the PHP side)
- [writing-scss](../writing-scss/SKILL.md) — styles

## 1. Bundle layout

The theme uses a **single-bundle entry**: every module is imported from `assets/scripts/scripts.js`, and
Parcel builds that into one file shipped as `dist/scripts/scripts.js`.

| Goes in | What |
|---|---|
| `assets/scripts/scripts.js` | The single entry. Imports every module. New behaviour = a new module + an import here. |
| `assets/scripts/js/<concern>.js` | A single-concern module (e.g. `accordion.js`). |
| `assets/scripts/js/<group>/<concern>.js` | A module belonging to a group of related behaviours. `js/animations/` is the precedent — one file per animation, plus `common.js` for shared helpers. |

### Rules

1. **A module is dead until it's imported.** Creating the file is half the job; the other half is the
   `import` line in `scripts.js`. `js/accordion.js` exists today and is **not** imported — it ships
   nothing. Don't add to that pile.
2. **Element-presence guards at the top of every entry function.** A module in a single bundle runs on
   *every page*. The first lines should find the element and quietly bail when it's absent:

   ```js
   const el = document.querySelector('.article-toc');
   if (!el) {
     return;
   }
   ```

   A missing element is not an error — never `throw`, never `console.error`. (`js/animations/count-up.js`
   currently logs an error for a missing element, and references an undefined variable while doing it.
   That's the bug this rule prevents.)
3. **One file per concern.** Don't grow a module into a 500-line god file. Split by behaviour and group
   in a folder, as `js/animations/` does.
4. **Named exports, not default exports.** Grepping `runOnScroll` should find the export and every call
   site. `export default` hides the name.
5. **No `console.log` / `console.error` in shipped code.** Remove debugging output before committing.

## 2. Style

There is no ESLint config in this theme yet, so these are conventions rather than enforced rules — hold
to them anyway:

| Rule | Requirement |
|---|---|
| `no-var` | Always `const` or `let`. Never `var`. |
| `curly` | Always `{ }` after `if` / `else` / `for`, even for a single-line body. |
| `brace-style` | Opening brace on the same line as the statement; body and closing brace each on their own line. |
| `eol-last` | Every file ends with a newline. |
| Indentation | 2 spaces for new files, matching the SCSS and the newer modules. |

```js
// ✅ correct style
export function initArticleToc() {
  const toc = document.querySelector('.article-toc');
  if (!toc) {
    return;
  }

  toc.addEventListener('click', function(event) {
    handleClick(event);
  });
}
```

## 3. Never inline `<script>` blocks in PHP for behaviour

Owned by [writing-php §4](../writing-php/SKILL.md#4-never-inline-script-blocks-in-php-for-behaviour), and
it binds equally from this side: behaviour belongs in a bundle module, not inside a PHP template's
`<script>` tag. Spot one while editing PHP → move it to a module in the same commit.

**PHP → JS data passing** goes through `wp_localize_script()` or `wp_add_inline_script()` against the
registered bundle handle. On the JS side, read the globalised object — never scrape a hidden DOM element
for data, and never sniff `window.location` to work out what page you're on.

```js
// ✅ GOOD — reads localised data, bails cleanly when absent
export function bookingSummary() {
  const root = document.querySelector('.booking-summary-card');
  if (!root) {
    return;
  }

  const courseId = window.quedamos?.courseId;
  if (!courseId) {
    return;
  }
  // …
}
```

## 4. Build

From the theme root:

```
npm run watch    # dev — rebuilds dist/ on save
npm run build    # production
```

`dist/` is gitignored and generated. Never edit it, never commit it. If a change isn't showing on the
page, confirm the watcher is running before debugging the code.

## 5. Adding a new convention

If you discover a JS pattern that should be enforced project-wide, **edit this file** and commit it
alongside the change that motivated it. Skills are committed to git and apply to every collaborator —
human or Claude — working in this repo.
