# Vital Seedstore

Wordpress woocommerce theme.

Created as a storefront child theme.

Initially derived from 'Vitalseeds theme II' by [Merlin Howse](www.merlinhowse.co.uk).

## Performance

### Dashicons subset

Max Mega Menu enqueues WordPress core's Dashicons stylesheet on every frontend
page (see `megamenu/classes/icons/dashicons.php`). That file is ~35KB of
render-blocking CSS with the entire icon font inlined as base64 — but the
frontend only ever draws four glyphs from it:

| Codepoint | Icon | Used by |
|---|---|---|
| `\f179` | `dashicons-search` | search link in the primary menu |
| `\f110` | `dashicons-admin-users` | my-account link in the primary menu |
| `\f140` | `arrow-down` | submenu indicator (`span.mega-indicator:after`) |
| `\f333` | `menu` | mobile hamburger (`button.mega-toggle-standard:after`) |

`includes/performance.php` re-registers the `dashicons` handle at
`wp_enqueue_scripts` priority 0, before Mega Menu enqueues it, pointing at a
four-glyph subset inlined as base64 (~1.2KB, no HTTP request). Mega Menu's own
`wp_enqueue_style('dashicons')` transparently picks up the subset, so no Mega
Menu selectors need changing.

Logged-in users are skipped (`is_admin_bar_showing()`) because the admin toolbar
needs the full icon set.

#### Turning it off

The subset covers exactly the four glyphs above. If the menu gains a dashicon
outside that set it will render as an empty box — the fix is to add the
codepoint and regenerate (below), but there are two escape hatches if you need
the icons back immediately.

From a snippet or plugin:

```php
add_filter('vitalseedstore_dashicons_subset_enabled', '__return_false');
```

Or in `wp-config.php`, if the site is broken badly enough that hooks aren't an
option:

```php
define('VITALSEEDSTORE_DISABLE_DASHICONS_SUBSET', true);
```

The constant is checked first and short-circuits before the filter runs. Either
one restores core's full Dashicons stylesheet. Both are free at runtime — no
extra queries, just a constant check and a filter call.

**Regenerating the subset** — if the menu gains a new dashicon, find its
codepoint in `wp-includes/css/dashicons.min.css`, add it to the `--unicodes`
list, and rebuild (needs `pip install fonttools brotli`):

```sh
pyftsubset wp-includes/fonts/dashicons.woff2 \
  --unicodes=U+F179,U+F110,U+F140,U+F333 \
  --flavor=woff2 --no-hinting --desubroutinize \
  --output-file=assets/fonts/dashicons-subset.woff2

base64 -i assets/fonts/dashicons-subset.woff2 | tr -d '\n'
```

Paste the base64 output into `VITALSEEDSTORE_DASHICONS_SUBSET` in
`includes/performance.php`. The decoded font is committed at
`assets/fonts/dashicons-subset.woff2` for reference.

If the new glyph is applied via a `dashicons-*` class in markup (rather than a
`content:` rule in Mega Menu's own stylesheet), add a matching `:before` content
rule to the inline CSS too — those rules came from the core stylesheet being
replaced.

### Lean icons (surge switch)

**Off by default.** A load-shedding switch, not an always-on optimisation.

Elementor enqueues its icon stylesheets on every page whether or not any icon is
drawn. On WooCommerce pages that is ~29KB of render-blocking CSS/JS across four
requests, none of it used:

| Handle | File | Size |
|---|---|---|
| `elementor-icons` | `elementor-icons.min.css` | 5.2KB |
| `font-awesome-5-all` | `all.min.css` | 14.1KB |
| `font-awesome-4-shim` | `v4-shims.min.css` + `.js` | 10.1KB |

Elementor does render on those pages, but only a global form/popup template
(`container`, `form`, `heading`, `text-editor`) — none of which draw an icon.
The icon-bearing widgets (`icon-list`, `testimonial-carousel`) are confined to
the homepage. Sampled across three products, three category archives, shop, cart
and about: no `eicon-*` or `fa-*` classes.

Enabling it skips those stylesheets on product, category, shop, cart, checkout
and account pages. The webfonts (`eicons.woff2` ~101KB, `fa-solid-900.woff2`
~77KB) go with them — a browser only fetches an icon font when a glyph in that
family is actually rendered, so removing the stylesheets removes the fonts as a
consequence.

The switch also drops `woocommerce-grid-list-view`'s own FontAwesome 4 copy
(7.8KB, registered under the generic `font-awesome` handle), but on a narrower
rule — that plugin's grid/list toggle draws `fa-bars` and `fa-th` on category
archives and genuinely needs it there:

| Page | Elementor icon CSS | grid/list FontAwesome |
|---|---|---|
| product | dropped | dropped |
| cart / checkout / account | dropped | dropped |
| category archive | dropped | **kept** |
| shop | dropped | **kept** |
| everything else | kept | kept |

Shop keeps the grid/list stylesheet even though it currently shows no toggle —
it is an Elementor-built page listing category tiles rather than a product loop,
but that layout is editable, so excluding it would be a trap for whoever changes
it next. Override with the `vitalseedstore_gridlist_iconless_page` filter if you
want it dropped there too.

#### Turning it on

In `wp-config.php`:

```php
define('VITALSEEDSTORE_LEAN_ICONS', true);
```

Or from a snippet or plugin:

```php
add_filter('vitalseedstore_lean_icons_enabled', '__return_true');
```

The constant wins outright when defined, so it can force the feature both on and
off regardless of any filter.

#### Carving out exceptions

If a popup or global template on a WooCommerce page later gains an icon, exclude
that page rather than turning the whole thing off:

```php
add_filter('vitalseedstore_iconless_page', function ($iconless) {
    return is_product() ? false : $iconless;
});
```

#### Related, but separate

The v4 shims are dequeued here so the switch stands alone, but they are dead
weight on *every* page, not just WooCommerce ones. Elementor has its own setting
for them — **Elementor → Settings → Advanced → Load Font Awesome 4 Support →
No** — which is the better fix, worth ~300ms sitewide.

Two cautions before flipping it. Elementor enqueues FontAwesome's `all.min.css`
only from inside `enqueue_shim()`, so turning the shim off removes FontAwesome
entirely unless the active *"Remove fontawesome"* snippet (which deregisters
`elementor-icons-fa-solid`/`-regular`/`-brands`) is retired at the same time. And
run **Elementor → Tools → Font Awesome Upgrade** first, so no FA4 icon names are
left relying on the shim.
