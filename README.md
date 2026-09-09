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
