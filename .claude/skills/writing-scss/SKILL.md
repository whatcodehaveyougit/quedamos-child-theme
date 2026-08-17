---
name: writing-scss
description: SCSS coding standards for the Quedamos wp-child-theme-template theme. Two pillars — (1) the design system is king; never invent values (colours, spacing, radii, shadows, breakpoints, hover states); and (2) the three-tier naming system: page wrapper `.{slug}-page` → section `.{name}-section` → reusable component in BEM, picked by ONE test (does this live on one page, or many?). Plus: design tokens from variables.scss (no hardcoded hex), typography via the $…-font-size tokens not raw rem, the reusable-widget rule (cards/buttons live in components/, never re-declared in a page partial), ALWAYS nesting a component's BEM `&__element`/`&--modifier` parts under the block, max nesting depth 3, the @import wiring step in style.scss, and the Parcel build. Use whenever creating or modifying any .scss file under themes/wp-child-theme-template/assets/styles/.
---

# Writing SCSS (wp-child-theme-template)

These conventions apply to **all** SCSS in `themes/wp-child-theme-template/assets/styles/`. Read before
adding or changing any `.scss` file.

Sibling skills:
- [writing-php](../writing-php/SKILL.md) — PHP conventions
- [writing-js](../writing-js/SKILL.md) — frontend behaviour

## 1. Naming — three tiers, one mechanical test

> ### The one test that picks the tier
> **Does this thing live on _one page_, or _many_?**
> - **One page** → a **scope (Tier 1)** or a **section (Tier 2)**, or a plainly-named inner element.
> - **Many** → a **component (Tier 3)**, full BEM, in `components/`.
>
> That's the whole decision. One page vs many.

| Tier | Use it for | Class form | Lives in | Existing examples |
|---|---|---|---|---|
| **1 · Page wrapper** | the whole page body — **one per page** | `.{page-slug}-page` — the slug is *derived from the template, never invented* | `pages/{slug}.scss` | `.about-page`, `.course-page` |
| **2 · Section** | a named stacked region within one page | `.{name}-section` | the page partial | `.hero-section`, `.article-cta-section` |
| **3 · Component** | a widget reused on **2+ pages** — full BEM | `.{c}` / `.{c}__{part}` / `.{c}--{variant}` | `components/{c}.scss` — **never** re-declared in a page partial | `.post-card`, `.q-card` |
| *(not a tier)* **Inner element** | a one-off bit inside a page/section | plain descriptive kebab, scoped by the wrapper | the page partial | `.hero-grid`, `.lede` |

A page **positions a component, never repaints it.** `.about-page .post-card { margin-block-start: … }`
(layout) is fine; restyling `.post-card`'s internals from a page partial is not — change it in
`components/post-card.scss` or add a `--modifier`.

**Inner elements get a real name, not a cryptic prefix.** `.q-wrap` / `.A-grid` → `.intro-wrap` /
`.hero-grid`. The wrapper already guarantees uniqueness, so no BEM and no `-section` suffix is needed on
a one-off inner bit.

### `-container` is deprecated as a generic suffix

`-container` is the tell of an uncodified system — it says nothing about what the thing *is*. A thing is
a `-page`, a `-section`, a named component, or a plain scoped descendant.

The theme currently carries a lot of it — `.courses-container`, `.why-choose-us-container`,
`.q-info-cards-container`, `.page-header-container`. **That's legacy. Don't extend it and don't imitate
it.** Rename when you're already rewriting a block of styles; leave it alone otherwise.

(Never touch third-party plugin classes like `.ti-widget` — SCSS only *targets* those.)

### A bare element selector is a site-wide rule — scope it

Outside `reset.scss` and `typography.scss`, **never leave a rule keyed on an element alone** (`ul`, `img`,
`figure`, `section`). It reads as local because of the file it sits in; it isn't. Anchor it to the thing it
is actually for.

```scss
// ❌ BAD — in the header's stylesheet, but hits every list on the site
@media screen and (min-width: 781px) {
  ul { align-items: center; }
}

// ✅ GOOD — says what it's for
ul.wp-block-navigation__container { align-items: center; }
```

The bad version is real: it shipped in `mobile-navigation.scss` (now `site-navigation.scss`) and centred every flex-or-grid `ul` above
781px. WordPress renders a post-template grid as a `<ul>`, so the related-posts cards on a single post came
out staggered and unequal — a bug with no visible connection to the navigation file it came from.

**This is why block markup needs reading before styling.** WordPress decides the tag: query loops are
`ul`/`li`, featured images are `figure`, columns are `div`. Check what the block actually emits (view
source, or the template) rather than assuming.

### Always nest BEM parts under the block — never flat

A component's `&__element` and `&--modifier` selectors are written as nested `&` selectors under the one
block rule, **never** as flat repeated selectors at the root. Flat BEM is a common convention elsewhere;
in this theme it is wrong every time.

```scss
// ❌ BAD — flat, block name repeated, related rules drift apart
.post-card { … }
.post-card--featured { … }
.post-card__title { … }

// ✅ GOOD — nested under the block
.post-card {
  …
  &--featured { … }
  &__title { … }
}
```

**Nest to mirror the DOM, too.** A child rendering inside `.post-card__body` is written inside
`.post-card__body { … }`, not as a flat sibling. Read the markup first and let its hierarchy drive the
nesting.

### Max nesting depth 3

A hard ceiling. It's the thing that structurally prevents specificity debt and the `!important` spiral.
Reach for `:where()` / `:is()` over `!important`.

The existing `qCard()` mixin uses `padding: … !important` — legacy, and the reason this rule exists.
Don't add more.

## 2. The design system is king — never invent values

