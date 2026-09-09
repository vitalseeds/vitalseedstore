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
