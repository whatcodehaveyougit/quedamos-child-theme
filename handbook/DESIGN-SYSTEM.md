# Quedamos design system

The **values** of the system — colours, type, spacing, radii, breakpoints — and, for each one, the file
that actually defines it. This file is a map, not a second copy: where a number appears here it is quoted
from the source named beside it, so if the two ever disagree **the source wins and this file is stale.**

For the *rules* about using these values — naming tiers, the never-invent rule, nesting depth, the wiring
step — see [writing-scss](../.claude/skills/writing-scss/SKILL.md). Short version, because it governs
everything below: **reference the token, never the literal, and if the value you need has no token, stop
and ask rather than pasting a hex.**

## Where each kind of value lives

| Kind | Source of truth | Consumed in SCSS as |
|---|---|---|
| Colour palette | [theme.json](../themes/wp-child-theme-template/theme.json) `settings.color.palette` | `$primary`, `$secondary`, `$background-grey` |
| Font families + faces | [theme.json](../themes/wp-child-theme-template/theme.json) `settings.typography.fontFamilies` | `font-family: 'Poppins'` (literal name) |
| Font sizes | [theme.json](../themes/wp-child-theme-template/theme.json) `settings.typography.fontSizes` | `$h1-font-size` … `$small-font-size` |
| Element/block defaults (buttons, links, headings) | [theme.json](../themes/wp-child-theme-template/theme.json) `styles` | inherited — don't restate |
| Content width | [theme.json](../themes/wp-child-theme-template/theme.json) `settings.layout.contentSize` | `$content-width`, `contentWidth()` |
| WP spacing presets | [theme.json](../themes/wp-child-theme-template/theme.json) `settings.spacing.spacingSizes` | `var:preset|spacing|20` in block markup |
| Sass spacing scale | [variables.scss](../themes/wp-child-theme-template/assets/styles/scss/variables.scss) | `$space-1` … `$space-5` |
| Radius, shadow, mixins | [variables.scss](../themes/wp-child-theme-template/assets/styles/scss/variables.scss) | `$BR`, `qCard()`, `qCardSurface()` |
| Breakpoints | *nothing — no tokens exist* | raw `768px` / `992px` (see below) |

### The theme.json caveat — read this before trusting a number

`theme.json` here is a **Site Editor export, not a hand-authored theme file** — its first key is
`"isGlobalStylesUserThemeJSON": true`, and it carries no `$schema`, `templateParts` or `customTemplates`.
WordPress lets the database record `wp_global_styles` **override it**, which means an edit to the file can
fail to show on the front end.

Checked 2026-08-17: the local DB record (post ID 1129, "Custom Styles") contains only
`{"version": 3, "isGlobalStylesUserThemeJSON": true}` — no overrides at all. So today `theme.json` *is*
the live system. That can change the moment someone touches Styles in the Site Editor, so when a colour
or size behaves unexpectedly, read the DB record before debugging the CSS:

```bash
SOCK="$HOME/Library/Application Support/Local/run/p9tqzUp97/mysql/mysqld.sock"
WP="$HOME/Local Sites/quedamos/app/public"
php -d mysqli.default_socket="$SOCK" /usr/local/bin/wp --path="$WP" \
  post list --post_type=wp_global_styles --fields=ID,post_title
```

## Colour

The palette is split in two in `theme.json`: a `custom` group (brand) and a `theme` group (neutrals and
accents). WordPress emits every entry as `--wp--preset--color--{slug}`.

| Name | Slug | Hex | Sass token |
|---|---|---|---|
| Primary | `custom-primary` | `#be2c2d` — brand red | `$primary` |
| Secondary | `custom-secondary` | `#4285f4` — blue | `$secondary` |
| Grey Base | `base` | `#f6f7f6` | `$background-grey` |
| White | `base-2` | `#ffffff` | *none* |
| Black Contrast | `contrast` | `#000000` | *none* |
| Primary Dark | `contrast-2` | `#9d292a` | *none* |
| Contrast / Three | `contrast-3` | `#757783` | *none* |
| Accent | `accent` | `#fffb01` — yellow | *none* (but see `$accent-yellow`) |
| Accent / Two | `accent-2` | `#e5f0fe` — pale blue | *none* |

Note `$primary` is a **red**, not a teal — worth saying because `$hover-grey` is a teal and reads like a
brand colour when it isn't (see Known drift).