**Non-negotiable.** §1 governs *structure* (how things are named); this governs *values* — colours,
spacing, radii, shadows, breakpoints, hover states, transitions.

Before reaching for **any** style value, find the existing pattern and mirror it. Don't pick a value
because it looks right, or because a token's name sounds close enough.

### Workflow when introducing any style value

1. **Does a token or mixin already exist?** Grep [variables.scss](../../../themes/wp-child-theme-template/assets/styles/scss/variables.scss).
   Grep `components/` for a similar widget. Look at a sibling context.
2. **If yes — use it verbatim**, even if something else might look slightly better here. Consistency
   beats local optimisation.
3. **If no — STOP and ask.** Show what you checked and what the candidate would be. Don't pick it
   yourself.
4. **Once decided, codify it** — add the token to `variables.scss` and note the pattern here, so the
   next session finds the answer instead of re-inventing it.

### Why the rule is absolute

A design system only works if it's the **only** source of style values. The moment one component invents
its own colour, the system has two answers — and two silently becomes fifty. The theme already shows
this: `pages/home.scss` alone carries `#ecf9f0`, `#555`, `#eef0ef`, `#ffbb2c`, `#5578ff`, `#e80368`,
`#e361ff`, `#47aeff`, `#ffa76e` — none of them in the system, none reusable, none findable. That is the
debt this rule exists to stop growing.

## 3. Design tokens — single source of truth

All tokens live in `variables.scss`. **Reference the token, never the literal.**

Unlike some themes, these are **Sass variables (`$name`), not CSS custom properties** — several are thin
aliases of WordPress's emitted `var(--wp--preset--*)`. Use the `$name` form at call sites.

| Kind | Tokens |
|---|---|
| Colour | `$primary`, `$secondary`, `$background-grey`, `$hover-grey`, `$accent-yellow` |
| Radius | `$BR` (10px) |
| Font size | `$h1-font-size` … `$h6-font-size`, `$medium-font-size`, `$small-font-size` |
| Spacing (single value) | `$space-1` (4px) … `$space-5` (64px), `$space-default` (16px) |
| Spacing (shorthand sets) | `$space-inset-*`, `$space-squish-inset-*`, `$space-stretch-inset-*`, `$space-stack-*`, `$vertical-space-*` |
| Mixins | `qCard()` — the standard card shadow/radius/padding; `containerPadding()` — the standard section padding |

```scss
// ❌ BAD — invented values, unfindable, drifts
.article-card {
  background: #fff;
  border-radius: 8px;
  padding: 30px;
}

// ✅ GOOD — the system already answers all three
.article-card {
  @include qCard();
}
```

**If a value you need has no token, STOP and ask** before pasting a literal — then add the token.

### Breakpoints — use 768px and 992px

The theme has **no breakpoint tokens**, and has drifted across `576`, `768`, `781`, `782` and `992`.
Until tokens are added, mirror the two dominant values — **`768px`** (mobile → tablet) and **`992px`**
(tablet → desktop) — and write mobile-first (`min-width`), which is the majority pattern.

Do not introduce a sixth breakpoint value. If a layout genuinely needs one, ask.

## 4. Typography — inherit by default

The theme defines heading sizes through the WordPress preset system, aliased into `variables.scss`.
Overriding these per-component scatters typography and drifts from the system.

1. **Use the right semantic tag.** A card title is an `<h3>`, body copy is a `<p>`. The theme's styles
   apply automatically. Don't use a `<div>` and re-create heading styles in SCSS.
2. **Default component SCSS has no typography rules.** Don't set `font-size` / `font-weight` /
   `line-height` to "make it look right" — usually the wrong tag was used, or the default is correct.
3. **When an override IS justified, use the token — never a raw value.** A literal `font-size: 18px`
   anywhere outside `variables.scss` is as forbidden as a hardcoded hex, even when the literal equals a
   token's value.

```scss
// ❌ BAD
font-size: 1.25rem;

// ✅ GOOD
font-size: $medium-font-size;
```

## 5. Reusable widgets live in `components/` — never re-declared

A card, button, pill or badge used on 2+ pages is a Tier-3 component with **one** home in
`components/{name}.scss`. A page partial may position it; it may never restyle its internals.

If you need a variant, add a `&--modifier` to the component — don't fork it into the page partial.

**Layout that's shared goes in a mixin, not a copy-paste.** `qCard()` and `containerPadding()` are the
precedent; follow it rather than repeating a shadow or padding stack.

## 6. File layout and the wiring step

```
assets/styles/
├── style.scss              ← the entry; @imports every partial
└── scss/
    ├── variables.scss      ← tokens + mixins (imported by partials that need them)
    ├── reset.scss
    ├── typography.scss
    ├── global.scss
    ├── components.scss     ← legacy shared bits
    ├── components/         ← Tier-3 components, one file each
    └── pages/{slug}.scss   ← Tier-1 page partials, one file each
```

**A new partial is invisible until it's wired in.** Two steps, always both:

1. Create `scss/components/{name}.scss` or `scss/pages/{slug}.scss`.
2. Add `@import url('./scss/…');` to [style.scss](../../../themes/wp-child-theme-template/assets/styles/style.scss).

A partial that uses tokens must `@import '../variables.scss';` at its top (mirror `pages/contact.scss`).

**Build with Parcel** from the theme root:

```
npm run watch    # dev — rebuilds dist/ on save
npm run build    # production
```

`dist/` is gitignored and generated — never edit it, and never commit it. If a style change isn't
showing, check the watcher is running before debugging the CSS.

## 7. Adding a new convention

If you discover an SCSS pattern that should be enforced project-wide, **edit this file** and commit it
alongside the change that motivated it. Skills are committed to git and apply to every collaborator —
human or Claude — working in this repo.
