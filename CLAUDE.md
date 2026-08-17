# Quedamos Languages — wp-content

Spanish language school site (quedamoslanguages.com). WordPress FSE, `twentytwentyfour` parent with the
`wp-child-theme-template` child theme doing all the real work. Assets build through Parcel.

The repo root is `wp-content/`, not the WordPress root — `wp-config.php` and core live above it and are not
version-controlled.

## The overarching rule: the system is king — never invent

**Find the existing pattern, mirror it. If none exists, ASK before picking.** Before naming a function,
placing a file, choosing a colour, or picking a class name, look at how the codebase already does it. A
value invented on the spot is a value that drifts.

This rule is why the `writing-*` skills exist: they record what the pattern *is*, so it doesn't have to be
rediscovered (or guessed at) every session.

## Follow the matching skill

Read the skill **before** writing code, not after:

| Working on | Read |
|---|---|
| Any `.php` under `themes/wp-child-theme-template/` | [writing-php](.claude/skills/writing-php/SKILL.md) |
| Any `.scss` under `themes/wp-child-theme-template/assets/styles/` | [writing-scss](.claude/skills/writing-scss/SKILL.md) |
| Any `.js` under `themes/wp-child-theme-template/assets/scripts/` | [writing-js](.claude/skills/writing-js/SKILL.md) |

## Redirects live in one file

Every 301 on this site is a single line in `quedamos_redirect_map()` in
[inc/redirects/redirects.php](themes/wp-child-theme-template/inc/redirects/redirects.php) — never a
redirect plugin, an `.htaccess` rule, or a stray `wp_redirect()` in another module. When a URL misbehaves
there must be exactly one place to look. Rules and rationale:
[writing-php §9](.claude/skills/writing-php/SKILL.md).

Add the map entry **before** deleting the old page; the redirect fires whether or not the page still
exists, so there's no window where the URL 404s.

## Known state — read before you trust the theme layout

Three things are true today that will surprise you if you assume a standard block theme:

1. **`theme.json` is a Site Editor export, not a hand-authored theme file.** Its first key is
   `"isGlobalStylesUserThemeJSON": true`. It has no `$schema`, no `templateParts`, no `customTemplates`.
   The matching database record (`wp_global_styles`) **overrides it** — so an edit to this file may not
   show up on the front end until the DB copy is cleared. Verify in the browser, never assume.
2. **`templates/` is for block templates (`.html`) only.** It previously held PHP partials, which was a
   collision with WordPress's own meaning for that folder; those have moved to `inc/`.
3. **Legacy code predates these conventions.** Unprefixed functions (`course_query_shortcode`,
   `output_acf_schema`), unescaped output and hardcoded production URLs exist in the theme. Leave them
   alone when you're not touching them; bring them up to standard when you are. Do not imitate them.

## Environment

`home_url()` resolves to `localhost:10033` locally (Local by Flywheel — the port is assigned by Local
and can change, so check `wp option get home` rather than trusting this number) and
`quedamoslanguages.com` on live. Anything that must only run in production gates on
`quedamos_is_live_site()` — never on a `WP_ENVIRONMENT_TYPE` constant, which is unset here and defaults
to `production` even locally.

## Git

`main` is protected. Never commit or push to it without Sigurd's express permission **for that specific
change** — permission for one push does not carry to the next. Default to a feature branch.
