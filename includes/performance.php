<?php

/**
 * Frontend performance tweaks.
 *
 * @package vitalseedstore
 */

defined('ABSPATH') || exit;

/**
 * Base64-encoded WOFF2 containing only the four Dashicons glyphs the frontend uses.
 *
 * The decoded font is committed alongside this file at
 * assets/fonts/dashicons-subset.woff2 for reference.
 *
 * If the menu gains a new dashicon, add its codepoint and regenerate
 * (needs `pip install fonttools brotli`):
 *
 *   pyftsubset wp-includes/fonts/dashicons.woff2 \
 *     --unicodes=U+F179,U+F110,U+F140,U+F333 \
 *     --flavor=woff2 --no-hinting --desubroutinize \
 *     --output-file=assets/fonts/dashicons-subset.woff2
 *   base64 -i assets/fonts/dashicons-subset.woff2 | tr -d '\n'
 */
define('VITALSEEDSTORE_DASHICONS_SUBSET', 'd09GMgABAAAAAAJcAAsAAAAABCAAAAIOAAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAHCAGVgBMCoJkgjQBNgIkAw4LDAAEIAWBBAcgGyEDEI6U7jLD4Hle7+vcl3wpAyIzS6MaqV2B3VZ00ZnJBUhhy53+7y3xE4/1KdM0tpqHbWFbnLalhWEkuY1lUeARX7RWAxDATTyJSI9eA0bgRAewLABA5L+EDSdemQX01vFvxFALCpuGPjJkVs8ZdPkU/GIqgEIlCCAAgAI0IBwNAcLRaQO4caJQn4Kfunxa+8W0LFg7/J/8+1u89xFgbDR3A05gOrAaAEgHABSChoqI0HRdT3GNb47cENuJyB15R1tyDseHb4radSNGt9w5Dennys41t7rQKlOqzt7zzB4xarRPvAJ1Cv3Z15/kR6yPyFiP2OHNm/btO3AkckNk4ax86WmnI0+fPXr2ZMTRqofN9ysftFhG+Lub1ROL80d6Og8s8vTML55YfeVdWNeehQlDv8V3OZAd/mP4j/DoA10SHndJoqJ01qaZmxhQMcucdcecZc7cPKtsFq3WpbmmHWqov+j5s/p2zFFHoGrLnquNJ9aNHrFinJcjRh+j98jenMl5fXP/7v3M/mY/1gAAckXuAEillAHIOakEkKtyAwEEDu14LZMDHb45KQBetTJuVqH127KUTwngRAEg8Fa56QsTmlMuXBHAxjpCAAjI2RmEhuguBAMWFH4MaBhg67ARSXJvOwYwg6ksZw5zmc5iFrGcYcxkNitZwFSWMYqZLGM5c++HkymhkGI0AMBGAgAAAA==');

/**
 * Whether the Dashicons subset should be served.
 *
 * The subset covers exactly the four glyphs listed below. If the menu ever gains
 * a dashicon outside that set it will render as an empty box, so this is the
 * escape hatch — turn it off and core's full stylesheet is used again.
 *
 * From a snippet or plugin:
 *
 *   add_filter('vitalseedstore_dashicons_subset_enabled', '__return_false');
 *
 * Or, if the site is broken badly enough that hooks aren't an option, in
 * wp-config.php:
 *
 *   define('VITALSEEDSTORE_DISABLE_DASHICONS_SUBSET', true);
 *
 * The preferred fix is to add the missing codepoint and regenerate the subset;
 * see the regeneration notes above.
 *
 * @return bool
 */
function vitalseedstore_dashicons_subset_enabled()
{
	// Checked first so a broken site can be recovered without hooks loading.
	if (defined('VITALSEEDSTORE_DISABLE_DASHICONS_SUBSET') && VITALSEEDSTORE_DISABLE_DASHICONS_SUBSET) {
		return false;
	}

	return (bool) apply_filters('vitalseedstore_dashicons_subset_enabled', true);
}

/**
 * Serve a four-glyph Dashicons subset to anonymous visitors.
 *
 * Max Mega Menu enqueues core's dashicons stylesheet unconditionally (see
 * megamenu/classes/icons/dashicons.php), which is 35KB of render-blocking CSS
 * with the whole icon font inlined as base64. The frontend only ever draws four
 * glyphs from it:
 *
 *   \f179  dashicons-search        search link in the primary menu
 *   \f110  dashicons-admin-users   my-account link in the primary menu
 *   \f140  arrow-down              submenu indicator (span.mega-indicator:after)
 *   \f333  menu                    mobile hamburger (button.mega-toggle-standard:after)
 *
 * Re-registering the 'dashicons' handle before Mega Menu enqueues it means the
 * plugin's own wp_enqueue_style('dashicons') call transparently picks up this
 * subset instead, so no Mega Menu selectors need changing. The font-family name
 * and unitsPerEm are unchanged, so glyph metrics render identically.
 *
 * The \f140 and \f333 content rules live in Mega Menu's own generated
 * stylesheet; only the two class-based glyphs need their content declaring here,
 * since those rules came from the core stylesheet we are replacing.
 */
