---
name: writing-php
description: PHP coding standards for the Quedamos wp-child-theme-template WordPress theme — output safety (esc_*, wp_json_encode), the inc/<module>/ file layout and module loader registry in functions.php, the quedamos_* function prefix, the no-inline-<script> rule, WordPress Coding Standards formatting (tabs, array(), spaces in parens), the inline-SVG helper, and the no-hardcoded-URL rule. Use whenever creating or modifying PHP under themes/wp-child-theme-template/ (block templates' PHP partials, shortcodes, schema generators, hook handlers, render callbacks). For styles see writing-scss; for frontend behaviour see writing-js.
---

# Writing PHP (wp-child-theme-template)

These conventions apply to **all** PHP work in `themes/wp-child-theme-template/` — module files under
`inc/`, shortcodes, schema generators, hook handlers, anything echoing to the browser or registering
hooks and filters.

Sibling skills:
- [writing-scss](../writing-scss/SKILL.md) — styles
- [writing-js](../writing-js/SKILL.md) — frontend behaviour

## The overarching rule: the system is king — never invent

A specialisation of the project-wide rule in [CLAUDE.md](../../../CLAUDE.md): **find the existing pattern,
mirror it; if none exists, ASK before picking.** For PHP that means:

- **Before placing a new file** — find the `inc/<module>/` that owns this domain and put it there. Don't
  invent a generic location.
- **Before naming a function** — apply the `quedamos_*` prefix (§2) and follow the naming of nearby
  functions in the same module.
- **Before introducing a new module** — confirm none of the existing ones fit. If one is genuinely
  needed, propose it and wait for sign-off before scaffolding.

If the right answer isn't obvious, **STOP and ask Sigurd** — then record the answer here so the next
session finds it.

## 1. Output safety

Applies to **every** PHP file in the theme that emits to the page.

- **Escape every dynamic string on output** — `esc_html()`, `esc_attr()`, `esc_url()`, `esc_textarea()`.
  Escape at the point of output, not at the point the value is fetched.
- **Emit JSON with `wp_json_encode()`, never raw `json_encode()`.** WP's wrapper sanitises malformed
  UTF-8 first; bare `json_encode()` returns `false` on the first bad byte and renders nothing. One
  accented character in a Spanish course title is enough to silently kill a whole JSON-LD block — which
  matters here more than most sites.

```php
// ❌ BAD — unescaped, and one bad byte kills the schema block
echo '<div class="course-title">' . $course . '</div>';
echo '<script type="application/ld+json">' . json_encode( $schema ) . '</script>';

// ✅ GOOD
echo '<div class="course-title">' . esc_html( $course ) . '</div>';
echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>';
```

**Shortcodes return, they don't echo.** A shortcode callback builds a string and `return`s it. Echoing
from a shortcode dumps output at the top of the page instead of where the shortcode sits. Where a
shortcode needs a template file, buffer it: `ob_start(); include …; return ob_get_clean();`.

**Watch `wpautop()` in shortcode output.** WordPress converts newlines in shortcode return values into
stray `<br>` and `<p>` tags. Build multi-element shortcode output as a single-line string, or strip
newlines before returning: `return preg_replace( '/\n\s*/', '', $html );`.

**And keep block-level tags out of the middle of it.** Stripping the newlines is not enough on its own:
`wpautop()` also treats a block-level tag as a paragraph boundary, so a `<div>` nested inside shortcode
output gets a stray `</div>`-closing `</p>` injected ahead of it — invalid markup that browsers then
recover from in their own way. Build inner wrappers as `<span>` and give them `display: flex` / `block`
in the SCSS. An outer wrapper at the very start of the string is fine; it's the nested ones that break.

```php
// ❌ BAD — wpautop injects `</p>` before the inner div
return '<div class="article-author">' . $name . '<div class="article-author__actions">' . $icons . '</div></div>';

// ✅ GOOD — inner wrapper is a span, laid out with flex in CSS
return '<div class="article-author">' . $name . '<span class="article-author__actions">' . $icons . '</span></div>';
```

## 2. Function prefix — `quedamos_*`

`quedamos_*` for every new top-level PHP function in the theme. No exceptions, no unprefixed helpers.

The legacy unprefixed names are frozen: `course_query_shortcode`, `output_acf_schema`,
`modify_navigation_block_output`, `booking_summary_shortcode`, `add_google_gtag_to_head`,
`twentytwentyfour_child_scripts`. Leave them in place when you're not touching them; **do not add new
ones**. When you move or rewrite one, give it the `quedamos_` prefix.

Rationale: a single predictable prefix makes the theme greppable and prevents collisions with WordPress,
ACF and plugins. An unprefixed top-level function in a theme is a future fatal error.

## 3. File layout — `inc/<module>/`

The theme is organised by **module** (a cohesive feature or concern), not by file type. Every directory
under `inc/` is a module. Before creating a file, ask *what module does this belong to?*

### The guiding principle: locality + self-containment

If code is specific to module X, it lives inside module X. Removing the module from the registry in
`functions.php` should remove all of its behaviour — no orphan hooks registered from elsewhere.

### Existing modules

| Module | Owns |
|---|---|
| `inc/helpers/` | Cross-cutting `quedamos_*` utilities — `quedamos_inline_svg()`, `quedamos_is_live_site()`, and the author identity (`author-identity.php`: canonical name, role, photo, profile page, social profiles) that `blog` renders and `schema` marks up |
| `inc/assets/` | Global bundle enqueue (Parcel `dist/` CSS + JS) and theme-wide localised data |
| `inc/analytics/` | Google Analytics + site verification, gated to production |
| `inc/blog/` | Single-article display meta — read time, byline, related-posts query scoping |
| `inc/booking/` | Booking summary shortcode and its view template |
| `inc/courses/` | Course archive query shortcode |
| `inc/navigation/` | The header navigation — the `quedamos/site-navigation` block (registration, its `wp_navigation` lookups, `site-navigation/render.php` for both the desktop row and the mobile panel, and `site-navigation/editor.js`) |
| `inc/redirects/` | The site's 301 redirect map — **every** redirect lives here (§9) |
| `inc/schema/` | Schema.org output — the ACF-driven JSON-LD shortcode, and `person.php`, which filters Rank Math's graph (§10) |

### Rules

1. **Module loader pattern.** Each module has an index file at its root named `<module>.php`, which
   `require_once`s the rest of the module. The module is registered once in the `$quedamos_modules`
   array in [functions.php](../../../themes/wp-child-theme-template/functions.php).
2. **One file per concern inside a module.** Don't grow a single file into a god file.
3. **New module?** Add the folder, add the `<module>.php` index, add one line to `$quedamos_modules`,
   and update the table above **in the same commit**. That's the whole ceremony.
4. **Don't reach into a sibling module.** If two unrelated modules both need something, it's a helper —
   promote it to `inc/helpers/`.
5. **A generic filename is the smell.** Reaching for `misc.php`, `extra.php`, `functions-2.php` means
   you've stopped asking which module owns this. Spin up the right module instead.

### `templates/` is for block templates only

`themes/wp-child-theme-template/templates/` is WordPress's **block template** directory — `.html` files
that override the parent theme's templates (`single.html`, `index.html`, `page-{slug}.html`). PHP does
not belong there; it goes in `inc/`. A module's own view partials live inside the module
(`inc/booking/parts/summary-card.php`).

### Quick decision tree

1. Is it a shortcode? → the module whose domain it serves, as `<name>-shortcode.php`.
2. Is it specific to one feature (booking, courses, blog, schema)? → that module's folder.
3. Is it a reusable helper used across modules? → `inc/helpers/`.
4. Is it a global theme asset enqueue? → `inc/assets/`.
5. None of the above? → propose a new module.

## 4. Never inline `<script>` blocks in PHP for behaviour

PHP under `inc/` must not contain `<script>…</script>` blocks of JS behaviour. Behaviour goes through
the Parcel bundle so it's minified, greppable by selector, and lives where a future reader will look.
See [writing-js](../writing-js/SKILL.md).

**Exceptions** (only these):

- **JSON-LD schema** — `<script type="application/ld+json">` is the correct way to emit schema.org data.
- **PHP → JS data passing** — use `wp_add_inline_script()` or `wp_localize_script()` against a
  registered handle, never a raw `<script>` tag.

Treat any other `<script>` block in a PHP file as a bug — move it to a bundle module in the same commit.

## 5. WordPress Coding Standards formatting

New PHP follows WPCS. Writing to these from the start is cheaper than retrofitting.

**Indentation — tabs, never spaces.** Including PHP blocks inside HTML partials.

**Spaces inside parentheses** — one space after `(` and before `)` in function calls and control
structures:

```php
// ❌ BAD
esc_html($foo)
if($condition)

// ✅ GOOD
esc_html( $foo )
if ( $condition )
```

**Long array syntax — `array()` not `[]`:**

```php
// ❌ BAD
$args = ['post_type' => 'post'];

// ✅ GOOD
$args = array( 'post_type' => 'post' );
```

**Multi-line calls** — the opening `(` is the last character on its line, the closing `)` sits on a line
of its own:

```php
$query = new WP_Query(
	array(
		'post_type' => 'post',
	)
);
```

**Yoda conditions** for comparisons against a literal — `if ( 'post' === $type )`, not
`if ( $type === 'post' )`. A typo'd `=` then fails loudly instead of silently assigning.

**Every file opens with a docblock** stating what the module owns, and every function gets one with
`@param` / `@return`. Follow the shape used in `inc/blog/article-meta.php`.

**Guard direct access** at the top of every module file: `defined( 'ABSPATH' ) || exit;`

## 6. Never hardcode raw `<svg>` markup — use `quedamos_inline_svg()`

Icons are reusable assets, not one-off markup. Drop the file in `svgs/` and inline it through
`quedamos_inline_svg()` ([inc/helpers/helpers.php](../../../themes/wp-child-theme-template/inc/helpers/helpers.php)),
which reads the file, adds an `inline-svg` class and runs it through `wp_kses`. One source of truth per
icon, greppable by filename, recolourable via `currentColor`.

```php
// ❌ BAD — pasted inline, not reusable
?><svg viewBox="0 0 24 24"><path d="M5 12h14" stroke="currentColor"/></svg><?php

// ✅ GOOD
echo quedamos_inline_svg( 'svgs/calendar.svg' );
```

Check the existing `svgs/` folder before drawing a new icon — mirror an existing glyph where one fits.

## 7. Never hardcode the site URL or brand contact details

`https://quedamoslanguages.com` must not appear in PHP. It breaks local development (the asset 404s or
silently loads from production) and it's invisible to WordPress's search-replace on migration.

| Need | Use |
|---|---|
| A link to a page on this site | `home_url( '/courses/' )` |
| A URL to a theme asset | `get_stylesheet_directory_uri()` |
| A path to a theme file on disk | `get_stylesheet_directory()` |
| An uploaded media URL | `wp_get_attachment_image()` / `wp_get_attachment_url()` |
| The site logo | `the_custom_logo()` |
| "Are we on live?" | `quedamos_is_live_site()` |

```php
// ❌ BAD — breaks locally, invisible to search-replace
$logo = 'https://quedamoslanguages.com/wp-content/uploads/2024/09/logo.webp';

// ✅ GOOD
$logo = get_theme_mod( 'custom_logo' ) ? wp_get_attachment_url( get_theme_mod( 'custom_logo' ) ) : '';
```

The cautionary tale is the since-deleted `inc/navigation/mobile-menu.php`, which built its markup as a
string with the production domain baked in — which is exactly why the local and live copies of that file
diverged. Its replacement, `inc/navigation/site-navigation/render.php`, hardcodes no URL at all: every
href is resolved from a post ID. Don't reintroduce the old shape.

## 8. Never inline data-parsing or formatting logic in a template — extract a helper

A view partial should **read** like markup: fetch, loop, echo escaped values. Logic that *derives* a
value for display — parsing a field, pluralising, formatting a date, mapping a code to a label — does not
belong wedged between tags.

```php
// ❌ BAD — date parsing wedged into the markup
<?php
$timestamp = strtotime( $start_date );
$day       = date( 'j', $timestamp );
$suffix    = date( 'S', $timestamp );
?>
<div class="start-date"><?php echo $day . $suffix . ' ' . date( 'F Y', $timestamp ); ?></div>

// ✅ GOOD — intent named in a helper; the template just echoes
<div class="start-date"><?php echo esc_html( quedamos_format_course_date( $start_date ) ); ?></div>
```

Where's the line? A single function call or lone ternary inline is fine. The moment you reach for
`preg_*`, a loop, or an `if/else` between `?>` and `<?php` to compute what to print, extract it. The
helper lives in the module that owns the data, not global `inc/helpers/`, unless it's genuinely
cross-cutting.

## 9. Every redirect goes in the redirects map — one file, no exceptions

All 301s live in the `quedamos_redirect_map()` array in
[inc/redirects/redirects.php](../../../themes/wp-child-theme-template/inc/redirects/redirects.php), as
`'/old-path/' => '/new-path/'`. Nowhere else — not a redirect plugin, not `.htaccess`, not an ad-hoc
`wp_redirect()` bolted onto a `template_redirect` hook in some other module.

Redirects are the site's least visible moving part: when a URL misbehaves, the first question is always
*"is something redirecting it?"*, and that question has to have exactly one place to look. Scattered
redirects also stack — two rules can chain or loop, and neither author knows the other exists.

```php
// ❌ BAD — a redirect hidden inside an unrelated module
add_action( 'template_redirect', function () {
	if ( is_page( 'booking-form-2' ) ) {
		wp_redirect( '/booking-form/' );
		exit;
	}
} );

// ✅ GOOD — one line in the map, with a comment saying why
'/booking-form-2/' => '/booking-form/',
```

**Always comment each entry with the reason and date.** An uncommented redirect is one nobody will ever
be confident enough to delete, so the map only ever grows.

Add the map entry **before** deleting the old page — the redirect fires whether or not the page still
exists, so there's no window where the URL 404s.

## 10. Schema.org — filter Rank Math's graph, never print a second block

Rank Math owns the JSON-LD on this site and already emits a complete graph: `Organization`, `WebSite`,
`WebPage`/`AboutPage`, `BreadcrumbList`, and a full `BlogPosting` per post (headline, dates,
`articleSection`, keywords, image, publisher, `inLanguage`, `mainEntityOfPage`). **Don't regenerate what
it already produces.** Two Article nodes, or two Person nodes, describing the same thing on one page is
worse than one thin node — nothing tells a consumer which to believe, and they will disagree the moment
one of them changes.

To add or correct schema, filter it:

```php
add_filter( 'rank_math/json_ld', 'quedamos_filter_person_schema', 100 );
```

- `$data` is an **array keyed by node name** — `publisher`, `WebSite`, `WebPage`, `ProfilePage`,
  `richSnippet`, `BreadcrumbList` — not a list. Reuse an existing key to supersede a node; add a new key
  to add one.
- **Run at priority 100 or above.** Rank Math's own schema module connects its entities at 99, so
  anything earlier gets overwritten by the plugin.
- Rank Math wraps the output in `wp_json_encode()` and `wp_kses_post_deep()` itself, so a filter returns
  plain arrays — don't encode or escape on the way out.

**One entity, one `@id`, referenced everywhere.** The `@id` is what makes separate mentions resolve to
the same thing. Point it at a page a human can read and cite — Sara's `@id` is a fragment on the About
page, not the `/author/admin/` archive, which is a bare post list that credentials nobody.

**Never assert in schema what the visible page doesn't say.** Markup that outruns the page is a
liability, not a signal. `inc/schema/person.php` draws every claim from prose already on the About page,
which is also why DEPLOY-LIST.md carries a content step to put her full name in that prose.

## 11. Adding a new convention

If you discover a PHP pattern that should be enforced project-wide, **edit this file** and commit it
alongside the change that motivated it. Skills are committed to git and apply to every collaborator —
human or Claude — working in this repo.