**Six of the nine palette colours have no Sass token.** So a component needing white, black or the pale
blue has nothing to reference — which is exactly the situation the never-invent rule covers: don't paste
`#fff`, add the alias to `variables.scss` (mirroring the three that exist) and use it. Page background is
already `base-2` via `theme.json` `styles.color.background`, so a component usually doesn't need white at
all.

## Typography

Three families, one weight each, all self-hosted `.woff` from
[assets/fonts/](../themes/wp-child-theme-template/assets/fonts/) and registered as `fontFace` entries in
`theme.json`:

| Family | Weight | File | Used for |
|---|---|---|---|
| Open Sans | 400 | `OpenSans-VariableFont_wdthwght.woff` | body default (`styles.typography.fontFamily`) |
| Poppins | 500 | `Poppins-Medium.woff` | all headings (`styles.elements.heading`), so h1–h4 |
| Raleway | 600 | `Raleway-SemiBold.woff` | h5 and h6 only, which override the heading family |

Only one weight per family is loaded. Asking for `font-weight: 700` on Poppins gets a synthesised faux
bold, not a real one — if a design needs another weight, the font file has to be added first.

### Size tokens

Larger sizes are fluid `clamp()`; the small end is fixed. Fluid range is set by
`settings.typography.fluid` (320px → 1200px viewport).

| Token | Preset slug | Value |
|---|---|---|
| `$h1-font-size` | `heading-one-size` | `clamp(2.5rem, 5vw, 4rem)` |
| `$h2-font-size` | `heading-two-size` | `clamp(2.0rem, 4vw, 4rem)` |
| `$h3-font-size` | `heading-three-size` | `clamp(1.8rem, 3vw, 3rem)` |
| `$h4-font-size` | `heading-four-size` | `1.6rem` |
| `$h5-font-size` | `heading-five-size` | `1.2rem` |
| `$h6-font-size` | `heading-six-size` | `1rem` |
| `$medium-font-size` | `medium` ("Body") | `clamp(1rem, 2.5vw, 1.1rem)` |
| `$small-font-size` | `small` | `0.8rem` |
| *none* | `extra-small` | `0.6rem` |

Line heights come from `settings.custom.lineHeight` — `small: 1.25`, `medium: 1.5`, `large: 1.75`, emitted
as `var(--wp--custom--line-height--small)` etc. All headings use `small`; paragraphs use `medium`.

The default component has **no typography rules at all** — use the right semantic tag and inherit.
[typography.scss](../themes/wp-child-theme-template/assets/styles/scss/typography.scss) is entirely
commented out, which is correct, not an oversight.

## Spacing — two parallel scales

This is the system's sharpest edge: there are **two unrelated spacing scales**, and which one you use
depends on whether you're writing block markup or SCSS.

**1. WordPress presets** (`theme.json`, referenced in block attributes as `var:preset|spacing|20`) — all
viewport-clamped except the first:

| Slug | Value |
|---|---|
| `10` | `1rem` |
| `20` | `min(1.5rem, 2vw)` |
| `30` | `min(2.5rem, 3vw)` |
| `40` | `min(4rem, 5vw)` |
| `50` | `min(6.5rem, 8vw)` |
| `60` | `min(10.5rem, 13vw)` |

**2. The Sass scale** (`variables.scss`, used in SCSS) — fixed pixels, and it does **not** line up with
the presets above:

| Token | Value |
|---|---|
| `$space-1` | `4px` |
| `$space-2` | `8px` |
| `$space-3` / `$space-default` | `16px` |
| `$space-4` | `32px` |
| `$space-5` | `64px` |

Plus shorthand sets for multi-value properties: `$space-inset-*` (even), `$space-squish-inset-*`,
`$space-stretch-inset-*`, `$space-stack-*` (bottom-only), `$vertical-space-*` (top+bottom). Each runs 1–5
on the same 4/8/16/32/64 progression.

Global block gap is `1rem`; root padding is `0 24px` (`styles.spacing.padding`) — note `24px` is not on
either scale.

**Caution:** the whole Sass scale is declared **twice** — in
[variables.scss](../themes/wp-child-theme-template/assets/styles/scss/variables.scss) and again at the
bottom of [style.scss](../themes/wp-child-theme-template/assets/styles/style.scss). The values are
currently identical. If you ever change one, change both, or delete the `style.scss` copy.