function vitalseedstore_dashicons_subset()
{
	if (!vitalseedstore_dashicons_subset_enabled()) {
		return;
	}

	// Logged-in users get the admin toolbar, which needs the full icon set.
	if (is_admin_bar_showing()) {
		return;
	}

	$css = sprintf(
		'@font-face{font-family:dashicons;src:url(data:font/woff2;base64,%s) format("woff2");font-weight:400;font-style:normal;font-display:block}' .
			'.dashicons,.dashicons-before:before{font-family:dashicons;display:inline-block;line-height:1;font-weight:400;font-style:normal;text-decoration:inherit;text-transform:none;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}' .
			'.dashicons-search:before{content:"\f179"}' .
			'.dashicons-admin-users:before{content:"\f110"}',
		VITALSEEDSTORE_DASHICONS_SUBSET
	);

	wp_deregister_style('dashicons');
	wp_register_style('dashicons', false);
	wp_add_inline_style('dashicons', $css);
}
// Priority 0 so the handle is replaced before Mega Menu enqueues it.
add_action('wp_enqueue_scripts', 'vitalseedstore_dashicons_subset', 0);


/**
 * Whether to skip Elementor's icon stylesheets on pages that don't use icons.
 *
 * Off by default — this is a surge switch, not an always-on optimisation. Turn
 * it on when load demands it, in wp-config.php:
 *
 *   define('VITALSEEDSTORE_LEAN_ICONS', true);
 *
 * Or from a snippet or plugin:
 *
 *   add_filter('vitalseedstore_lean_icons_enabled', '__return_true');
 *
 * The constant wins outright when defined, so it can force the feature both on
 * and off regardless of what any filter says.
 *
 * @return bool
 */
function vitalseedstore_lean_icons_enabled()
{
	if (defined('VITALSEEDSTORE_LEAN_ICONS')) {
		return (bool) VITALSEEDSTORE_LEAN_ICONS;
	}

	return (bool) apply_filters('vitalseedstore_lean_icons_enabled', false);
}

/**
 * Whether the current page is known not to render any Elementor icons.
 *
 * Elementor renders on WooCommerce pages, but only a global form/popup template
 * (container, form, heading, text-editor) — none of which draw an icon. The
 * icon-bearing widgets (icon-list, testimonial-carousel) are confined to the
 * homepage and other Elementor-built pages. Sampled across three products,
 * three category archives, shop, cart and about: no eicon-* or fa-* classes.
 *
 * Filter this to carve out an exception without editing the theme, e.g. if a
 * popup on product pages later gains an icon:
 *
 *   add_filter('vitalseedstore_iconless_page', function ($iconless) {
 *       return is_product() ? false : $iconless;
 *   });
 *
 * @return bool
 */
function vitalseedstore_is_iconless_page()
{
	if (!function_exists('is_woocommerce')) {
		return false;
	}

	$iconless = is_product()
		|| is_product_category()
		|| is_shop()
		|| is_cart()
		|| is_checkout()
		|| is_account_page();

	return (bool) apply_filters('vitalseedstore_iconless_page', $iconless);
}

/**
 * Whether the current page is known not to render the grid/list view toggle.
 *
 * woocommerce-grid-list-view registers its own copy of FontAwesome 4 under the
 * generic 'font-awesome' handle (berocket/framework.php) and enqueues it on
 * every frontend page. The toggle it draws — fa-bars and fa-th — only appears on
 * product category archives.
 *
 * Product archives are deliberately left alone, including the shop page. The
 * shop page currently shows no toggle (it is an Elementor-built page listing
 * category tiles rather than a product loop) but its layout is editable, so
 * excluding it here would be a trap for whoever changes it next.
 *
 * @return bool
 */
function vitalseedstore_is_gridlist_iconless_page()
{
	if (!function_exists('is_woocommerce')) {
		return false;
	}

	$iconless = is_product()
		|| is_cart()
		|| is_checkout()
		|| is_account_page();

	return (bool) apply_filters('vitalseedstore_gridlist_iconless_page', $iconless);
}

/**
 * Drop Elementor's icon stylesheets on WooCommerce pages.
 *
 * Elementor enqueues these on every page regardless of whether any icon is
 * drawn. On product, category, cart and account pages that is ~29KB of
 * render-blocking CSS/JS across four requests, none of it used:
 *
 *   elementor-icons       elementor-icons.min.css   5.2KB
 *   font-awesome-5-all    all.min.css              14.1KB
 *   font-awesome-4-shim   v4-shims.min.css + .js   10.1KB
 *
 * The v4 shims are dequeued here too, so this stays independent of Elementor's
 * own "Load Font Awesome 4 Support" setting — that can be turned off separately
 * once the FA4 to FA5 migration is confirmed complete.
 *
 * The webfonts themselves are not dequeued because they are never enqueued:
 * a browser only fetches an icon font when a glyph in that family is rendered,
 * so removing the stylesheets removes the fonts as a consequence.
 */
function vitalseedstore_lean_icons()
{
	if (!vitalseedstore_lean_icons_enabled()) {
		return;
	}

	if (vitalseedstore_is_iconless_page()) {
		foreach (array('elementor-icons', 'font-awesome-5-all', 'font-awesome-4-shim') as $handle) {
			wp_dequeue_style($handle);
		}

		wp_dequeue_script('font-awesome-4-shim');
	}

	// woocommerce-grid-list-view's own FontAwesome copy, on a narrower rule —
	// its toggle still needs the font on category archives.
	if (vitalseedstore_is_gridlist_iconless_page()) {
		wp_dequeue_style('font-awesome');
	}
}
// Priority 100: Elementor enqueues its frontend styles at 20, and triggers the
// FontAwesome enqueue from inside that, so this has to run afterwards.
add_action('wp_enqueue_scripts', 'vitalseedstore_lean_icons', 100);