## Radius, shadow, layout

| Thing | Value | Source |
|---|---|---|
| Standard radius | `$BR` = `10px` | `variables.scss` |
| Button radius | `50px` (pill) | `theme.json` `styles.blocks.core/button.border.radius` |
| Card shadow | `0 4px 8px 0 rgba(0,0,0,.2), 0 6px 20px 0 rgba(0,0,0,.19)` | `qCardSurface()` mixin |
| Content width | `1280px` (`contentSize` **and** `wideSize`) | `theme.json`, aliased as `$content-width` |

Mixins, in preference order — reach for these before writing the properties by hand:

- `qCardSurface()` — shadow + radius only, for a card that manages its own padding.
- `qCard()` — `qCardSurface()` plus `padding: $space-4 !important`, full height, flex column. The
  `!important` is legacy; don't add more.
- `contentWidth()` — `max-width: $content-width; margin-inline: auto`. On a full-bleed band, put this on
  the **inner** element so the background stays edge-to-edge while content lines up with the page.
- `containerPadding()` — `3rem 2rem`, the standard section padding. Off both spacing scales; legacy.

## Buttons and links

Both are defined in `theme.json` and inherited — a component should not restate them.

**Button** (`styles.blocks.core/button`): background `$primary`, radius `50px`, padding
`0.75rem 1.5rem`, font size `medium`. Hover swaps the background to `$secondary` (red → blue) and keeps
the padding. There is no secondary or outline button variant in the system; if a design needs one, that's
a decision to raise, not to invent.

**Link** (`styles.elements.link`): colour `contrast` (`#000000`), `text-decoration: none`, underline on
hover. That underline-on-hover is the system's default hover treatment for text.

## Breakpoints

**There are no breakpoint tokens**, and the theme has drifted across five values. Current usage across
`assets/styles/`:

| Query | Occurrences |
|---|---|
| `min-width: 992px` | 9 |
| `min-width: 768px` | 6 |
| `max-width: 768px` | 3 |
| `min-width: 781px` | 2 |
| `min-width: 782px` | 2 |
| `min-width: 576px` | 1 |

**Use `768px` (mobile → tablet) and `992px` (tablet → desktop), mobile-first `min-width`.** Don't
introduce a sixth value. `781`/`782` exist only because `782px` is WordPress's own navigation-block
overlay breakpoint — if you're working on the header, that's the number core switches at, so match core
rather than the house value and say why in a comment.

## Known drift — present in the code, not part of the system

Do not copy these, and do not treat them as precedent. Bring them onto the system when you're already
rewriting the block that contains them.

- **`$hover-grey: rgba(35, 90, 108, 0.12)`** — a *teal*, hardcoded in `variables.scss`, matching no
  palette entry. It's tokenised, so it's usable, but it isn't derived from the brand.
- **`$accent-yellow: #fffb01`** — a literal duplicate of the `accent` palette entry. Two answers for one
  colour; prefer the preset.
- **`$domain-url: 'https://quedamoslanguages.com'`** in `variables.scss` — a hardcoded production URL in
  the style layer. See [writing-php](../.claude/skills/writing-php/SKILL.md) on the no-hardcoded-URL rule.
- **Header nav colour `#37423b`** in
  [navigation.scss](../themes/wp-child-theme-template/assets/styles/scss/navigation.scss) — an off-palette
  near-black, with `font-family: 'Poppins'` and `font-size: 15px` also hardcoded there.
- **`pages/home.scss`** carries `#ecf9f0`, `#555`, `#eef0ef`, `#ffbb2c`, `#5578ff`, `#e80368`, `#e361ff`,
  `#47aeff`, `#ffa76e` — none in the system. This is the debt the never-invent rule exists to stop.
- **`-container` as a generic class suffix** — widespread legacy naming. See writing-scss §1.

## Adding a value to the system

1. Grep [variables.scss](../themes/wp-child-theme-template/assets/styles/scss/variables.scss) and
   `scss/components/` for something that already answers it.
2. If nothing does, **ask** — don't pick it yourself.
3. Once agreed: add the token to `variables.scss`, record it in the right table above, and commit that
   alongside the change that needed it.

Colours are the exception to "add it to `variables.scss`": a genuinely new *brand* colour belongs in
`theme.json`'s palette first (so it's available in the editor too), then aliased into `variables.scss`.
